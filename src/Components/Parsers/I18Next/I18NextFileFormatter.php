<?php
/**
 * Created for lokilizer
 * Date: 2025-02-11 21:42
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Components\Parsers\I18Next;

use Adbar\Dot;
use PrinsFrank\Standards\Language\LanguageAlpha2;
use XAKEPEHOK\Lokilizer\Components\Parsers\FileExportRepresentation;
use XAKEPEHOK\Lokilizer\Components\Parsers\FileFormatterInterface;
use XAKEPEHOK\Lokilizer\Models\Localization\Components\AbstractPluralValue;
use XAKEPEHOK\Lokilizer\Models\Localization\Components\CardinalPluralValue;
use XAKEPEHOK\Lokilizer\Models\Localization\Components\OrdinalPluralValue;
use XAKEPEHOK\Lokilizer\Models\Localization\Components\SimpleValue;
use XAKEPEHOK\Lokilizer\Models\Localization\Record;
use XAKEPEHOK\Lokilizer\Models\Localization\SimpleRecord;

class I18NextFileFormatter implements FileFormatterInterface
{

    /**
     * @param LanguageAlpha2 $language
     * @param array $flatArray
     * @return array<string, SimpleValue|CardinalPluralValue|OrdinalPluralValue>
     */
    public function parse(LanguageAlpha2 $language, array $flatArray): array
    {
        $categories = AbstractPluralValue::getCategoriesForLanguage($language);

        /**
         * Some categories in some languages may be skipped, e.g. "other" for Russian, thats reason for max = 2.
         * Also, some languages may contain only one category, e.g. "other" for Chinese.
         * This check needed for ignore single non-plural keys, that ends with "_one" without other categories
         */
        $minCount = min(count($categories), 2);

        $cardinal = [];
        $ordinal = [];
        $positions = [];

        $position = 0;
        foreach ($flatArray as $flatKey => $value) {
            $position++;
            foreach ($categories as $category) {
                $postfix = "_ordinal_{$category}";
                if (str_ends_with($flatKey, $postfix)) {
                    $key = substr($flatKey, 0, -strlen($postfix));
                    $ordinal[$key][$category] = $value;
                    $positions[$key] = $position;
                    continue;
                }

                $postfix = "_{$category}";
                if (str_ends_with($flatKey, $postfix)) {
                    $key = substr($flatKey, 0, -strlen($postfix));
                    $cardinal[$key][$category] = $value;
                    $positions[$key] = $position;
                    continue;
                }

                $positions[$flatKey] = $position;
            }
        }

        $cardinal = array_filter($cardinal, fn(array $items) => count($items) >= $minCount);
        $ordinal = array_filter($ordinal, fn(array $items) => count($items) >= $minCount);

        $result = [];
        $this->handlePlural($language, $cardinal, $result, $flatArray, CardinalPluralValue::class);
        $this->handlePlural($language, $ordinal, $result, $flatArray, OrdinalPluralValue::class);

        foreach ($flatArray as $flatKey => $value) {
            if (is_array($value) && empty($value)) {
                continue;
            }
            $result[$flatKey] = new SimpleValue($language, $value);
        }

        uksort($result, function ($a, $b) use ($positions) {
            return ($positions[$a]) <=> ($positions[$b] ?? 0);
        });

        return $result;
    }

    /**
     * @param LanguageAlpha2 $language
     * @param array<string, string> $options
     * @param Record ...$records
     * @return FileExportRepresentation
     */
    public function export(LanguageAlpha2 $language, array $options, Record ...$records): FileExportRepresentation
    {
        $result = new Dot();
        foreach ($records as $record) {

            if ($record instanceof SimpleRecord) {
                $result->set($record->getKey(), $record->getValue($language));
                continue;
            }

            $values = $record->getValue($language)->toArray();

            if ($record->getValue($language) instanceof CardinalPluralValue) {
                foreach ($values as $category => $value) {
                    $result->set("{$record->getKey()}_{$category}", $value);
                }
            }

            if ($record->getValue($language) instanceof OrdinalPluralValue) {
                foreach ($values as $category => $value) {
                    $result->set("{$record->getKey()}_ordinal_{$category}", $value);
                }
            }
        }

        $flags = JSON_UNESCAPED_UNICODE;
        if ($options['Pretty print'] === 'pretty') {
            $flags |= JSON_PRETTY_PRINT;
        }

        if ($options['Format'] === 'flat') {
            $content = json_encode($result->flatten(), $flags);
        } else {
            $content = json_encode($result->all(), $flags);
        }

        return new FileExportRepresentation(
            content: $content,
            filename: ($language->value) . '.json',
            size: strlen($content),
        );
    }

   private function handlePlural(LanguageAlpha2 $language, array $plurals, array &$result, array &$flatArray, string $pluralClass): void
    {
        foreach ($plurals as $key => $value) {
            //This if for better IDE find usage
            if ($pluralClass === CardinalPluralValue::class) {
                $result[$key] = new CardinalPluralValue(
                    language: $language,
                    zero: $value['zero'] ?? '',
                    one: $value['one'] ?? '',
                    two: $value['two'] ?? '',
                    few: $value['few'] ?? '',
                    many: $value['many'] ?? '',
                    other: $value['other'] ?? '',
                );
            } else if ($pluralClass === OrdinalPluralValue::class) {
                $result[$key] = new OrdinalPluralValue(
                    language: $language,
                    zero: $value['zero'] ?? '',
                    one: $value['one'] ?? '',
                    two: $value['two'] ?? '',
                    few: $value['few'] ?? '',
                    many: $value['many'] ?? '',
                    other: $value['other'] ?? '',
                );
            }

            $categories = AbstractPluralValue::getCategories();
            foreach ($categories as $category) {
                $flatKey = "{$key}_$category";
                unset($flatArray[$flatKey]);
            }
        }
    }

    public static function exportOptions(): array
    {
        return [
            'Format' => [
                'structured' => 'Structured JSON',
                'flat' => 'Flat JSON',
            ],
            'Pretty print' => [
                'pretty' => 'Yes',
                'minified' => 'No (minified)',
            ],
        ];
    }
}