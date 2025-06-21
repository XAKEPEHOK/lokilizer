<?php
/**
 * Created for sr-app
 * Date: 2025-01-14 23:59
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Models\Localization\Db;

use DiBify\DiBify\Exceptions\DuplicateModelException;
use DiBify\DiBify\Exceptions\SerializerException;
use DiBify\DiBify\Exceptions\UnassignedIdException;
use DiBify\DiBify\Manager\Transaction;
use DiBify\DiBify\Mappers\MapperInterface;
use DiBify\DiBify\Replicator\DirectReplicator;
use Generator;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Components\Db\ApiSearchInterface;
use XAKEPEHOK\Lokilizer\Components\Db\Repo\RepoApiSearchTrait;
use XAKEPEHOK\Lokilizer\Components\Db\Repo\Repository;
use XAKEPEHOK\Lokilizer\Models\Localization\Components\SimpleValue;
use XAKEPEHOK\Lokilizer\Models\Localization\Db\Storage\RecordMongoStorage;
use XAKEPEHOK\Lokilizer\Models\Localization\PluralRecord;
use XAKEPEHOK\Lokilizer\Models\Localization\SimpleRecord;
use PrinsFrank\Standards\Language\LanguageAlpha2;
use XAKEPEHOK\Lokilizer\Models\Localization\Record;

class RecordRepo extends Repository implements ApiSearchInterface
{

    use RepoApiSearchTrait;

    public function __construct(
        RecordMongoStorage     $storage,
        private CacheInterface $cache,
    )
    {
        parent::__construct(new DirectReplicator($storage));
    }

    /**
     * @param bool $withOutdated
     * @return string[]
     */
    public function fetchKeysArray(bool $withOutdated = false): array
    {
        /** @var RecordMongoStorage $storage */
        $storage = $this->getReplicator()->getPrimary();
        return $storage->fetchKeysArray($withOutdated);
    }

    /**
     * @param bool $withOutdated
     * @return string[]
     */
    public function fetchIdsArray(bool $withOutdated = false): array
    {
        /** @var RecordMongoStorage $storage */
        $storage = $this->getReplicator()->getPrimary();
        return $storage->fetchIdsArray($withOutdated);
    }

    /**
     * @param LanguageAlpha2 $to
     * @param SimpleValue ...$values
     * @return SimpleRecord|null
     * @throws DuplicateModelException
     * @throws SerializerException
     * @throws UnassignedIdException
     */
    public function findAlreadyTranslated(LanguageAlpha2 $to, SimpleValue ...$values): ?SimpleRecord
    {
        /** @var RecordMongoStorage $storage */
        $storage = $this->getReplicator()->getPrimary();
        $data = $storage->findWithValues($to->value, array_map(
            fn(SimpleValue $value): array => [
                'language' => $value->getLanguage()->value,
                'value' => $value->value,
            ],
            $values
        ));

        if (is_null($data)) {
            return null;
        }

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->populateOne($data);
    }

    /**
     * @param bool $withOutdated
     * @return SimpleRecord[]|PluralRecord[]
     * @throws DuplicateModelException
     * @throws SerializerException
     * @throws UnassignedIdException
     */
    public function findAll(bool $withOutdated = false): array
    {
        /** @var RecordMongoStorage $primary */
        $primary = $this->getReplicator()->getPrimary();
        return $this->populateMany($primary->findAll($withOutdated));
    }

    /**
     * @param Record $record
     * @param int $range
     * @return Record[]
     * @throws DuplicateModelException
     * @throws SerializerException
     * @throws UnassignedIdException
     */
    public function findNearby(Record $record, int $range = 5): array
    {
        /** @var RecordMongoStorage $primary */
        $primary = $this->getReplicator()->getPrimary();
        return $this->populateMany($primary->findNearby(
            $record->getParent(),
            $record->getPosition() - $range,
            $record->getPosition() + $range,
        ));
    }

    /**
     * @param string $key
     * @return SimpleRecord|PluralRecord
     * @throws DuplicateModelException
     * @throws SerializerException
     * @throws UnassignedIdException
     */
    public function findByKey(string $key): ?Record
    {
        /** @var RecordMongoStorage $primary */
        $primary = $this->getReplicator()->getPrimary();
        $data = $primary->findByKey($key);
        if (is_null($data)) {
            return null;
        }
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->populateOne($data);
    }

    /**
     * @param string $keyPrefix
     * @param bool $withOutdated
     * @return Record[]
     * @throws DuplicateModelException
     * @throws SerializerException
     * @throws UnassignedIdException
     */
    public function findWithKeyPrefix(string $keyPrefix, bool $withOutdated = false): array
    {
        /** @var RecordMongoStorage $primary */
        $primary = $this->getReplicator()->getPrimary();
        return $this->populateMany($primary->findWithKeyPrefix($keyPrefix, $withOutdated));
    }


    /**
     * @param LanguageAlpha2|null $language
     * @param bool $caseSensitive
     * @param int $min
     * @param int|null $max
     * @return SimpleRecord[]|PluralRecord[]
     */
    public function findDuplicates(?LanguageAlpha2 $language, bool $caseSensitive, int $min, ?int $max): array
    {
        /** @var RecordMongoStorage $primary */
        $primary = $this->getReplicator()->getPrimary();
        return $primary->findDuplicates($language?->value, $caseSensitive, $min, $max);
    }

    public function findValueByRegexArray(?LanguageAlpha2 $language, array $regexArray, bool $andTrueOrFalse, bool|null $verified = null, bool $withOutdated = false): array
    {
        /** @var RecordMongoStorage $primary */
        $primary = $this->getReplicator()->getPrimary();
        return $this->populateMany($primary->findValueByRegexArray($language->value, $regexArray, $andTrueOrFalse, $verified, $withOutdated));
    }

    public function count(): int
    {
        /** @var RecordMongoStorage $storage */
        $storage = $this->replicator->getPrimary();
        return $storage->countByAPI([]);
    }

    /**
     * @return Generator<Record>
     * @throws DuplicateModelException
     * @throws SerializerException
     * @throws UnassignedIdException
     */
    public function iterator(): Generator
    {
        /** @var RecordMongoStorage $storage */
        $storage = $this->replicator->getPrimary();
        foreach ($storage->iterator() as $data) {
            yield $this->populateOne($data);
        }
    }

    /**
     * @param bool $asEnum
     * @param bool $withCache
     * @return string[]|LanguageAlpha2[]
     * @throws InvalidArgumentException
     */
    public function fetchLanguages(bool $asEnum = false, bool $withCache = true): array
    {
        $cacheKey = "language-" . Current::getProject()->id();
        if (!$withCache) {
            $this->cache->delete($cacheKey);
        }

        $languages = $this->cache->get($cacheKey, function (ItemInterface $item) {
            $item->expiresAfter(60 * 60);
            /** @var RecordMongoStorage $primary */
            $primary = $this->getReplicator()->getPrimary();
            return $primary->fetchLanguages();
        });

        $project = Current::getProject();
        $map = [
            LanguageAlpha2::English->value => -10,
            ($project->getSecondaryLanguage() ?? $project->getPrimaryLanguage())->value => -500,
            $project->getPrimaryLanguage()->value => -1000,
        ];

        usort($languages, function (string $a, string $b) use ($map) {
            $scoreA = $map[$a] ?? $a;
            $scoreB = $map[$b] ?? $b;
            return  $scoreA <=> $scoreB;
        });

        if ($asEnum) {
            return array_map(
                fn(string $language) => LanguageAlpha2::from($language),
                $languages
            );
        }

        return $languages;
    }

    public function commit(Transaction $transaction): void
    {
        $this->cache->delete("language-" . Current::getProject()->id());
        parent::commit($transaction);
    }

    public function getMapper(): MapperInterface
    {
        if (!isset($this->mapper)) {
            $this->mapper = new RecordMapper();
        }
        return $this->mapper;
    }

    public function classes(): array
    {
        return [SimpleRecord::class, PluralRecord::class];
    }
}