<?php
/**
 * Created for lokilizer
 * Date: 2025-02-10 15:33
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Models\Glossary\Db\Storage;

use Adbar\Dot;
use DiBify\DiBify\Repository\Storage\StorageData;
use MongoDB\Collection;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Components\Db\ApiFilter\ApiFilterInterface;
use XAKEPEHOK\Lokilizer\Components\Db\ApiSearchInterface;
use XAKEPEHOK\Lokilizer\Components\Db\Storage\Mongo\MongoApiFilter;
use XAKEPEHOK\Lokilizer\Components\Db\Storage\Mongo\MongoApiSearchTrait;
use XAKEPEHOK\Lokilizer\Components\Db\Storage\Mongo\MongoStorage;

class GlossaryMongoStorage extends MongoStorage implements ApiSearchInterface
{

    use MongoApiSearchTrait;

    public function findPrimary(): ?StorageData
    {
        return $this->findOneByFilter(['_id.id' => 'primary']);
    }

    public function findByKeyPrefix(string $keyPrefix): ?StorageData
    {
        return $this->findOneByFilter(['keyPrefix' => $keyPrefix]);
    }

    /**
     * @param string[] $prefixes
     * @return StorageData[]
     */
    public function findByKeyPrefixes(string ...$prefixes): array
    {
        return $this->findByFilter(['keyPrefix' => [
            '$in' => $prefixes
        ]]);
    }

    public function findAll(): array
    {
        return $this->findByFilter([]);
    }

    protected function apiFilter(Dot $query): MongoApiFilter|ApiFilterInterface
    {
        $filter = new MongoApiFilter();

        $notPrimaryFilter = new MongoApiFilter();
        $notPrimaryFilter->equals('_id.id', 'primary');
        $filter->not($notPrimaryFilter);

        return $filter;
    }

    protected function pools(): array
    {
        return [
            'llmCost'
        ];
    }

    public function scope(): ?string
    {
        return Current::getProject()->id()->get();
    }

    public function getCollection(): Collection
    {
        return $this->database->selectCollection('glossary');
    }
}