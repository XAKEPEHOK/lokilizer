<?php

namespace XAKEPEHOK\Lokilizer\Apps\Mcp;

use DiBify\DiBify\Exceptions\DuplicateModelException;
use DiBify\DiBify\Exceptions\SerializerException;
use DiBify\DiBify\Exceptions\UnassignedIdException;
use DiBify\DiBify\Repository\Components\Pagination;
use DiBify\DiBify\Repository\Components\Sorting;
use InvalidArgumentException;
use PrinsFrank\Standards\Language\LanguageAlpha2;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Models\Glossary\Db\Storage\GlossaryRepo;
use XAKEPEHOK\Lokilizer\Models\Glossary\GlossaryItem;
use XAKEPEHOK\Lokilizer\Models\Glossary\SpecialGlossary;
use XAKEPEHOK\Lokilizer\Models\Localization\Db\RecordRepo;
use XAKEPEHOK\Lokilizer\Models\Localization\PluralRecord;

class LocalizationTools
{

    public function __construct(
        private readonly RecordRepo $recordRepo,
        private readonly GlossaryRepo $glossaryRepo,
    )
    {
    }

    /**
     * List ISO 639-1 languages used in the project and identify primary and optional secondary languages.
     */
    public function getLanguages(): array
    {
        $project = Current::getProject();
        $languages = $this->withProjectLanguages($this->recordRepo->fetchLanguages(true));

        return [
            'languages' => array_map(
                fn(LanguageAlpha2 $language) => $language->value,
                $languages
            ),
            'primary' => $project->getPrimaryLanguage()->value,
            'secondary' => $project->getSecondaryLanguage()?->value,
        ];
    }

    /**
     * Get the primary and special glossaries. Primary and optional secondary translations are always included.
     *
     * @param string|null $language ISO 639-1 code of the working language to include
     * @return array
     * @throws DuplicateModelException
     * @throws SerializerException
     * @throws UnassignedIdException
     */
    public function getGlossaries(?string $language = null): array
    {
        $languages = $this->withProjectLanguages(
            $language === null ? [] : [$this->parseLanguage($language)]
        );

        return array_map(
            function ($glossary) use ($languages) {
                $data = [
                    'type' => $glossary instanceof SpecialGlossary ? $glossary->getKeyPrefix() : 'primary',
                    'summary' => $glossary->getSummary(),
                    'items' => array_values(array_map(
                        fn(GlossaryItem $item) => [
                            'description' => $item->description,
                            'translations' => $this->glossaryTranslations($item, $languages),
                        ],
                        array_filter(
                            $glossary->getItems(),
                            fn(GlossaryItem $item) => $item->isComplete()
                        )
                    )),
                ];

                return $data;
            },
            $this->glossaryRepo->findAll()
        );
    }

    /**
     * Find current localization records by value with pagination.
     * Each result includes its key, primary-language comment, and selected language values.
     *
     * @param string $query Case-insensitive substring to find in UI values
     * @param string|null $searchLanguage ISO 639-1 language to search; omit to search values in all languages
     * @param string|null $resultLanguage ISO 639-1 additional language to include in results
     * @param int $page Page number, starting at 1; each page contains up to 50 records
     */
    public function searchRecords(
        string $query,
        ?string $searchLanguage = null,
        ?string $resultLanguage = null,
        int $page = 1,
    ): array
    {
        $query = trim($query);
        if ($query === '') {
            throw new InvalidArgumentException('Query cannot be empty');
        }

        if ($page < 1) {
            throw new InvalidArgumentException('Page must be greater than or equal to 1');
        }

        $searchLanguage = $searchLanguage === null
            ? null
            : $this->parseLanguage($searchLanguage);
        $resultLanguage = $resultLanguage === null
            ? null
            : $this->parseLanguage($resultLanguage);

        $filters = [
            'value' => $query,
            'outdated' => false,
        ];

        if ($searchLanguage !== null) {
            $filters['language'] = $searchLanguage->value;
        }

        $perPage = 50;
        $total = $this->recordRepo->countByAPI($filters);
        $records = $this->recordRepo->searchByAPI(
            $filters,
            new Pagination($page, $perPage),
            new Sorting('position', Sorting::SORT_ASC)
        );

        $languages = $this->withProjectLanguages(array_filter([
            $searchLanguage,
            $resultLanguage,
        ]));

        return [
            'items' => array_map(
                fn($record) => [
                    'key' => $record->getKey(),
                    'type' => $record instanceof PluralRecord ? $record->getType() : 'simple',
                    'comment' => $record->getComment(),
                    'values' => $this->recordValues($record, $languages),
                ],
                $records
            ),
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $total,
                'totalPages' => (int) ceil($total / $perPage),
            ],
        ];
    }

    private function glossaryTranslations(GlossaryItem $item, array $languages): array
    {
        $translations = [];
        foreach ($languages as $language) {
            $phrase = $item->getByLanguage($language);
            if ($phrase !== null) {
                $translations[$language->value] = $phrase->phrase;
            }
        }
        return $translations;
    }

    private function recordValues($record, array $languages): array
    {
        $values = [];
        foreach ($languages as $language) {
            $value = $record->getValue($language);
            if ($value !== null) {
                $values[$language->value] = $value->jsonSerialize();
            }
        }
        return $values;
    }

    private function withProjectLanguages(array $languages): array
    {
        $project = Current::getProject();
        $result = [];
        foreach ([...$project->getLanguages(), ...$languages] as $language) {
            $result[$language->value] = $language;
        }
        return array_values($result);
    }

    private function parseLanguage(string $language): LanguageAlpha2
    {
        $parsed = LanguageAlpha2::tryFrom(strtolower(trim($language)));
        if ($parsed === null) {
            throw new InvalidArgumentException("Invalid ISO 639-1 language code: {$language}");
        }
        return $parsed;
    }

}
