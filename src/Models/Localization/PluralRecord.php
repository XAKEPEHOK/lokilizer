<?php
/**
 * Created for sr-app
 * Date: 2025-01-13 23:06
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Models\Localization;

use TypeError;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Models\Localization\Components\AbstractPluralValue;
use XAKEPEHOK\Lokilizer\Models\Localization\Components\AbstractValue;

class PluralRecord extends Record
{

    public function __construct(
        string              $flatKey,
        AbstractPluralValue ...$values,
    )
    {
        $this->values[] = $values[0]::getEmpty(Current::getProject()->getPrimaryLanguage());
        parent::__construct($flatKey, ...$values);
    }

    public function setValue(AbstractValue $value): bool
    {
        if (!($value instanceof AbstractPluralValue)) {
            throw new TypeError('Argument 1 passed to ' . __METHOD__ . ' must be of type AbstractPluralValue');
        }
        return parent::setValue($value);
    }


    public function getType(): string
    {
        return $this->getPrimaryValue()::getType();
    }

    public static function getModelAlias(): string
    {
        return 'pluralString';
    }
}