<?php
/**
 * Created for sr-app
 * Date: 2025-01-14 23:56
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Models\Localization\Db\Storage;

use Adbar\Dot;
use DateTimeImmutable;
use DiBify\DiBify\Repository\Storage\StorageData;
use Generator;
use MongoDB\BSON\Regex;
use stdClass;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Components\Db\ApiFilter\ApiFilterInterface;
use XAKEPEHOK\Lokilizer\Components\Db\ApiFilter\FilterWrapper;
use XAKEPEHOK\Lokilizer\Components\Db\ApiFilter\Range\DatetimeRange;
use XAKEPEHOK\Lokilizer\Components\Db\ApiFilter\Range\NumberRange;
use XAKEPEHOK\Lokilizer\Components\Db\ApiFilter\Range\StringRange;
use XAKEPEHOK\Lokilizer\Components\Db\ApiSearchInterface;
use XAKEPEHOK\Lokilizer\Components\Db\Storage\Mongo\MongoApiFilter;
use XAKEPEHOK\Lokilizer\Components\Db\Storage\Mongo\MongoApiSearchTrait;
use XAKEPEHOK\Lokilizer\Components\Db\Storage\Mongo\MongoStorage;
use XAKEPEHOK\Lokilizer\Models\Localization\Components\AbstractPluralValue;
use XAKEPEHOK\Lokilizer\Models\Localization\PluralRecord;
use XAKEPEHOK\Lokilizer\Models\Localization\SimpleRecord;
use XAKEPEHOK\Lokilizer\Models\Project\Project;
use MongoDB\Collection;

class RecordMongoStorage extends MongoStorage implements ApiSearchInterface
{

    use MongoApiSearchTrait;

    public function fetchLanguages(): array
    {
        return $this->getCollection()->distinct('values.language', ['_id.project' => $this->scope()]);
    }

    public function findDuplicates(?string $language, bool $caseSensitive, int $min, ?int $max): array
    {
        // Базовый match по верхнеуровневым полям
        $match = [
            'type' => SimpleRecord::getModelAlias(),
            'outdatedAt' => null,
        ];

        // Собираем весь конвейер в массив $pipeline, чтобы поэтапно применять
        $pipeline = [
            ['$match' => $match],
            // Разворачиваем массив values, чтобы каждый объект в values стал отдельным документом в потоке агрегации
            ['$unwind' => '$values']
        ];

        // Если нужно фильтровать по языку, добавляем match для вложенного поля
        if ($language) {
            $pipeline[] = [
                '$match' => [
                    'values.language' => $language,
                ],
            ];
        }

        // Готовим поле, по которому будем группировать (value).
        // Если регистронезависимо, приводим к lower-case
        $groupValue = ['$trim' => ['input' => '$values.value']];
        if (!$caseSensitive) {
            $groupValue = ['$toLower' => $groupValue];
        }

        // Далее группируем по полученному значению,
        // считая количество (count),
        // собираем поля, которые вам нужны (keys, values и т.д.)
        $pipeline[] = [
            '$group' => [
                '_id' => $groupValue,
                'count' => ['$sum' => 1],
                'keys' => ['$push' => '$key'], // если по-прежнему нужен сбор ключей
                'values' => ['$addToSet' => '$values.value'],
            ],
        ];

        // Фильтруем по количеству дубликатов - min, max
        $countMatch = ['$gte' => $min];
        if ($max !== null) {
            $countMatch['$lte'] = $max;
        }

        $pipeline[] = [
            '$match' => [
                'count' => $countMatch
            ],
        ];

        // Сортируем по убыванию счётчика
        $pipeline[] = [
            '$sort' => ['count' => -1],
        ];

        // Выполняем агрегацию
        $result = $this->aggregate($pipeline)->toArray();

        // Преобразуем в массив
        return json_decode(json_encode($result), true);
    }


    public function findByKey(string $key): ?StorageData
    {
        return $this->findOneByFilter([
            'key' => $key,
        ]);
    }


    public function findAll(bool $withOutdated): array
    {
        $filter = [];
        if (!$withOutdated) {
            $filter['outdatedAt'] = null;
        }

        return $this->findByFilter($filter);
    }


    public function findValueByRegexArray(
        ?string $language,
        array   $regexArray,
        bool    $andTrueOrFalse,
        ?bool   $verified,
        bool    $withOutdated
    )
    {
        // Если $andTrueOrFalse === true, то все регулярки должны совпасть (AND),
        // иначе – достаточно, чтобы совпала хотя бы одна (OR).
        $operator = $andTrueOrFalse ? '$and' : '$or';

        // Готовим условия для SimpleRecord:
        // Вместо 'value.value' => ['$regex' => $regex]
        // теперь проверяем массив 'values' с помощью $elemMatch (чтобы язык, верификация и значение совпали в одном элементе).
        //
        // Для каждой регулярки строим условие вида:
        //  [
        //    'values' => [
        //        '$elemMatch' => [
        //            'value' => [ '$regex' => $regex ],
        //            'language' => $language (если задан),
        //            'verified' => $verified (если задан),
        //        ]
        //    ]
        //  ]
        //
        // И эти условия объединяем через $and / $or (зависит от $operator).
        $simpleRecordConditions = array_map(
            function (string $regex) use ($language, $verified) {
                $elemMatch = [
                    'value' => ['$regex' => $regex],
                ];
                if ($language) {
                    $elemMatch['language'] = $language;
                }
                if (is_bool($verified)) {
                    // Предполагаем, что true/false записано напрямую в поле "verified"
                    $elemMatch['verified'] = $verified;
                }

                return [
                    'values' => [
                        '$elemMatch' => $elemMatch,
                    ],
                ];
            },
            $regexArray
        );

        // Аналогично для PluralRecord:
        // Старый код искал по ['value.<category>' => ['$regex' => ...]].
        // При новой структуре "values" может выглядеть, например, вот так:
        //   values: [
        //     { language: 'ru', verified: true,  value: { one: '...', other: '...' } },
        //     { language: 'en', verified: false, value: { one: '...', other: '...' } }
        //   ]
        //
        // Тогда, чтобы искать по конкретной категории, нужно обращаться к "values.value.<category>".
        // Так же добавляем фильтры по language/verified внутри $elemMatch.
        $pluralRecordConditions = [];
        foreach (AbstractPluralValue::getCategories() as $category) {
            // для каждой категории («one», «few», «other» и т. п.) формируем набор условий по регуляркам
            $conditionsForThisCategory = array_map(
                function (string $regex) use ($language, $verified, $category) {
                    $elemMatch = [
                        // например 'value.one' => ['$regex' => ...]
                        "value.$category" => ['$regex' => $regex],
                    ];
                    if ($language) {
                        $elemMatch['language'] = $language;
                    }
                    if (is_bool($verified)) {
                        $elemMatch['verified'] = $verified;
                    }

                    return [
                        'values' => [
                            '$elemMatch' => $elemMatch,
                        ],
                    ];
                },
                $regexArray
            );

            // Объединяем условия по этой категории через $and / $or
            $pluralRecordConditions[] = [
                $operator => $conditionsForThisCategory,
            ];
        }

        // Теперь склеиваем условия: либо SimpleRecord (и набор regex-условий), либо PluralRecord (и набор категория-условий).
        $filter = [
            '$or' => [
                [
                    'type' => SimpleRecord::getModelAlias(),
                    // $operator => [...], где внутри лежат map-ы для каждой регулярки
                    $operator => $simpleRecordConditions,
                ],
                [
                    'type' => PluralRecord::getModelAlias(),
                    // Тут уже свой $or: "Попробуй найти хоть по одной из категорий"
                    // (если ваш изначальный код именно так делал).
                    '$or' => $pluralRecordConditions,
                ],
            ],
        ];

        // С учётом, что раньше делался фильтр по 'outdatedAt'
        if (!$withOutdated) {
            $filter['outdatedAt'] = null;
        }

        // Результат
        return $this->findByFilter($filter);
    }


    public function count(): int
    {
        return $this->countByCondition([]);
    }

    /**
     * @return Generator<StorageData>
     */
    public function iterator(): Generator
    {
        $cursor = $this->getCollection()->find();
        $cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);
        foreach ($cursor as $document) {
            yield $this->doc2data($document);
        }
    }

    public function findWithKeyPrefix(string $keyPrefix, bool $withOutdated): array
    {
        $filter = [
            'key' => [
                '$regex' => '^' . preg_quote($keyPrefix),
            ],
        ];

        if (!$withOutdated) {
            $filter['outdatedAt'] = null;
        }

        return $this->findByFilter($filter);
    }

    public function fetchKeysArray(bool $withOutdated): array
    {
        if (!$withOutdated) {
            $match['outdatedAt'] = null;
        } else {
            $match = new stdClass();
        }

        $result = json_decode(json_encode($this->aggregate([
            ['$match' => $match],
            ['$group' => [
                '_id' => '$key',
            ]],
        ])->toArray()), true);

        return array_column($result, '_id');
    }

    public function fetchIdsArray(bool $withOutdated): array
    {
        if (!$withOutdated) {
            $match['outdatedAt'] = null;
        } else {
            $match = new stdClass();
        }

        $result = json_decode(json_encode($this->aggregate([
            ['$match' => $match],
            ['$group' => [
                '_id' => '$_id.id',
            ]],
        ])->toArray()), true);

        return array_column($result, '_id');
    }

    public function findNearby(string $parentKey, int $fromPosition, int $toPosition): array
    {
        $filter = [
            'parent' => $parentKey,
            'outdatedAt' => null,
            'position' => [
                '$gte' => $fromPosition,
                '$lte' => $toPosition,
            ],
        ];

        return $this->findByFilter($filter);
    }

    public function findWithValues(string $to, array $values): ?StorageData
    {
        return $this->findOneByFilter(
            [
                'values' => [
                    '$all' => [
                        ...array_map(
                            fn(array $value) => ['$elemMatch' => $value],
                            $values
                        ),
                        [
                            '$elemMatch' => [
                                'language' => $to,
                                'value' => ['$nin' => [null, '']],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'sort' => [
                    'value.verified' => -1
                ],
            ]
        );
    }

    protected function apiFilter(Dot $query): MongoApiFilter|ApiFilterInterface
    {
        $filter = new MongoApiFilter();
        $wrapper = new FilterWrapper($filter, $query);

        $wrapper->ids('_id.id', 'ids');
        $wrapper->same('type');
        $wrapper->like('parent');
        $wrapper->like('key');
        $wrapper->like('comment');
        $wrapper->range('level');
        $wrapper->range('createdAt');
        $wrapper->range('updatedAt');
        $wrapper->range('touchedAt');
        $wrapper->range('outdatedAt');
        $wrapper->range('llmCost');
        $wrapper->range('position');

        if (strlen($query->get('value', '')) > 0) {

            $raw = [
                '$or' => [
                    $this->rawValueElementMatch($query, 'value', 'value'),
                ],
            ];

            foreach (AbstractPluralValue::getCategories() as $category) {
                $raw['$or'][] = $this->rawValueElementMatch($query, 'value', $category);
            }

            $filter->raw($raw);
        }

        if ($query->get('outdated') === true) {
            $filter->range('outdatedAt', new DatetimeRange(new DateTimeImmutable('2000-01-01 00:00:00'), null));
        } else if ($query->get('outdated') === false) {
            $filter->equals('outdatedAt', null);
        }

        if ($query->get('warnings') === true) {
            $warningsFilter = $this->languageBasedFilter($query);
            $warningsFilter->range('warnings', new NumberRange(1, null));
            $filter->equals('values', $warningsFilter);
        } else if ($query->get('warnings') === false) {
            $warningsFilter = $this->languageBasedFilter($query);
            $warningsFilter->equals('warnings', 0);
            $filter->equals('values', $warningsFilter);
        }

        if (is_bool($query->get('verified'))) {
            $verifiedFilter = $this->languageBasedFilter($query);
            $verifiedFilter->equals('verified', $query->get('verified'));
            $filter->equals('values', $verifiedFilter);
        }

        if ($query->get('suggested') === true) {
            $suggestedFilter = $this->languageBasedFilter($query);
            $suggestedFilter->range('suggested.value', new StringRange("", null));
            $filter->equals('values', $suggestedFilter);
        } else if ($query->get('suggested') === false) {
            $suggestedFilter = $this->languageBasedFilter($query);
            $suggestedFilter->equals('suggested.value', null);
            $filter->equals('values', $suggestedFilter);
        }

        return $wrapper->getFilter();
    }

    private function rawValueElementMatch(Dot $query, string $queryKey, string $valueKey): array
    {
        $language = [];
        if (!empty($query->get('language'))) {
            $language['language'] = $query->get('language');
        }

        return [
            'values' => [
                '$elemMatch' => [
                    ...$language,
                    $valueKey => [
                        '$regex' => new Regex(preg_quote(trim($query->get('value')))),
                        '$options' => 'i'
                    ],
                ]
            ],
        ];
    }

    private function languageBasedFilter(Dot $query): MongoApiFilter
    {
        $filter = new MongoApiFilter();
        if ($query->get('language') !== null) {
            $filter->equals('language', $query->get('language'));
        }
        return $filter;
    }

    protected function references(): array
    {
        return [
            'project' => Project::getModelAlias(),
        ];
    }

    protected function dates(): array
    {
        return [
            'createdAt',
            'updatedAt',
            'touchedAt',
            'outdatedAt',
        ];
    }

    protected function pools(): array
    {
        return [
            'llmCost'
        ];
    }

    public function getCollection(): Collection
    {
        return $this->database->selectCollection('records');
    }

    public function scope(): ?string
    {
        return Current::getProject()->id()->get();
    }
}