<?php

namespace XAKEPEHOK\Lokilizer\Models\User\Db;

use PhpDto\EmailAddress\EmailAddress;
use XAKEPEHOK\Lokilizer\Components\Db\Repo\Repository;
use XAKEPEHOK\Lokilizer\Models\User\Db\Storage\UserMongoStorage;
use XAKEPEHOK\Lokilizer\Models\User\User;
use DiBify\DiBify\Mappers\MapperInterface;
use DiBify\DiBify\Replicator\DirectReplicator;

class UserRepo extends Repository
{

    public function __construct(UserMongoStorage $mongoStorage)
    {
        parent::__construct(new DirectReplicator($mongoStorage));
    }

    public function findByEmail(EmailAddress $email): ?User
    {
        /** @var UserMongoStorage $storage */
        $storage = $this->getReplicator()->getPrimary();
        $data = $storage->findByEmail($email->get());
        if (is_null($data)) {
            return null;
        }
        return $this->populateOne($data);
    }

    protected function getMapper(): MapperInterface
    {
        if (!isset($this->mapper)) {
            $this->mapper = new UserMapper();
        }
        return $this->mapper;
    }

    public function classes(): array
    {
        return [User::class];
    }
}