<?php

use DiBify\Migrations\Manager\VersionManagers\FileVersionManager;
use DiBify\Migrations\Manager\VersionManagers\VersionManagerInterface;
use MongoDB\Client;
use MongoDB\Database;
use Psr\Container\ContainerInterface;

return [
    Redis::class => function () {
        $redis = new Redis();
        $redis->connect($_ENV['REDIS_HOST'], $_ENV['REDIS_PORT']);
        $redis->select($_ENV['REDIS_DATABASE']);
        return $redis;
    },

    Client::class => function () {
        return new Client(
            $_ENV['MONGO_URI'],
            [
                'socketTimeoutMS' => 20 * 60 * 1000,
            ]
        );
    },

    Database::class => function (ContainerInterface $container) {
        return $container->get(Client::class)->selectDatabase('lokilizer');
    },

    VersionManagerInterface::class => function (ContainerInterface $container) {
        return new FileVersionManager();
    },

];