<?php
/**
 * Created for lokilizer
 * Date: 2025-03-05 13:48
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Apps\Console\Handle\Tasks;

use PrinsFrank\Standards\Language\LanguageAlpha2;
use XAKEPEHOK\Lokilizer\Models\Localization\Record;

trait RecordFilterTrait
{

    public function shouldSkipRecord(Record $record, LanguageAlpha2 $language, string $key, string $value): bool
    {
        if (!empty(trim($key))) {
            if (!str_contains($record->getKey(), $key)) {
                return true;
            }
        }

        if (!empty(trim($value))) {
            if (!str_contains(
                mb_strtolower($record->getValue($language)?->getStringContext() ?? ''),
                mb_strtolower($value)
            )) {
                return true;
            }
        }

        return false;
    }

}