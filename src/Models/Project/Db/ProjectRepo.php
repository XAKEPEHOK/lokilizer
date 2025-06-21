<?php

namespace XAKEPEHOK\Lokilizer\Models\Project\Db;

use XAKEPEHOK\Lokilizer\Components\Db\Repo\Repository;
use XAKEPEHOK\Lokilizer\Models\Project\Project;
use XAKEPEHOK\Lokilizer\Models\Project\Db\Storage\ProjectMongoStorage;
use XAKEPEHOK\Lokilizer\Models\User\User;
use DiBify\DiBify\Exceptions\DuplicateModelException;
use DiBify\DiBify\Exceptions\SerializerException;
use DiBify\DiBify\Exceptions\UnassignedIdException;
use DiBify\DiBify\Helpers\IdHelper;
use DiBify\DiBify\Mappers\MapperInterface;
use DiBify\DiBify\Replicator\DirectReplicator;

class ProjectRepo extends Repository
{

    public function __construct(ProjectMongoStorage $mongoStorage)
    {
        parent::__construct(new DirectReplicator($mongoStorage));
    }

    /**
     * @param User $user
     * @return User[]
     * @throws DuplicateModelException
     * @throws SerializerException
     * @throws UnassignedIdException
     */
    public function findByUser(User $user): array
    {
        /** @var ProjectMongoStorage $storage */
        $storage = $this->getReplicator()->getPrimary();
        return $this->populateMany($storage->findByUser(IdHelper::scalarizeOne($user)));
    }

    /**
     * @return Project[]
     * @throws DuplicateModelException
     * @throws SerializerException
     * @throws UnassignedIdException
     */
    public function findAll(): array
    {
        /** @var ProjectMongoStorage $storage */
        $storage = $this->getReplicator()->getPrimary();
        return $this->populateMany($storage->findAll());
    }

    protected function getMapper(): MapperInterface
    {
        if (!isset($this->mapper)) {
            $this->mapper = new ProjectMapper();
        }
        return $this->mapper;
    }

    public function classes(): array
    {
        return [Project::class];
    }
}