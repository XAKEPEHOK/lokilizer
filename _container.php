<?php
/**
 * Created for Ploito
 * Datetime: 27.06.2019 13:05
 * @author Timur Kasumov aka XAKEPEHOK
 */

require __DIR__.'/vendor/autoload.php';
use DI\ContainerBuilder;
use DiBify\DiBify\Manager\ModelManager;
use Dotenv\Dotenv;
use Dotenv\Repository\Adapter\EnvConstAdapter;
use Dotenv\Repository\RepositoryBuilder;

$repository = RepositoryBuilder::createWithNoAdapters()
    ->addAdapter(EnvConstAdapter::class)
    ->immutable()
    ->make();

$env = Dotenv::create($repository, __DIR__);
$env->load();

$_ENV['PROJECT_NAME'] = 'Lokilizer';
$_ENV['PROJECT_HOME'] = 'https://lokilizer.com';

$isSentry = isset($_ENV['SENTRY']) && !empty($_ENV['SENTRY']);
if ($isSentry) {
    Sentry\init([
        'dsn' => $_ENV['SENTRY'],
        'environment' => $_ENV['APP_ENV'],
        'before_send' => function (Sentry\Event $event): ?Sentry\Event {
            $exceptions = $event->getExceptions();
            $skipClasses = [
                \Slim\Exception\HttpException::class,
            ];
            if (count($exceptions) > 0) {
                $exceptionClass = $exceptions[0]->getType();
                foreach ($skipClasses as $skipClass) {
                    if (is_a($exceptionClass, $skipClass, true)) {
                        return null;
                    }
                }
            }
            return $event;
        },
    ]);
}

$config = array_merge(
    require __DIR__ . '/app/config/infrastructure.php',
    require __DIR__ . '/app/config/dependencies.php',
);

if ($_ENV['APP_ENV'] === 'dev') {
    if (file_exists(__DIR__ . '/app/config/infrastructure.dev.php')) {
        $config = array_merge($config, require __DIR__ . '/app/config/infrastructure.dev.php');
    }

    if (file_exists(__DIR__ . '/app/config/dependencies.dev.php')) {
        $config = array_merge($config, require __DIR__ . '/app/config/dependencies.dev.php');
    }
}

if ($_ENV['APP_ENV'] === 'prod') {
    if (file_exists(__DIR__ . '/app/config/infrastructure.prod.php')) {
        $config = array_merge($config, require __DIR__ . '/app/config/infrastructure.prod.php');
    }

    if (file_exists(__DIR__ . '/app/config/dependencies.prod.php')) {
        $config = array_merge($config, require __DIR__ . '/app/config/dependencies.prod.php');
    }
}

if (file_exists(__DIR__ . '/app/config/infrastructure.local.php')) {
    $config = array_merge($config, require __DIR__ . '/app/config/infrastructure.local.php');
}

if (file_exists(__DIR__ . '/app/config/dependencies.local.php')) {
    $config = array_merge($config, require __DIR__ . '/app/config/dependencies.local.php');
}

$builder = new ContainerBuilder();
$builder->addDefinitions($config);

$container = $builder->build();

$container->get(ModelManager::class);

return $container;