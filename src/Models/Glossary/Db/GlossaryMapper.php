<?php
/**
 * Created for lokilizer
 * Date: 2025-02-10 15:03
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Models\Glossary\Db;

use DiBify\DiBify\Exceptions\SerializerException;
use DiBify\DiBify\Mappers\ArrayMapper;
use DiBify\DiBify\Mappers\Components\UnionRule;
use DiBify\DiBify\Mappers\FloatMapper;
use DiBify\DiBify\Mappers\IdMapper;
use DiBify\DiBify\Mappers\ModelMapper;
use DiBify\DiBify\Mappers\ObjectMapper;
use DiBify\DiBify\Mappers\PoolMapper;
use DiBify\DiBify\Mappers\StringMapper;
use DiBify\DiBify\Mappers\UnionMapper;
use DiBify\DiBify\Pool\FloatPool;
use DiBify\DiBify\Repository\Storage\StorageData;
use XAKEPEHOK\Lokilizer\Components\Db\Mappers\LanguageMapper;
use XAKEPEHOK\Lokilizer\Models\Glossary\Glossary;
use XAKEPEHOK\Lokilizer\Models\Glossary\GlossaryItem;
use XAKEPEHOK\Lokilizer\Models\Glossary\GlossaryPhrase;
use XAKEPEHOK\Lokilizer\Models\Glossary\PrimaryGlossary;
use XAKEPEHOK\Lokilizer\Models\Glossary\SpecialGlossary;

class GlossaryMapper extends UnionMapper
{

    public function __construct()
    {
        $phraseMapper = new ObjectMapper(GlossaryPhrase::class, [
            'language' => LanguageMapper::getInstance(),
            'phrase' => StringMapper::getInstance(),
        ]);

        $baseMappers = [
            'id' => IdMapper::getInstance(),
            'summary' => StringMapper::getInstance(),
            'items' => new ArrayMapper(new ObjectMapper(GlossaryItem::class, [
                'primary' => $phraseMapper,
                'description' => StringMapper::getInstance(),
                'translations' => new ArrayMapper($phraseMapper)
            ])),
            'llmCost' => new PoolMapper(FloatPool::class, FloatMapper::getInstance()),
        ];

        $primaryGlossaryMapper = new ModelMapper(PrimaryGlossary::class, $baseMappers);
        $specialGlossaryMapper = new ModelMapper(SpecialGlossary::class, [
            'keyPrefix' => StringMapper::getInstance(),
            ...$baseMappers,
        ]);

        parent::__construct([
            new UnionRule(
                mapper: $primaryGlossaryMapper,
                serialize: function (Glossary $record, StorageData $data) {
                    if (!($record instanceof PrimaryGlossary)) {
                        throw new SerializerException('Expected PrimaryGlossary, but passed ' . get_class($record));
                    }
                    return $data;
                },
                deserialize: function (StorageData $data) {
                    if (isset($data->body['keyPrefix'])) {
                        throw new SerializerException('Expected PrimaryGlossary without key prefix');
                    }
                    return $data;
                },
            ),
            new UnionRule(
                mapper: $specialGlossaryMapper,
                serialize: function (Glossary $record, StorageData $data) {
                    if (!($record instanceof SpecialGlossary)) {
                        throw new SerializerException('Expected SpecialGlossary, but passed ' . get_class($record));
                    }
                    return $data;
                },
                deserialize: function (StorageData $data) {
                    if (!isset($data->body['keyPrefix'])) {
                        throw new SerializerException('Expected SpecialGlossary with key prefix');
                    }
                    return $data;
                },
            ),
        ]);
    }

    public function deserialize($data)
    {
        if (!isset($data->body['llmCost'])) {
            $data->body['llmCost'] = 0.0;
        }

        return parent::deserialize($data);
    }

}