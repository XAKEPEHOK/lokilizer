<?php
/**
 * Created for lokilizer
 * Date: 2025-02-06 01:01
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Components\Db\Mappers;

use DiBify\DiBify\Mappers\EnumMapper;
use DiBify\DiBify\Mappers\SharedMapperTrait;
use DiBify\DiBify\Mappers\StringMapper;
use PrinsFrank\Standards\Language\LanguageAlpha2;

class LanguageMapper extends EnumMapper
{

    use SharedMapperTrait;

    private function __construct()
    {
        parent::__construct(LanguageAlpha2::class, StringMapper::getInstance());
    }

}