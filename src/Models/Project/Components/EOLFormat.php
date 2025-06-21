<?php
/**
 * Created for lokilizer
 * Date: 2025-01-23 17:10
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Models\Project\Components;

enum EOLFormat: string
{

    case RN = "\r\n";
    case R = "\r";
    case N = "\n";

    public function isInString(string $string): bool
    {
        return str_contains($string, $this->value);
    }

    /**
     * @return self[]
     */
    public function getOthers(): array
    {
        return array_filter(self::cases(), fn (self $value) => $value !== $this);
    }

    public function convert(string $value): string
    {
        return str_ireplace(self::default()->value, $this->value, $value);
    }

    public static function simplify(string $string): string
    {
        foreach (EOLFormat::cases() as $format) {
            $string = str_replace($format->value, self::default()->value, $string);
        }
        return $string;
    }

    public static function count(string $string): int
    {
        $string = self::simplify($string);
        return count(explode(self::default()->value, $string)) - 1;
    }

    public static function default(): EOLFormat
    {
        return self::N;
    }

}
