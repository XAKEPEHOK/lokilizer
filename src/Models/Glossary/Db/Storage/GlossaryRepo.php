<?php
/**
 * Created for lokilizer
 * Date: 2025-02-10 15:34
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Models\Glossary\Db\Storage;

use DiBify\DiBify\Exceptions\DuplicateModelException;
use DiBify\DiBify\Exceptions\SerializerException;
use DiBify\DiBify\Exceptions\UnassignedIdException;
use DiBify\DiBify\Mappers\MapperInterface;
use DiBify\DiBify\Replicator\DirectReplicator;
use XAKEPEHOK\Lokilizer\Components\Db\ApiSearchInterface;
use XAKEPEHOK\Lokilizer\Components\Db\Repo\RepoApiSearchTrait;
use XAKEPEHOK\Lokilizer\Components\Db\Repo\Repository;
use XAKEPEHOK\Lokilizer\Models\Glossary\Db\GlossaryMapper;
use XAKEPEHOK\Lokilizer\Models\Glossary\Glossary;
use XAKEPEHOK\Lokilizer\Models\Glossary\PrimaryGlossary;
use XAKEPEHOK\Lokilizer\Models\Glossary\SpecialGlossary;

class GlossaryRepo extends Repository implements ApiSearchInterface
{

    use RepoApiSearchTrait;


    public function __construct(GlossaryMongoStorage $storage)
    {
        parent::__construct(new DirectReplicator($storage));
    }

    /**
     * @return Glossary[]
     * @throws DuplicateModelException
     * @throws SerializerException
     * @throws UnassignedIdException
     */
    public function findAll(): array
    {
        /** @var GlossaryMongoStorage $storage */
        $storage = $this->getReplicator()->getPrimary();
        return $this->populateMany($storage->findAll());
    }

    public function findPrimary(): PrimaryGlossary
    {
        /** @var GlossaryMongoStorage $storage */
        $storage = $this->getReplicator()->getPrimary();
        $data = $storage->findPrimary();
        if ($data) {
            /** @noinspection PhpIncompatibleReturnTypeInspection */
            return $this->populateOne($data);
        }
        $glossary = new PrimaryGlossary();
        $glossary->id()->assign('primary');
        return $glossary;
    }

    public function findByKeyPrefix(string $keyPrefix): ?SpecialGlossary
    {
        /** @var GlossaryMongoStorage $storage */
        $storage = $this->getReplicator()->getPrimary();
        $data = $storage->findByKeyPrefix($keyPrefix);
        if (!$data) {
            return null;
        }
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->populateOne($data);
    }

    public function findForKeys(string ...$keys): array
    {
        $prefixes = [];

        foreach ($keys as $key) {
            $parts = explode('.', $key);
            $current = '';

            foreach ($parts as $part) {
                $current = $current === '' ? $part : $current . '.' . $part;
                $prefixes[] = $current;
            }
        }

        $prefixes = array_unique($prefixes);

        /** @var GlossaryMongoStorage $storage */
        $storage = $this->getReplicator()->getPrimary();
        $data = $storage->findByKeyPrefixes(...$prefixes);
        return $this->populateMany($data);
    }

    public function getMapper(): MapperInterface
    {
        if (!isset($this->mapper)) {
            $this->mapper = new GlossaryMapper();
        }
        return $this->mapper;
    }

    public function classes(): array
    {
        return [PrimaryGlossary::class, SpecialGlossary::class];
    }
}