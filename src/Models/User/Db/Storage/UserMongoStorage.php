<?php

namespace XAKEPEHOK\Lokilizer\Models\User\Db\Storage;

use DiBify\DiBify\Repository\Storage\StorageData;
use XAKEPEHOK\Lokilizer\Components\Db\Storage\Mongo\MongoStorage;
use MongoDB\Collection;

class UserMongoStorage extends MongoStorage
{

    public function findByEmail(string $email): ?StorageData
    {
        return $this->findOneByFilter(['email' => $email]);
    }

    protected function dates(): array
    {
        return [
            'registeredAt',
            'lastVisitedAt',
            'passwordChangedAt',
            'authResetAt',
        ];
    }

    public function getCollection(): Collection
    {
        return $this->database->selectCollection('users');
    }

    public function scope(): ?string
    {
        return null;
    }
}