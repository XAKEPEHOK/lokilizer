<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 15.05.2019 12:10
 */

namespace XAKEPEHOK\Lokilizer\Components\Db\ApiFilter\Range;


class StringRange implements RangeInterface
{

    public function __construct(
        private readonly ?string $gte,
        private readonly ?string $lte,
    )
    {
    }

    public function gte(): ?string
    {
        return $this->gte;
    }

    public function lte(): ?string
    {
        return $this->lte;
    }

}