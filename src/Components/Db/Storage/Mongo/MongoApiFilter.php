<?php
/**
 * Created for ploito-core
 * Date: 16.10.2023
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Components\Db\Storage\Mongo;

use DiBify\DiBify\Helpers\IdHelper;
use MongoDB\BSON\Regex;
use MongoDB\BSON\UTCDateTime;
use XAKEPEHOK\Lokilizer\Components\Db\ApiFilter\ApiFilterInterface;
use XAKEPEHOK\Lokilizer\Components\Db\ApiFilter\Range\DatetimeRange;
use XAKEPEHOK\Lokilizer\Components\Db\ApiFilter\Range\RangeInterface;
use XAKEPEHOK\Lokilizer\Components\Db\ApiFilter\Range\StringRange;

class MongoApiFilter implements ApiFilterInterface
{

    private array $filters = [];

    public function ids(string $field, array $ids): ApiFilterInterface
    {
        if (!empty($ids)) {
            $this->filters[][$field] = [
                '$in' => IdHelper::scalarizeMany(...$ids),
            ];
        }

        return $this;
    }

    public function in(string $field, array $values): ApiFilterInterface
    {
        if (!empty($values)) {
            $this->filters[][$field] = [
                '$in' => $values,
            ];
        }

        return $this;
    }

    public function equals(string $field, $value): ApiFilterInterface
    {
        if ($value instanceof ApiFilterInterface) {
            $elemMatch = [];
            foreach ($value->get() as $filter) {
                $elemMatch = array_merge($elemMatch, $filter);
            }
            $this->filters[][$field] = [
                '$elemMatch' => $elemMatch,
            ];
        } else {
            $this->filters[][$field] = $value;
        }
        return $this;
    }

    public function like(string $field, $value): ApiFilterInterface
    {
        $this->filters[][$field] = [
            '$regex' => new Regex(preg_quote(trim($value))),
            '$options' => 'i'
        ];
        return $this;
    }

    public function range(string $field, RangeInterface $range): ApiFilterInterface
    {
        $filter = array_filter([
            '$gte' => $range->gte(),
            '$lte' => $range->lte(),
        ], fn ($value) => $value !== null && ($value !== '' || $range instanceof StringRange));

        if ($range instanceof DatetimeRange) {
            $filter = array_map(function ($value) {
                return new UTCDateTime($value * 1000);
            }, $filter);
        }

        if (!empty($filter)) {
            $this->filters[][$field] = $filter;
        }

        return $this;
    }

    public function empty(string $field, bool $includeEmptyString, bool $includeEmptyArray): ApiFilterInterface
    {
        if ($includeEmptyString || $includeEmptyArray) {

            $filters = [[$field => null]];

            if ($includeEmptyString) {
                $filters[] = [$field => ''];
            }

            if ($includeEmptyArray) {
                $filters[] = [$field => [
                    '$exists' => true,
                    '$eq' => [],
                ]];
            }

            $this->filters[]['$or'] = $filters;
        } else {
            $this->filters[][$field] = null;
        }

        return $this;
    }

    public function exists(string $field): ApiFilterInterface
    {
        $this->filters[] = [$field => [
            '$exists' => true,
        ]];
        return $this;
    }

    public function or(ApiFilterInterface $filter): ApiFilterInterface
    {
        if (!empty($filter->get())) {
            $this->filters[]['$or'] = $filter->get();
        }
        return $this;
    }

    public function and(ApiFilterInterface $filter): ApiFilterInterface
    {
        if (!empty($filter->get())) {
            $this->filters[]['$and'] = $filter->get();
        }
        return $this;
    }

    public function not(ApiFilterInterface $filter): ApiFilterInterface
    {
        if (!empty($filter->get())) {
            $this->filters[]['$nor'] = $filter->get();
        }
        return $this;
    }

    public function raw(array $filter): ApiFilterInterface
    {
        $this->filters[] = $filter;
        return $this;
    }

    public function get(): array
    {
        return $this->filters;
    }

    public function reset(): void
    {
        $this->filters = [];
    }
}