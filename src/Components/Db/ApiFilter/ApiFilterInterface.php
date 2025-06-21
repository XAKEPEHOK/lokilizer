<?php
/**
 * Created for lv-app
 * Date: 23.04.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Components\Db\ApiFilter;


use XAKEPEHOK\Lokilizer\Components\Db\ApiFilter\Range\RangeInterface;

interface ApiFilterInterface
{

    public function ids(string $field, array $ids): self;

    public function equals(string $field, $value): self;

    public function like(string $field, $value): self;

    public function in(string $field, array $values): self;

    public function range(string $field, RangeInterface $range): self;

    public function empty(string $field, bool $includeEmptyString, bool $includeEmptyArray): self;

    public function exists(string $field): self;

    public function or(ApiFilterInterface $filter): self;

    public function and(ApiFilterInterface $filter): self;

    public function not(ApiFilterInterface $filter): self;

    public function get();

    public function reset(): void;

}