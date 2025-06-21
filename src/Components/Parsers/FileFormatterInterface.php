<?php
/**
 * Created for lokilizer
 * Date: 2025-02-11 21:23
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Components\Parsers;

use PrinsFrank\Standards\Language\LanguageAlpha2;
use XAKEPEHOK\Lokilizer\Models\Localization\Components\CardinalPluralValue;
use XAKEPEHOK\Lokilizer\Models\Localization\Components\OrdinalPluralValue;
use XAKEPEHOK\Lokilizer\Models\Localization\Components\SimpleValue;
use XAKEPEHOK\Lokilizer\Models\Localization\Record;

interface FileFormatterInterface
{


    /**
     * @param LanguageAlpha2 $language
     * @param array $flatArray
     * @return array<string, SimpleValue|CardinalPluralValue|OrdinalPluralValue>
     */
    public function parse(LanguageAlpha2 $language, array $flatArray): array;

    public function export(LanguageAlpha2 $language, array $options, Record ...$records): FileExportRepresentation;

    /**
     * @return array<string, array<string, string>>
     */
    public static function exportOptions(): array;

}