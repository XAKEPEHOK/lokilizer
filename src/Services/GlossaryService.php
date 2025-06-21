<?php
/**
 * Created for lokilizer
 * Date: 2025-02-15 04:08
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Services;

use XAKEPEHOK\Lokilizer\Models\Glossary\Db\Storage\GlossaryRepo;
use XAKEPEHOK\Lokilizer\Models\Glossary\Glossary;
use XAKEPEHOK\Lokilizer\Models\Glossary\GlossaryItem;
use XAKEPEHOK\Lokilizer\Models\Glossary\PrimaryGlossary;
use XAKEPEHOK\Lokilizer\Models\Glossary\SpecialGlossary;
use XAKEPEHOK\Lokilizer\Models\Localization\Record;

class GlossaryService
{

    public const SIMILARITY_THRESHOLD = 0.55;

    /** @var Glossary[]  */
    private array $glossaries;

    public function __construct(
        private GlossaryRepo $glossaryRepo,
    )
    {
    }

    public function getDistilled(Record $record, int $minTerminologyCount = 1): array
    {
        if (!isset($this->glossaries)) {
            $this->glossaries = $this->glossaryRepo->findAll();
        }

        $glossaries = array_filter(
            $this->glossaries,
            function (Glossary $glossary) use ($record) {
                if ($glossary instanceof PrimaryGlossary) {
                    return true;
                }

                if ($glossary instanceof SpecialGlossary) {
                    return $glossary->isForKey($record->getKey());
                }

                return false;
            },
        );

        $distilled = array_map(
            function (Glossary $glossary) use ($record) {
                $cloned = clone $glossary;
                $cloned->setItems(...$this->distillItems($glossary, $record));
                return $cloned;
            },
            $glossaries,
        );

        return array_filter(
            $distilled,
            fn (Glossary $glossary) => count($glossary->getItems()) >= $minTerminologyCount
        );
    }

    public function distillToString(Glossary $glossary, Record|string ...$recordsOrStrings): ?string
    {
        $summary = $glossary->getSummary();
        if (is_null($summary)) {
            return null;
        }

        $distilled = clone $glossary;
        $distilled->setItems(...$this->distillItems($glossary, ...$recordsOrStrings));

        return $this->glossaryToString($distilled);
    }

    public function glossaryToString(Glossary $glossary): ?string
    {
        $summary = $glossary->getSummary();
        if (is_null($summary)) {
            return null;
        }

        $items = $glossary->getItems();
        if (!empty($items)) {
            $summary.= "\n\n## " . implode("\n\n## ", $items);
        }

        return $summary;
    }

    /**
     * @param Glossary $glossary
     * @param Record|string ...$recordsOrStrings
     * @return GlossaryItem[]
     */
    public function distillItems(Glossary $glossary, Record|string ...$recordsOrStrings): array
    {
        $phrases = [];
        foreach ($recordsOrStrings as $recordOrString) {
            $string = $recordOrString instanceof Record ? $recordOrString->getPrimaryValue()->getStringContext() : $recordOrString;
            $words = $this->extractWords($string);
            $phrases = array_merge(
                $phrases,
                array_map(
                    fn(array $pair) => implode(' ', $pair),
                    $this->getConsecutiveGroups(3, ...$words)
                )
            );
        }
        $phrases = array_unique($phrases);

        $glossaryPhrases = [];
        foreach ($glossary->getItems() as $item) {
            if (!$item->isComplete()) {
                continue;
            }
            $glossaryPhrases[$item->primary->phrase] = $item;
        }

        /** @var GlossaryItem[] $items */
        $items = [];
        foreach ($glossaryPhrases as $glossaryPhrase => $glossaryItem) {
            foreach ($phrases as $phrase) {
                $similarity = $this->similarity($glossaryPhrase, $phrase);
                if ($similarity >= self::SIMILARITY_THRESHOLD) {
                    $cloned = clone $glossaryItem;
                    $cloned->similarity = max(($items[$cloned->primary->phrase]?->similarity ?? 0), $similarity);
                    $items[$cloned->primary->phrase] = $cloned;
                }
            }
        }

        return array_values($items);
    }

    private function extractWords(string $text): array
    {
        $words = [];

        // Шаг 1: Разбиваем строку по символам, не входящим в [A-Za-z0-9]
        $tokens = preg_split('/[^\p{L}]+/ui', $text, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($tokens as $token) {
            // Шаг 2: Выделяем сегменты, состоящие либо из цифр, либо из букв
            preg_match_all('/[\p{L}]+/ui', $token, $matches);
            foreach ($matches[0] as $segment) {
                // Шаг 3: Если сегмент состоит из букв, разбиваем его по границам CamelCase
                if (ctype_alpha($segment)) {
                    // Разбивка по переходу от строчной буквы к заглавной (например, "HelloWorld" -> ["Hello", "World"])
                    $subParts = preg_split('/(?<=[\p{Ll}])(?=[\p{Lu}])/', $segment);
                    foreach ($subParts as $subPart) {
                        $words[] = mb_strtolower($subPart);
                    }
                } else {
                    // Для цифр просто добавляем их (без изменений регистра)
                    $words[] = mb_strtolower($segment);
                }
            }
        }

        return $words;
    }

    private function getConsecutiveGroups(int $maxGroupSize, string ...$words): array
    {
        $groups = [];
        $count = count($words);

        // Генерируем группы размером от 1 до $maxGroupSize включительно
        for ($groupSize = 1; $groupSize <= $maxGroupSize; $groupSize++) {
            if ($count < $groupSize) {
                // Если оставшихся слов меньше, чем размер группы, прерываем цикл.
                break;
            }
            for ($i = 0; $i <= $count - $groupSize; $i++) {
                $groups[] = array_slice($words, $i, $groupSize);
            }
        }

        return $groups;
    }

    private function similarity(string $string_1, string $string_2): float
    {
        $string_1 = mb_strtolower($string_1);
        $string_2 = mb_strtolower($string_2);

        similar_text($string_1, $string_2, $similarityPercent);

        $distance = levenshtein($string_1, $string_2);
        $length = (mb_strlen($string_1) + mb_strlen($string_2)) / 2;
        $levenshteinPercent = min(100, max(0, 100 - ($distance / ($length / 100))));

        return ($similarityPercent + $levenshteinPercent) / 2 / 100;
    }


}