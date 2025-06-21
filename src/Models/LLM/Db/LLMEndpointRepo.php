<?php

namespace XAKEPEHOK\Lokilizer\Models\LLM\Db;

use DiBify\DiBify\Exceptions\DuplicateModelException;
use DiBify\DiBify\Exceptions\SerializerException;
use DiBify\DiBify\Exceptions\UnassignedIdException;
use DiBify\DiBify\Mappers\MapperInterface;
use DiBify\DiBify\Model\ModelInterface;
use DiBify\DiBify\Replicator\DirectReplicator;
use Exception;
use Throwable;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Components\Db\ApiSearchInterface;
use XAKEPEHOK\Lokilizer\Components\Db\Repo\RepoApiSearchTrait;
use XAKEPEHOK\Lokilizer\Components\Db\Repo\Repository;
use XAKEPEHOK\Lokilizer\Models\LLM\Db\Storage\LLMEndpointMongoStorage;
use XAKEPEHOK\Lokilizer\Models\LLM\Exceptions\NoLLMEndpointException;
use XAKEPEHOK\Lokilizer\Models\LLM\LLMEndpoint;

class LLMEndpointRepo extends Repository implements ApiSearchInterface
{

    use RepoApiSearchTrait;

    public function __construct(LLMEndpointMongoStorage $storage)
    {
        parent::__construct(new DirectReplicator($storage));
    }

    /**
     * @return LLMEndpoint[]
     * @throws DuplicateModelException
     * @throws SerializerException
     * @throws UnassignedIdException
     */
    public function findAll(): array
    {
        /** @var LLMEndpointMongoStorage $storage */
        $storage = $this->getReplicator()->getPrimary();

        /** @var LLMEndpoint[] $models */
        $models = $this->populateMany($storage->findAll());
        return $models;
    }

    public function findDefault(): ?LLMEndpoint
    {
        $exception = new NoLLMEndpointException('You need to have least one added LLM endpoint.');

        if (Current::getProject()->getDefaultLLM()) {
            /** @var LLMEndpoint $model */
            try {
                $model = Current::getProject()->getDefaultLLM()->getModel();
                return $model;
            } catch (Throwable) {
                throw $exception;
            }
        }

        /** @var LLMEndpointMongoStorage $storage */
        $storage = $this->getReplicator()->getPrimary();

        $data = $storage->findDefault();
        if (is_null($data)) {
            throw $exception;
        }

        /** @var LLMEndpoint $model */
        $model = $this->populateOne($data);
        return $model;
    }

    public function findById($id, Exception $notFoundException = null): ModelInterface|LLMEndpoint|null
    {
        return parent::findById($id, $notFoundException);
    }

    public function getMapper(): MapperInterface
    {
        if (!isset($this->mapper)) {
            $this->mapper = new LLMEndpointMapper();
        }
        return $this->mapper;
    }

    public function classes(): array
    {
        return [LLMEndpoint::class];
    }
}