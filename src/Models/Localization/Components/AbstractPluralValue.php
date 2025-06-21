<?php
/**
 * Created for sr-app
 * Date: 2025-01-14 22:56
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Models\Localization\Components;

use MessageFormatter;
use PrinsFrank\Standards\Language\LanguageAlpha2;
use RuntimeException;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Models\Localization\PluralRecord;
use XAKEPEHOK\Lokilizer\Models\Localization\Record;
use XAKEPEHOK\Lokilizer\Models\Project\Components\EOLFormat;

abstract class AbstractPluralValue extends AbstractValue
{

    /**
     * Plural rule type: cardinal (eg 1, 2, 3, ...).
     *
     * @var string
     */
    const TYPE_CARDINAL = 'cardinal';

    /**
     * Plural rule type: ordinal (eg 1st, 2nd, 3rd, ...).
     *
     * @var string
     */
    const TYPE_ORDINAL = 'ordinal';

    protected string $zero;
    protected string $one;
    protected string $two;
    protected string $few;
    protected string $many;
    protected string $other;
    private static array $examples = [];

    public function __construct(
        LanguageAlpha2 $language,
        string $zero,
        string $one,
        string $two,
        string $few,
        string $many,
        string $other,
    )
    {
        $this->zero = EOLFormat::simplify($zero);
        $this->one = EOLFormat::simplify($one);
        $this->two = EOLFormat::simplify($two);
        $this->few = EOLFormat::simplify($few);
        $this->many = EOLFormat::simplify($many);
        $this->other = EOLFormat::simplify($other);
        parent::__construct($language);
    }

    public function getCategoryValue(string $category): string
    {
        return $this->$category;
    }

    public function validate(Record|PluralRecord $record): array
    {
        /** @var AbstractPluralValue $primary */
        $primary = $record->getPrimaryValue();

        /** @var AbstractPluralValue|null $secondary */
        $secondary = $record->getSecondaryValue();

        if (get_class($this) !== get_class($primary)) {
            throw new RuntimeException('Passed invalid arguments to ValueInterface::validate()');
        }

        $errors = [];
        $shouldBeFilled = self::getCategoriesForLanguage($this->getLanguage());
        $shouldBeEmpty = array_diff(self::getCategories(), $shouldBeFilled);

        foreach ($shouldBeFilled as $category) {
            $value = trim($this->$category);
            if (strlen($value) == 0) {
                $errors[] = "Plural category '{$category}' should be filled";
            }

            if (mb_strlen($this->$category) !== mb_strlen(trim($this->$category))) {
                $errors[] = "Whitespaces are not trimmed in category '{$category}'";
            }

            if (str_contains($this->$category, '  ')) {
                $errors[] = "String contains double whitespaces";
            }
        }

        foreach ($shouldBeEmpty as $category) {
            $value = $this->$category;
            if (strlen($value) > 0) {
                $errors[] = "Plural category '{$category}' should be empty";
            }
        }

        $format = Current::getProject()->getPlaceholdersFormat();
        $placeholders = $primary->getPlaceholders();
        $placeholders['count'] = $format->wrap('count');

        $duplicates = [];
        foreach ($shouldBeFilled as $category) {
            $value = $this->$category;
            $duplicates[trim($value)] = 1;

            foreach ($placeholders as $placeholder) {
                if (!str_contains($value, $placeholder)) {
                    $errors[] = "Placeholder '{$placeholder}' does not exist in category '{$category}'";
                }
            }

            $selfPlaceholders = $format->match($value);
            foreach ($selfPlaceholders as $placeholder) {
                if (!in_array($placeholder, $placeholders)) {
                    $errors[] = "Redundant placeholder '{$placeholder} in category '{$category}'";
                }
            }
        }

        if (count($shouldBeFilled) !== count($duplicates)) {
            $errors[] = "Same text in different categories";
        }

        $categoryEOL = [];
        $primaryHasEOL = false;
        $currentHasEOL = false;

        $categoryColon = [];
        $primaryHasColon = false;
        $currentHasColon = false;

        foreach ($shouldBeFilled as $category) {
            $primaryEOL = EOLFormat::count($primary->$category);
            $currentEOL = EOLFormat::count($this->$category);
            $categoryEOL[$category] = $currentEOL;
            $primaryHasEOL = $primaryHasEOL || $primaryEOL > 0;
            $currentHasEOL = $currentHasEOL || $currentEOL > 0;

            $categoryColon[$category] = substr_count($this->$category, ':');
            $primaryHasColon = $primaryHasColon || substr_count($primary->$category, ':') > 0;
            $currentHasColon = $currentHasColon || substr_count($this->$category, ':') > 0;
        }

        if ($primaryHasEOL !== $currentHasEOL) {
            if ($primaryHasEOL) {
                $errors[] = "EOL exists in primary language value, but not exists in current value";
            } else {
                $errors[] = "EOL does not exists in primary language value, but exists in current value";
            }
        }

        if ($primaryHasColon !== $currentHasColon) {
            if ($primaryHasColon) {
                $errors[] = "Colon exists in primary language value, but not exists in current value";
            } else {
                $errors[] = "Colon does not exists in primary language value, but exists in current value";
            }
        }

        $minEol = min($categoryEOL);
        $maxEol = max($categoryEOL);
        if ($minEol !== $maxEol) {
            $errors[] = "Different categories contain different count of EOL";
        }

        $minColon = min($categoryColon);
        $maxColon = max($categoryColon);
        if ($minColon !== $maxColon) {
            $errors[] = "Different categories contain different count of colons";
        }

        return array_values($errors);
    }

    public function getPlaceholders(): array
    {
        $format = Current::getProject()->getPlaceholdersFormat();
        return $format->match(implode(' ', $this->toArray()));
    }

    public function countEOL(): int
    {
        return array_sum(array_map(
            fn(string $value) => EOLFormat::count($value),
            $this->toArray()
        ));
    }

    public function getLength(): int
    {
        return intval(array_avg(array_map(
            fn(string $val) => mb_strlen($val),
            array_values($this->toArray())
        )));
    }

    public function isEmpty(): bool
    {
        $merged = implode('', array_map(
            fn(string $val) => trim($val),
            $this->toArray()
        ));

        return $merged === '';
    }

    public function isEquals(AbstractPluralValue|AbstractValue|null $value): bool
    {
        if (!parent::isEquals($value)) {
            return false;
        }

        return $value->toArray() === $this->toArray();
    }

    public function toArray(): array
    {
        $array = [
            'zero' => $this->zero,
            'one' => $this->one,
            'two' => $this->two,
            'few' => $this->few,
            'many' => $this->many,
            'other' => $this->other,
        ];

        $categories = self::getCategoriesForLanguage($this->language);
        return array_filter(
            $array,
            fn (string $value, string $key) => in_array($key, $categories, true),
            ARRAY_FILTER_USE_BOTH
        );
    }

    public function getStringContext(): string
    {
        $array = $this->toArray();
        $array = array_combine(
            $array,
            array_map('mb_strlen', $array)
        );
        arsort($array);
        return trim(array_key_first($array));
    }

    abstract public static function getType(): string;

    public function jsonSerialize(): array
    {
        $eol = Current::getProject()->getEOLFormat();
        return array_map(
            fn(string $value) => $eol->convert($value),
            $this->toArray()
        );
    }

    public static function getCategories(): array
    {
        return [
            'zero',
            'one',
            'two',
            'few',
            'many',
            'other',
        ];
    }

    public static function getEmpty(LanguageAlpha2 $language): static
    {
        return new static($language, '', '', '', '', '', '');
    }

    public static function getCategoriesForLanguage(LanguageAlpha2 $language, $type = self::TYPE_CARDINAL): array
    {
        return array_keys(self::pluralsForLanguage($language, $type));
    }

    public static function getCategoryExamples(LanguageAlpha2 $language, string $category, $type = self::TYPE_CARDINAL): array
    {
        $plurals = self::pluralsForLanguage($language, $type);
        return $plurals[$category] ?? ['Unknown'];
    }

    private static function pluralsForLanguage(LanguageAlpha2 $language, $type = self::TYPE_CARDINAL, int $maxCount = 15): array
    {
        if (!isset(static::$examples[$language->value][$type])) {
            $range = [
                ...range(0, 100),
                ...array_map(fn(int $value) => $value / 10, range(0, 100)),
                ...array_map(fn(int $value) => $value * 10, range(0, 100)),
                ...array_map(fn(int $value) => $value * 100, range(0, 100)),
            ];

            // Возможные ключевые слова согласно CLDR
            $candidates = ['zero', 'one', 'two', 'few', 'many', 'other'];

            // Формируем шаблон для MessageFormatter.
            // В шаблоне перечисляем все кандидаты, но выбор происходит по правилам локали.
            // Например, шаблон будет выглядеть так:
            // {0, plural, zero{zero} one{one} two{two} few{few} many{many} other{other}}
            $patternParts = [];
            foreach ($candidates as $keyword) {
                $patternParts[] = $keyword . '{' . $keyword . '}';
            }

            // Выбираем тип правил: cardinal или ordinal
            $pluralOrOrdinal = $type === self::TYPE_CARDINAL ? 'plural' : 'selectordinal';
            $pattern = '{0, ' . $pluralOrOrdinal . ', ' . implode(' ', $patternParts) . '}';

            $formatter = new MessageFormatter($language->value, $pattern);
            $found = [];

            foreach ($range as $i) {
                $result = $formatter->format([$i]);
                if ($result !== false && in_array($result, $candidates, true)) {
                    $found[$result][] = $i;
                }
            }

            $result = [];
            foreach ($candidates as $keyword) {
                if (isset($found[$keyword])) {
                    $result[$keyword] = $found[$keyword];
                }
            }

            $result = array_map(fn(array $values) => array_slice(array_unique($values), 0, $maxCount), $result);
            static::$examples[$language->value][$type] = $result;
        }

        return static::$examples[$language->value][$type];
    }
}