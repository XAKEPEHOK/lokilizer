<?php

namespace XAKEPEHOK\Lokilizer\Models\Project\Db\Storage;

use XAKEPEHOK\Lokilizer\Components\Db\Storage\Mongo\MongoStorage;
use XAKEPEHOK\Lokilizer\Models\User\User;
use MongoDB\Collection;

class ProjectMongoStorage extends MongoStorage
{

    public function findAll(): array
    {
        return $this->findByFilter([]);
    }

    public function findByUser(string $userId): array
    {
        return $this->findByFilter([
            'users.user' => $userId
        ]);
    }

    protected function references(): array
    {
        return [
            'users.*.user' => User::getModelAlias(),
        ];
    }

    protected function pools(): array
    {
        return [
            'balance'
        ];
    }

    public function scope(): ?string
    {
        return null;
    }

    public function getCollection(): Collection
    {
        return $this->database->selectCollection('projects');
    }
}