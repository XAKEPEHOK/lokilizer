<?php
/**
 * Created for ploito-core
 * Date: 16.10.2023
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Components\Db\Storage\Mongo;

use Adbar\Dot;
use DiBify\DiBify\Repository\Components\Pagination;
use DiBify\DiBify\Repository\Components\Sorting;
use DiBify\DiBify\Repository\Storage\StorageData;
use MongoDB\Collection;
use XAKEPEHOK\Lokilizer\Components\Db\ApiFilter\ApiFilterInterface;

trait MongoApiSearchTrait
{

    public function searchByAPI(array $filters, ?Pagination $pagination, ?Sorting $sort, bool $withScope = true): array
    {
        $options = [];
        if ($pagination) {
            $options['limit'] = $pagination->getSize();
            $options['skip'] = max(0, $pagination->getSize() * ($pagination->getNumber() - 1));
        }

        if ($sort) {
            $options['sort'] = $this->apiSort($sort, $withScope);
            $options['collation'] = $this->apiSortCollationOptions($sort);
        }

        $filter = $this->filterToArray($filters, $withScope);

        $this->log('find', $filter, $options);
        $cursor = $this->getCollection()->find($filter, $options);
        $cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);

        $documents = [];
        foreach ($cursor as $document) {
            $documents[] = $this->doc2data($document);
        }

        return $documents;
    }

    public function countByAPI(array $filters, bool $withScope = true): int
    {
        return $this->getCollection()->countDocuments($this->filterToArray($filters, $withScope));
    }

    abstract protected function apiFilter(Dot $query): MongoApiFilter|ApiFilterInterface;

    protected function apiSort(Sorting $sort, bool $withScope = true): array
    {
        $direction = $sort->getDirection() === Sorting::SORT_ASC ? 1 : -1;
        $field = $sort->getField();
        if ($field === 'id') {
            $field = $withScope && $this->scope() ? '_id.id' : '_id';
        }
        return [$field => $direction];
    }

    protected function apiSortCollationOptions(Sorting $sort): array
    {
        return [
            'locale' => 'en_US',
            'numericOrdering' => in_array($sort->getField(), $this->apiSortNumberFields()),
        ];
    }

    protected function apiSortNumberFields(): array
    {
        return [
            'id',
        ];
    }

    abstract protected function doc2data($document): StorageData;

    abstract public function scope(): ?string;

    abstract public function getCollection(): Collection;

    abstract protected function log(string $type, array $filter = [], array $options = []);

    protected function filterToArray(array $filters, bool $withScope = true): array
    {
        $include = $this->apiFilter(new Dot($filters));

        if (!empty($include->get())) {
            $filter = ['$and' => $include->get()];
        } else {
            $filter = [];
        }

        if ($withScope && $this->scope()) {
            $filter = array_merge(["_id.{$this->scopeKey()}" => $this->scope()], $filter);
        }

        return $filter;
    }

}