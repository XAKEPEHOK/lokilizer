<?php
/**
 * Created for sr-app
 * Date: 2025-01-14 23:07
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Models\Localization\Db;

use DiBify\DiBify\Exceptions\SerializerException;
use DiBify\DiBify\Mappers\ArrayMapper;
use DiBify\DiBify\Mappers\BoolMapper;
use DiBify\DiBify\Mappers\Components\UnionRule;
use DiBify\DiBify\Mappers\DateTimeMapper;
use DiBify\DiBify\Mappers\FloatMapper;
use DiBify\DiBify\Mappers\IdMapper;
use DiBify\DiBify\Mappers\IntMapper;
use DiBify\DiBify\Mappers\ModelMapper;
use DiBify\DiBify\Mappers\NullOrMapper;
use DiBify\DiBify\Mappers\ObjectMapper;
use DiBify\DiBify\Mappers\PoolMapper;
use DiBify\DiBify\Mappers\StringMapper;
use DiBify\DiBify\Mappers\UnionMapper;
use DiBify\DiBify\Pool\FloatPool;
use DiBify\DiBify\Repository\Storage\StorageData;
use XAKEPEHOK\Lokilizer\Components\Db\Mappers\LanguageMapper;
use XAKEPEHOK\Lokilizer\Models\Localization\Components\CardinalPluralValue;
use XAKEPEHOK\Lokilizer\Models\Localization\Components\OrdinalPluralValue;
use XAKEPEHOK\Lokilizer\Models\Localization\Components\SimpleValue;
use XAKEPEHOK\Lokilizer\Models\Localization\PluralRecord;
use XAKEPEHOK\Lokilizer\Models\Localization\SimpleRecord;
use XAKEPEHOK\Lokilizer\Models\Localization\Components\AbstractPluralValue;
use XAKEPEHOK\Lokilizer\Models\Localization\Record;

class RecordMapper extends UnionMapper
{

    public function __construct()
    {
        $baseMappers = [
            'id' => IDMapper::getInstance(),
            'createdAt' => DateTimeMapper::getInstanceImmutable(),
            'updatedAt' => DateTimeMapper::getInstanceImmutable(),
            'touchedAt' => DateTimeMapper::getInstanceImmutable(),
            'level' => IntMapper::getInstance(),
            'parent' => StringMapper::getInstance(),
            'key' => StringMapper::getInstance(),
            'comment' => StringMapper::getInstance(),
            'outdatedAt' => new NullOrMapper(DateTimeMapper::getInstanceImmutable()),
            'llmCost' => new PoolMapper(FloatPool::class, FloatMapper::getInstance()),
            'position' => IntMapper::getInstance(),
        ];

        $baseValueMappers = [
            'language' => LanguageMapper::getInstance(),
            'warnings' => IntMapper::getInstance(),
            'verified' => BoolMapper::getInstance(),
        ];

        $simpleValueMapper = new ObjectMapper(SimpleValue::class, [
            ...$baseValueMappers,
            'value' => StringMapper::getInstance(),
            'suggested' => new NullOrMapper(new ObjectMapper(SimpleValue::class, [
                ...$baseValueMappers,
                'value' => StringMapper::getInstance(),
            ])),
        ]);

        $basePluralMappers = [
            ...$baseValueMappers,
            'zero' => StringMapper::getInstance(),
            'one' => StringMapper::getInstance(),
            'two' => StringMapper::getInstance(),
            'few' => StringMapper::getInstance(),
            'many' => StringMapper::getInstance(),
            'other' => StringMapper::getInstance(),
        ];

        $pluralValueMapper = new UnionMapper([
            new UnionRule(
                mapper: new ObjectMapper(CardinalPluralValue::class, [
                    ...$basePluralMappers,
                    'suggested' => new NullOrMapper(new ObjectMapper(CardinalPluralValue::class, $basePluralMappers))
                ]),
                serialize: function (AbstractPluralValue $value, array $data) {
                    if (!($value instanceof CardinalPluralValue)) {
                        throw new SerializerException('CardinalPluralValue expected');
                    }
                    $data['type'] = $value::getType();
                    return $data;
                },
                deserialize: function (array $data) {
                    if ($data['type'] !== CardinalPluralValue::getType()) {
                        throw new SerializerException('Type should be a CardinalPluralValue');
                    }
                    return $data;
                },
            ),
            new UnionRule(
                mapper: new ObjectMapper(OrdinalPluralValue::class, [
                    ...$basePluralMappers,
                    'suggested' => new NullOrMapper(new ObjectMapper(OrdinalPluralValue::class, $basePluralMappers))
                ]),
                serialize: function (AbstractPluralValue $value, array $data) {
                    if (!($value instanceof OrdinalPluralValue)) {
                        throw new SerializerException('OrdinalPluralValue expected');
                    }
                    $data['type'] = $value::getType();
                    return $data;
                },
                deserialize: function (array $data) {
                    if ($data['type'] !== OrdinalPluralValue::getType()) {
                        throw new SerializerException('Type should be a OrdinalPluralValue');
                    }
                    return $data;
                },
            ),
        ]);

        $simpleStringMappers = new ModelMapper(SimpleRecord::class, [
            ...$baseMappers,
            'values' => new ArrayMapper($simpleValueMapper),
        ]);

        $pluralStringMappers = new ModelMapper(PluralRecord::class, [
            ...$baseMappers,
            'values' => new ArrayMapper($pluralValueMapper),
        ]);

        parent::__construct([
            new UnionRule(
                mapper: $simpleStringMappers,
                serialize: function (Record $record, StorageData $data) {
                    if (!($record instanceof SimpleRecord)) {
                        throw new SerializerException('SimpleString string expected');
                    }
                    $data->body['type'] = $record::getModelAlias();
                    return $data;
                },
                deserialize: function (StorageData $data) {
                    if ($data->body['type'] !== SimpleRecord::getModelAlias()) {
                        throw new SerializerException('Type should be a SimpleString');
                    }
                    return $data;
                },
            ),
            new UnionRule(
                mapper: $pluralStringMappers,
                serialize: function (Record $record, StorageData $data) {
                    if (!($record instanceof PluralRecord)) {
                        throw new SerializerException('PluralString string expected');
                    }
                    $data->body['type'] = $record::getModelAlias();
                    return $data;
                },
                deserialize: function (StorageData $data) {
                    if ($data->body['type'] !== PluralRecord::getModelAlias()) {
                        throw new SerializerException('Type should be a PluralString');
                    }
                    return $data;
                },
            ),
        ]);
    }

}