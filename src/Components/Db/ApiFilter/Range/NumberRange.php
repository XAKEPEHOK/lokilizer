<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 15.05.2019 12:10
 */

namespace XAKEPEHOK\Lokilizer\Components\Db\ApiFilter\Range;


class NumberRange implements RangeInterface
{

    public function __construct(
        private readonly ?float $gte,
        private readonly ?float $lte,
    )
    {
    }

    public function gte(): ?float
    {
        return $this->gte;
    }

    public function lte(): ?float
    {
        return $this->lte;
    }

}