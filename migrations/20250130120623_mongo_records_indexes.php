<?php

use Psr\Container\ContainerInterface;
use XAKEPEHOK\Lokilizer\Models\Localization\Db\Storage\RecordMongoStorage;

/** @var ContainerInterface $container */
$container = require __DIR__ . '/../_container.php';

$container->get(RecordMongoStorage::class)->getCollection()->createIndex(
    [
        "_id.project" => 1,
        "language" => 1,
    ],
    [
        'name' => 'projectLanguage',
    ]
);

$container->get(RecordMongoStorage::class)->getCollection()->createIndex(
    [
        "_id.project" => 1,
        "language" => 1,
        "outdatedAt" => -1,
    ],
    [
        'name' => 'projectLanguageOutdated',
    ]
);

$container->get(RecordMongoStorage::class)->getCollection()->createIndex(
    [
        "_id.project" => 1,
        "language" => 1,
        "touchedAt" => -1,
    ],
    [
        'name' => 'projectLanguageTouched',
    ]
);