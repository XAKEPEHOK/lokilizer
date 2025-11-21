<?php

namespace XAKEPEHOK\Lokilizer\Models\Localization\Components;

use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Models\Localization\Record;
use XAKEPEHOK\Lokilizer\Models\Localization\SimpleRecord;
use XAKEPEHOK\Lokilizer\Models\Localization\PluralRecord;

/**
 * Класс для унифицированной проверки количества специальных символов
 * в значениях переводов (SimpleValue и AbstractPluralValue).
 */
class ValueSymbolValidator
{
    /**
     * Символы, которые проверяются на количество.
     */
    private const SYMBOLS = [
        '?' => 'question mark',
        '!' => 'exclamation mark',
        '[' => 'opening square bracket',
        ']' => 'closing square bracket',
        '(' => 'opening round bracket',
        ')' => 'closing round bracket',
        '.' => 'dot',
        ';' => 'semicolon',
        '=' => 'equals sign',
        ':' => 'colon', // добавлено для полноты
    ];

    /**
     * Проверяет, включены ли проверки символов для текущего проекта.
     * @return bool
     */
    public static function isEnabled(): bool
    {
        // Получаем текущий проект и проверяем его настройку
        $project = Current::getProject();
        // Предположим, что у Project есть метод getSymbolValidationEnabled()
        // Если метода нет — его нужно добавить (см. Шаг 2)
        return $project->getSymbolValidationEnabled();
    }

    /**
     * Проверяет количество специальных символов в переданном значении.
     *
     * @param string $currentValue Значение, которое проверяется.
     * @param string $primaryValue Значение первичного языка для сравнения.
     * @param string $category Категория (для PluralRecord). Необязательно.
     * @return array Массив ошибок.
     */
    public static function validate(string $currentValue, string $primaryValue, ?string $category = null): array
    {
        // ✅ Если проверки выключены — возвращаем пустой массив
        if (!self::isEnabled()) {
            return [];
        }

        $errors = [];

        foreach (self::SYMBOLS as $symbol => $name) {
            $primaryCount = substr_count($primaryValue, $symbol);
            $currentCount = substr_count($currentValue, $symbol);

            // Проверка на совпадение количества
            if ($primaryCount !== $currentCount) {
                $symbolQuoted = "'{$symbol}'";
                $categoryText = $category ? " in category '{$category}'" : '';
                $errors[] = "Mismatch in the number of {$name} {$symbolQuoted}. Primary: {$primaryCount}; Current: {$currentCount}{$categoryText}";
            }
        }

        return $errors;
    }

    /**
     * Проверяет, есть ли символы в первичном и текущем значении (для PluralRecord).
     *
     * @param string $currentValue
     * @param string $primaryValue
     * @return array [primaryHasSymbol, currentHasSymbol]
     */
    public static function hasSymbol(string $currentValue, string $primaryValue, string $symbol): array
    {
        $primaryCount = substr_count($primaryValue, $symbol);
        $currentCount = substr_count($currentValue, $symbol);

        return [
            $primaryCount > 0,
            $currentCount > 0,
        ];
    }

    /**
     * Проверяет, есть ли символы в первичном и текущем значении (для PluralRecord).
     *
     * @param string $currentValue
     * @param string $primaryValue
     * @param string $symbol
     * @param string|null $category
     * @return array
     */
    public static function validateExistence(string $currentValue, string $primaryValue, string $symbol, ?string $category = null): array
    {
        // ✅ Если проверки выключены — возвращаем пустой массив
        if (!self::isEnabled()) {
            return [];
        }

        $errors = [];
        $name = self::SYMBOLS[$symbol] ?? 'unknown symbol';
        $symbolQuoted = "'{$symbol}'";
        $categoryText = $category ? " in category '{$category}'" : '';

        [$primaryHas, $currentHas] = self::hasSymbol($currentValue, $primaryValue, $symbol);

        if ($primaryHas !== $currentHas) {
            if ($primaryHas) {
                $errors[] = "{$name} {$symbolQuoted} exists in primary language value, but not exists in current value{$categoryText}";
            } else {
                $errors[] = "{$name} {$symbolQuoted} does not exists in primary language value, but exists in current value{$categoryText}";
            }
        }

        return $errors;
    }
}