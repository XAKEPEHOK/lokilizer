<?php
/**
 * Created for sr-app
 * Date: 2025-01-13 23:06
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Models\Localization;

use TypeError;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Models\Localization\Components\AbstractValue;
use XAKEPEHOK\Lokilizer\Models\Localization\Components\SimpleValue;

class SimpleRecord extends Record
{

    public function __construct(
        string         $flatKey,
        SimpleValue    ...$values,
    )
    {
        $this->values[] = new SimpleValue(Current::getProject()->getPrimaryLanguage(), '');
        parent::__construct($flatKey, ...$values);
    }

    public function setValue(AbstractValue $value): bool
    {
        if (!($value instanceof SimpleValue)) {
            throw new TypeError('Argument 1 passed to ' . __METHOD__ . ' must be of type SimpleValue');
        }
        return parent::setValue($value);
    }

    public static function getModelAlias(): string
    {
        return 'simpleString';
    }
}