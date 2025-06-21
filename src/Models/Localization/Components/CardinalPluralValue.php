<?php
/**
 * Created for sr-app
 * Date: 2025-01-14 22:56
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Models\Localization\Components;


class CardinalPluralValue extends AbstractPluralValue
{

    /**
     * Cardinal (eg 1, 2, 3, ...)
     * @return string
     */
    public static function getType(): string
    {
        return self::TYPE_CARDINAL;
    }
}