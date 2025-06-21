<?php

namespace XAKEPEHOK\Lokilizer\Models\LLM\Db\Storage;

use Adbar\Dot;
use DiBify\DiBify\Repository\Storage\StorageData;
use MongoDB\Collection;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Components\Db\ApiFilter\ApiFilterInterface;
use XAKEPEHOK\Lokilizer\Components\Db\ApiFilter\FilterWrapper;
use XAKEPEHOK\Lokilizer\Components\Db\ApiSearchInterface;
use XAKEPEHOK\Lokilizer\Components\Db\Storage\Mongo\MongoApiFilter;
use XAKEPEHOK\Lokilizer\Components\Db\Storage\Mongo\MongoApiSearchTrait;
use XAKEPEHOK\Lokilizer\Components\Db\Storage\Mongo\MongoStorage;

class LLMEndpointMongoStorage extends MongoStorage implements ApiSearchInterface
{

    use MongoApiSearchTrait;

    public function findAll(): array
    {
        return $this->findByFilter([]);
    }

    public function findDefault(): ?StorageData
    {
        return $this->findOneByFilter([]);
    }

    public function getCollection(): Collection
    {
        return $this->database->selectCollection('llm');
    }

    public function scope(): ?string
    {
        return Current::getProject()->id()->get();
    }

    protected function pools(): array
    {
        return [
            'cost'
        ];
    }

    protected function apiFilter(Dot $query): MongoApiFilter|ApiFilterInterface
    {
        $filter = new MongoApiFilter();
        $wrapper = new FilterWrapper($filter, $query);

        $wrapper->ids('_id.id', 'id');
        $wrapper->like('name');
        $wrapper->like('uri');
        $wrapper->like('model');
        $wrapper->like('proxy');
        $wrapper->range('timeout');
        $wrapper->range('cost');

        return $wrapper->getFilter();
    }
}