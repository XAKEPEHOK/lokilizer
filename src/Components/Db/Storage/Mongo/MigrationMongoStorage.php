<?php

namespace XAKEPEHOK\Lokilizer\Components\Db\Storage\Mongo;

use DiBify\DiBify\Repository\Storage\StorageData;
use DiBify\Migrations\Manager\VersionManagers\VersionManagerInterface;
use MongoDB\Collection;
use XAKEPEHOK\Path\Path;

class MigrationMongoStorage extends MongoStorage implements VersionManagerInterface
{

    public function getApplied(): array
    {
        $migrations = $this->findByFilter([]);
        $result = [];
        foreach ($migrations as $data) {
            $result[$data->id] = $data->body['appliedAt'];
        }

        if (empty($result)) {
            return $this->handleMigrationsFromFile();
        }

        return $result;
    }

    public function apply(string $name): void
    {
        $this->insert(new StorageData($name, [
            'appliedAt' => time(),
        ]));
    }

    public function rollback(string $name): void
    {
        $this->delete($name);
    }

    protected function dates(): array
    {
        return [
            'appliedAt',
        ];
    }

    public function scope(): ?string
    {
        return null;
    }

    public function getCollection(): Collection
    {
        return $this->database->selectCollection('__migrations');
    }

    private function handleMigrationsFromFile(): array
    {
        $file = Path::root()->down('migrations')->down('_applied.json');
        if (file_exists((string)$file)) {
            $applied = json_decode(file_get_contents((string)$file), true);
            foreach ($applied as $name => $time) {
                $this->insert(new StorageData($name, [
                    'appliedAt' => $time,
                ]));
            }
            return $applied;
        }
        return [];
    }
}