<?php
/**
 * Created for ploito-core
 * Date: 16.10.2023
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Components\Db\Repo;

use DiBify\DiBify\Replicator\ReplicatorInterface;
use DiBify\DiBify\Repository\Components\Pagination;
use DiBify\DiBify\Repository\Components\Sorting;
use DiBify\DiBify\Repository\Storage\StorageInterface;
use XAKEPEHOK\Lokilizer\Components\Db\ApiSearchInterface;

trait RepoApiSearchTrait
{

    public function searchByAPI(array $filters, ?Pagination $pagination, ?Sorting $sort, bool $withScope = true): array
    {
        /** @var StorageInterface|ApiSearchInterface $storage */
        $storage = $this->getReplicator()->getPrimary();
        return $this->populateMany($storage->searchByAPI($filters, $pagination, $sort, $withScope));
    }

    public function countByAPI(array $filters, bool $withScope = true): int
    {
        /** @var StorageInterface|ApiSearchInterface $storage */
        $storage = $this->getReplicator()->getPrimary();
        return $storage->countByAPI($filters, $withScope);
    }

    abstract public function getReplicator(): ReplicatorInterface;

    abstract protected function populateMany(array $array): array;

}