<?php
/**
 * Created for lokilizer
 * Date: 2025-02-28 13:54
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Services;

use PrinsFrank\Standards\Language\LanguageAlpha2;
use UnexpectedValueException;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Models\Localization\Components\AbstractPluralValue;
use XAKEPEHOK\Lokilizer\Models\Localization\Components\SimpleValue;
use XAKEPEHOK\Lokilizer\Models\Localization\Db\RecordRepo;
use XAKEPEHOK\Lokilizer\Models\Localization\PluralRecord;
use XAKEPEHOK\Lokilizer\Models\Localization\Record;
use XAKEPEHOK\Lokilizer\Models\Localization\SimpleRecord;

class LLMRecordService
{

    public function __construct(
        private RecordRepo $recordRepo,
    )
    {
    }



    public function representRecord(Record $record, LanguageAlpha2 ...$languages): array
    {
        $values = [];
        foreach ($languages as $language) {
            $value = $record->getValue($language);

            if (is_null($value)) {
                continue;
            }

            $context = [
                'nearByValues' => $this->simpleContext($record, $language),
                'verifiedByHuman' => $value->verified,
            ];

            if (Current::getProject()->getPrimaryLanguage() === $language) {
                $context['isPrimaryLanguage'] = true;
            }

            if (Current::getProject()->getSecondaryLanguage() === $language) {
                $context['isSecondaryLanguage'] = true;
            }

            if ($value instanceof AbstractPluralValue) {
                $values[$language->value] = [
                    'context' => [
                        ...$context,
                        'plural' => [
                            'type' => $value->getType(),
                            'categories' => $value::getCategoriesForLanguage($language, $value->getType()),
                        ],
                    ],
                    'value' => $value,
                ];
            }

            if ($value instanceof SimpleValue) {
                $values[$language->value] = [
                    'context' => [
                        ...$context
                    ],
                    'value' => $value,
                ];
            }
        }

        return [
            'keyInTranslationFile' => $record->getKey(),
            'commentForKey' => $record->getComment(),
            'values' => $values,
        ];
    }

    public function getJsonSchemaType(Record $record, LanguageAlpha2 $language): array
    {
        if ($record instanceof PluralRecord) {
            /** @var AbstractPluralValue $value */
            $value = $record->getPrimaryValue();
            $categories = $value::getCategoriesForLanguage($language, $record->getType());
            return [
                'type' => 'object',
                'properties' => array_combine(
                    $categories,
                    array_map(fn() => ['type' => 'string'], $categories)
                ),
                'required' => $categories,
                'additionalProperties' => false
            ];
        }

        if ($record instanceof SimpleRecord) {
            return ['type' => 'string'];
        }

        throw new UnexpectedValueException('Invalid record type');
    }

    private function simpleContext(Record $record, LanguageAlpha2 $language): array
    {
        $records = array_filter(
            $this->recordRepo->findNearby($record),
            fn(Record $found) => $found !== $record && !$found->getValue($language)?->isEmpty()
        );

        $context = [];
        foreach ($records as $contextRecord) {
            $context[$contextRecord->getParent()][explodeLast($contextRecord->getKey(), '.')] = $contextRecord->getValue($language);
        }

        return $context;
    }

}