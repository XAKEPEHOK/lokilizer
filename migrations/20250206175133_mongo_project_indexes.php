<?php

use Psr\Container\ContainerInterface;
use XAKEPEHOK\Lokilizer\Models\Project\Db\Storage\ProjectMongoStorage;

/** @var ContainerInterface $container */
$container = require __DIR__ . '/../_container.php';

$container->get(ProjectMongoStorage::class)->getCollection()->createIndex(
    [
        "users.user" => -1,
    ],
    [
        'name' => 'projectUsers',
    ]
);