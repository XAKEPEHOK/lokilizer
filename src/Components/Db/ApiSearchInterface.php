<?php
/**
 * Created for ploito-core
 * Date: 16.10.2023
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Components\Db;

use DiBify\DiBify\Repository\Components\Pagination;
use DiBify\DiBify\Repository\Components\Sorting;
use DiBify\DiBify\Repository\Storage\StorageData;

interface ApiSearchInterface
{

    /**
     * @param array $filters
     * @param Pagination|null $pagination
     * @param Sorting|null $sort
     * @param bool $withScope
     * @return StorageData[]
     */
    public function searchByAPI(array $filters, ?Pagination $pagination, ?Sorting $sort, bool $withScope = true): array;

    public function countByAPI(array $filters, bool $withScope = true): int;

}