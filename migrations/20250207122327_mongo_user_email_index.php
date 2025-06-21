<?php

use Psr\Container\ContainerInterface;
use XAKEPEHOK\Lokilizer\Models\User\Db\Storage\UserMongoStorage;

/** @var ContainerInterface $container */
$container = require __DIR__ . '/../_container.php';

$container->get(UserMongoStorage::class)->getCollection()->createIndex(
    [
        "email" => 1,
    ],
    [
        'name' => 'userEmail',
        'unique' => true
    ]
);