<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 15.05.2019 12:09
 */

namespace XAKEPEHOK\Lokilizer\Components\Db\ApiFilter\Range;


interface RangeInterface
{

    public function gte(): mixed;

    public function lte(): mixed;

}