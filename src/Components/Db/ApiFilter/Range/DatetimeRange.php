<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 15.05.2019 12:11
 */

namespace XAKEPEHOK\Lokilizer\Components\Db\ApiFilter\Range;

use DateTimeInterface;

class DatetimeRange implements RangeInterface
{
    public function __construct(
        private readonly ?DateTimeInterface $gte,
        private readonly ?DateTimeInterface $lte,
    )
    {
    }

    public function gte(): ?string
    {
        return $this->format($this->gte);
    }

    public function lte(): ?string
    {
        return $this->format($this->lte);
    }

    private function format(?DateTimeInterface $datetime): ?string
    {
        return $datetime === null ? null : (int)$datetime->format('U');
    }

}