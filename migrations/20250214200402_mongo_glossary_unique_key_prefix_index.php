<?php

use Psr\Container\ContainerInterface;
use XAKEPEHOK\Lokilizer\Models\Glossary\Db\Storage\GlossaryMongoStorage;

/** @var ContainerInterface $container */
$container = require __DIR__ . '/../_container.php';

$container->get(GlossaryMongoStorage::class)->getCollection()->createIndex(
    [
        "_id.project" => -1,
        "keyPrefix" => 1,
    ],
    [
        'name' => 'keyPrefix',
        'unique' => true
    ]
);