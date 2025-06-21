<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 14.06.2017 14:18
 */

use XAKEPEHOK\Lokilizer\Models\Glossary\Db\Storage\GlossaryRepo;
use XAKEPEHOK\Lokilizer\Models\Glossary\PrimaryGlossary;
use XAKEPEHOK\Lokilizer\Models\Glossary\SpecialGlossary;
use XAKEPEHOK\Lokilizer\Models\LLM\Db\LLMEndpointRepo;
use XAKEPEHOK\Lokilizer\Models\LLM\LLMEndpoint;
use XAKEPEHOK\Lokilizer\Models\Project\Project;
use XAKEPEHOK\Lokilizer\Models\Project\Db\ProjectRepo;
use XAKEPEHOK\Lokilizer\Models\Localization\Db\RecordRepo;
use XAKEPEHOK\Lokilizer\Models\Localization\PluralRecord;
use XAKEPEHOK\Lokilizer\Models\Localization\SimpleRecord;
use XAKEPEHOK\Lokilizer\Models\User\Db\UserRepo;
use XAKEPEHOK\Lokilizer\Models\User\User;
use DiBify\DiBify\Id\SortableUniqueIdGenerator;
use DiBify\DiBify\Locker\LockerInterface;
use DiBify\DiBify\Manager\ConfigManager;
use DiBify\DiBify\Manager\ModelManager;
use DiBify\Locker\Redis\Locker;
use League\Plates\Engine;
use Psr\Container\ContainerInterface;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Contracts\Cache\CacheInterface;
use XAKEPEHOK\Path\Path;

return [

    ConfigManager::class => function (ContainerInterface $container) {
        $idGenerator = new SortableUniqueIdGenerator();
        $config = new ConfigManager();
        $config->add($container->get(UserRepo::class), [User::class], $idGenerator);
        $config->add($container->get(ProjectRepo::class), [Project::class], $idGenerator);
        $config->add($container->get(GlossaryRepo::class), [PrimaryGlossary::class, SpecialGlossary::class], $idGenerator);
        $config->add($container->get(RecordRepo::class), [SimpleRecord::class, PluralRecord::class], $idGenerator);
        $config->add($container->get(LLMEndpointRepo::class), [LLMEndpoint::class], $idGenerator);

        return $config;
    },

    LockerInterface::class => function (ContainerInterface $container) {
        return new Locker(
            redis: $container->get(Redis::class),
            defaultTimeout: 60,
            maxTimeout: 300
        );
    },

    ModelManager::class => function (ContainerInterface $container) {
        return ModelManager::construct(
            $container->get(ConfigManager::class),
            $container->get(LockerInterface::class)
        );
    },

    CacheInterface::class => function (ContainerInterface $container) {
        return new RedisAdapter(
            $container->get(Redis::class)
        );
    },

    Engine::class => function (ContainerInterface $container) {
        $engine = new Engine(Path::root()->down('src/Apps/Portal/View'));
        $engine->registerFunction('redis', fn() => $container->get(Redis::class));
        return $engine;
    },
];