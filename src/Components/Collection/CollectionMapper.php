<?php
/**
 * Created for sr-app
 * Date: 29.07.2024 18:57
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Components\Collection;

use DiBify\DiBify\Mappers\ArrayMapper;
use DiBify\DiBify\Mappers\MapperInterface;
use DiBify\DiBify\Mappers\ObjectMapper;

class CollectionMapper extends ObjectMapper
{

    private bool $indexedKeys;

    public function __construct(string $collectionClass, MapperInterface $mapper, bool $indexedKeys = false)
    {
        parent::__construct($collectionClass, [
            'collection' => new ArrayMapper($mapper),
        ]);
        $this->indexedKeys = $indexedKeys;
    }

    public function serialize($complex)
    {
        $data = parent::serialize($complex);
        return $this->indexedKeys ? array_values($data['collection']) : $data['collection'];
    }

    public function deserialize($data)
    {
        $data = ['collection' => $this->indexedKeys ? array_values($data) : $data];
        $collection = parent::deserialize($data);
        if (method_exists($collection, '__wakeup')) {
            $collection->__wakeup();
        }
        return $collection;
    }

}