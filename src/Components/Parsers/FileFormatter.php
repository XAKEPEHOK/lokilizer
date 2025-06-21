<?php
/**
 * Created for lokilizer
 * Date: 2025-02-11 17:47
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Components\Parsers;

use XAKEPEHOK\Lokilizer\Components\Parsers\I18Next\I18NextFileFormatter;

enum FileFormatter: string
{

    case I18NEXT = 'i18next';

    public function factory(): FileFormatterInterface
    {
        return match ($this) {
            self::I18NEXT => new I18NextFileFormatter(),
        };
    }

}