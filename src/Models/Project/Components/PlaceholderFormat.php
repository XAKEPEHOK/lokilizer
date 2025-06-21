<?php
/**
 * Created for lokilizer
 * Date: 2025-01-23 17:10
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Models\Project\Components;

enum PlaceholderFormat: string
{

    case JS = 'js';
    case SINGLE_CURVE_BRACKETS = 'single_curve_brackets';
    case DOUBLE_CURVE_BRACKETS = 'double_curve_brackets';

    public function match(string $string): array
    {
        $regexp = match($this) {
            self::JS => '\$\{([\w_]+)\}',
            self::SINGLE_CURVE_BRACKETS => '\{([\w_]+)\}',
            self::DOUBLE_CURVE_BRACKETS => '\{\{([\w_]+)\}\}',
        };
        $matches = [];
        preg_match_all("/($regexp)/ui", $string, $matches, PREG_SET_ORDER);
        return array_combine(
            array_column($matches, 2),
            array_column($matches, 1),
        );
    }

    public function wrap(string $string): string
    {
        return match($this) {
            self::JS => '${' . $string .'}',
            self::SINGLE_CURVE_BRACKETS => '{' . $string . '}',
            self::DOUBLE_CURVE_BRACKETS => '{{' . $string . '}}',
        };
    }

}
