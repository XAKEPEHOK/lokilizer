<?php
/**
 * Created for lokilizer
 * Date: 2025-02-11 18:21
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Models\Localization\Components;


class OrdinalPluralValue extends AbstractPluralValue
{

    /**
     * Ordinal (eg 1st, 2nd, 3rd, ...)
     * @return string
     */
    public static function getType(): string
    {
        return self::TYPE_ORDINAL;
    }

}