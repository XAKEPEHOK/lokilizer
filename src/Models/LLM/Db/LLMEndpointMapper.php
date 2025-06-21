<?php

namespace XAKEPEHOK\Lokilizer\Models\LLM\Db;

use DiBify\DiBify\Mappers\FloatMapper;
use DiBify\DiBify\Mappers\IdMapper;
use DiBify\DiBify\Mappers\IntMapper;
use DiBify\DiBify\Mappers\ModelMapper;
use DiBify\DiBify\Mappers\ObjectMapper;
use DiBify\DiBify\Mappers\PoolMapper;
use DiBify\DiBify\Mappers\StringMapper;
use DiBify\DiBify\Pool\FloatPool;
use XAKEPEHOK\Lokilizer\Models\LLM\LLMEndpoint;
use XAKEPEHOK\Lokilizer\Models\LLM\LLMPricing;

class LLMEndpointMapper extends ModelMapper
{

    public function __construct()
    {
        parent::__construct(LLMEndpoint::class, [
            'id' => IdMapper::getInstance(),
            'name' => StringMapper::getInstance(),
            'uri' => StringMapper::getInstance(),
            'token' => StringMapper::getInstance(),
            'model' => StringMapper::getInstance(),
            'pricing' => new ObjectMapper(LLMPricing::class, [
                'inputPer1M' => FloatMapper::getInstance(),
                'outputPer1M' => FloatMapper::getInstance(),
            ]),
            'cost' => new PoolMapper(FloatPool::class, FloatMapper::getInstance()),
            'timeout' => IntMapper::getInstance(),
            'proxy' => StringMapper::getInstance(),
        ]);
    }

}