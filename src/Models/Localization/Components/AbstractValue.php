<?php
/**
 * Created for lokilizer
 * Date: 2025-01-21 17:18
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Models\Localization\Components;

use JsonSerializable;
use LogicException;
use PrinsFrank\Standards\Language\LanguageAlpha2;
use TypeError;
use XAKEPEHOK\Lokilizer\Models\Localization\Record;

abstract class AbstractValue implements JsonSerializable
{

    protected LanguageAlpha2 $language;

    protected int $warnings = 0;
    public bool $verified = false;

    protected ?self $suggested = null;

    public function __construct(LanguageAlpha2 $language)
    {
        $this->language = $language;
    }

    public function getLanguage(): LanguageAlpha2
    {
        return $this->language;
    }

    public function getSuggested(): ?self
    {
        return $this->suggested;
    }

    public function setSuggested(?self $suggested): bool
    {
        if ($suggested && get_class($suggested) !== $this::class) {
            throw new TypeError('Suggested value type mismatch');
        }

        if (is_null($suggested) && is_null($this->suggested)) {
            return false;
        }

        if ($suggested?->isEquals($this->suggested) || $this->suggested?->isEquals($suggested)) {
            return false;
        }

        if ($suggested && $suggested->isEmpty()) {
            $this->suggested = null;
            return true;
        }

        $this->suggested = $suggested;
        return true;
    }

    abstract public function validate(Record $record): array;

    public function getWarnings(): int
    {
        return $this->warnings;
    }

    public function setWarnings(int|array $warnings): void
    {
        $this->warnings = is_int($warnings) ? $warnings : count($warnings);
        if ($this->warnings > 0) {
            $this->verified = false;
        }
    }


    abstract public function getPlaceholders(): array;

    abstract public function countEOL(): int;

    abstract public function getLength(): int;

    abstract public function getStringContext(): string;

    abstract public static function getEmpty(LanguageAlpha2 $language): static;

    abstract public function isEmpty(): bool;

    public function isEquals(?AbstractValue $value): bool
    {
        if (is_null($value)) {
            return false;
        }

        if (get_class($value) !== get_class($this)) {
            throw new TypeError("Value is not equal to " . get_class($this));
        }

        if ($value->getLanguage() !== $this->getLanguage()) {
            throw new LogicException('Value with different languages cannot be compared');
        }

        return true;
    }

    /**
     * @param LanguageAlpha2|AbstractValue $languageOrEmpty
     * @param string|array $value
     * @return SimpleValue|AbstractPluralValue
     */
    public static function parse(LanguageAlpha2|AbstractValue $languageOrEmpty, string|array $value): SimpleValue|AbstractPluralValue
    {
        $language = $languageOrEmpty instanceof  LanguageAlpha2 ? $languageOrEmpty : $languageOrEmpty->getLanguage();

        if (is_array($value)) {
            if ($languageOrEmpty instanceof OrdinalPluralValue) {
                return new OrdinalPluralValue(
                    $language,
                    $value['zero'] ?? '',
                    $value['one'] ?? '',
                    $value['two'] ?? '',
                    $value['few'] ?? '',
                    $value['many'] ?? '',
                    $value['other'] ?? '',
                );
            } else {
                return new CardinalPluralValue(
                    $language,
                    $value['zero'] ?? '',
                    $value['one'] ?? '',
                    $value['two'] ?? '',
                    $value['few'] ?? '',
                    $value['many'] ?? '',
                    $value['other'] ?? '',
                );
            }
        }

        return new SimpleValue($language, $value);
    }

}