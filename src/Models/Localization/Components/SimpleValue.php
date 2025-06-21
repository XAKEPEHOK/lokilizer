<?php
/**
 * Created for lokilizer
 * Date: 2025-01-21 17:18
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Models\Localization\Components;

use PrinsFrank\Standards\Language\LanguageAlpha2;
use RuntimeException;
use Stringable;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Models\Localization\Record;
use XAKEPEHOK\Lokilizer\Models\Localization\SimpleRecord;
use XAKEPEHOK\Lokilizer\Models\Project\Components\EOLFormat;

class SimpleValue extends AbstractValue implements Stringable
{

    public readonly string $value;

    public function __construct(
        LanguageAlpha2 $language,
        string $value,
    )
    {
        $this->value = EOLFormat::simplify($value);
        parent::__construct($language);
    }

    public function get(): string
    {
        return $this->value;
    }

    public function validate(Record|SimpleRecord $record): array
    {
        /** @var SimpleValue $primary */
        $primary = $record->getPrimaryValue();

        /** @var SimpleValue|null $secondary */
        $secondary = $record->getSecondaryValue();

        if (get_class($this) !== get_class($primary)) {
            throw new RuntimeException('Passed invalid arguments to ValueInterface::validate()');
        }

        $errors = [];

        if (empty(trim($this->value))) {
            $errors[] = "Value cannot be empty";
            return $errors;
        }

        if (mb_strlen($this->value) !== mb_strlen(trim($this->value))) {
            $errors[] = "Whitespaces are not trimmed";
        }

        if (str_contains($this->value, '  ')) {
            $errors[] = "String contains double whitespaces";
        }

        $exclude = ['email', 'api', 'ip', 'url', 'uri', 'id'];
        if ($this !== $primary && trim($this->value) === trim($primary->value) && !in_array(mb_strtolower(trim($primary)), $exclude)) {
            $errors[] = "String potentially may not translated from primary language";
        }

        if ($secondary && $this !== $primary && $this !== $secondary && trim($this->value) === trim($secondary->value) && !in_array(mb_strtolower(trim($secondary)), $exclude)) {
            $errors[] = "String potentially may not translated from secondary language";
        }

        if ($this !== $primary && trim($this->value) !== trim($primary->value) && mb_strtolower(trim($this->value)) === mb_strtolower(trim($primary))) {
            $errors[] = "String has different chars in lower/upper cases";
        }

        $placeholders = $primary->getPlaceholders();
        foreach ($placeholders as $placeholder) {
            if (!str_contains($this->value, $placeholder)) {
                $errors[] = "Placeholder '{$placeholder}' does not exist";
            }
        }

        $selfPlaceholders = Current::getProject()->getPlaceholdersFormat()->match($this->value);
        foreach ($selfPlaceholders as $placeholder) {
            if (!in_array($placeholder, $placeholders)) {
                $errors[] = "Redundant placeholder '{$placeholder}";
            }
        }

        $primaryEOL = EOLFormat::count($primary->value);
        $currentEOL = EOLFormat::count($this->value);
        if ($primaryEOL !== $currentEOL) {
            $errors[] = "EOL count mismatch. Primary: {$primaryEOL}; Current: {$currentEOL}";
        }

        $primaryColons = substr_count($primary->value, ":");
        $currentColons = substr_count($this->value, ":");
        if ($primaryColons !== $currentColons) {
            $errors[] = "Colons count mismatch. Primary: {$primaryColons}; Current: {$currentColons}";
        }

        return $errors;
    }

    public function getPlaceholders(): array
    {
        $format = Current::getProject()->getPlaceholdersFormat();
        return $format->match($this->value);
    }

    public function countEOL(): int
    {
        return EOLFormat::count($this->value);
    }

    public function getLength(): int
    {
        return mb_strlen($this->value);
    }

    public function isEmpty(): bool
    {
        return trim($this->value) === '';
    }

    public function isEquals(SimpleValue|AbstractValue|null $value): bool
    {
        return parent::isEquals($value) && $this->value === $value->value;
    }

    public function getStringContext(): string
    {
        return trim($this->value);
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function jsonSerialize(): string
    {
        return Current::getProject()->getEOLFormat()->convert($this->value);
    }

    public static function getEmpty(LanguageAlpha2 $language): static
    {
        return new static($language, '');
    }
}