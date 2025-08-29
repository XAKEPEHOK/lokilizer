<?php

use DiBify\Migrations\Manager\Commands\MigrationCreateCommand;
use DiBify\Migrations\Manager\Commands\MigrationRunCommand;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application;
use XAKEPEHOK\Lokilizer\Apps\Console\Handle\HandleQueueCommand;
use XAKEPEHOK\Lokilizer\Apps\Console\Handle\Tasks\BackupRestoreTaskCommand;
use XAKEPEHOK\Lokilizer\Apps\Console\Handle\Tasks\BatchAISuggestTaskCommand;
use XAKEPEHOK\Lokilizer\Apps\Console\Handle\Tasks\BackupMakeTaskCommand;
use XAKEPEHOK\Lokilizer\Apps\Console\Handle\Tasks\BatchDeleteTaskCommand;
use XAKEPEHOK\Lokilizer\Apps\Console\Handle\Tasks\BatchModifyTaskCommand;
use XAKEPEHOK\Lokilizer\Apps\Console\Handle\Tasks\BatchAITranslateTaskCommand;
use XAKEPEHOK\Lokilizer\Apps\Console\Handle\Tasks\FileUploadTaskCommand;
use XAKEPEHOK\Lokilizer\Apps\Console\Handle\Tasks\GlossaryTranslateTaskCommand;

/** @var ContainerInterface $container */
$container = require_once __DIR__ . '/_container.php';

$app = new Application();
$app->add($container->get(HandleQueueCommand::class));
$app->add($container->get(FileUploadTaskCommand::class));
$app->add($container->get(BatchAITranslateTaskCommand::class));
$app->add($container->get(GlossaryTranslateTaskCommand::class));
$app->add($container->get(BatchAISuggestTaskCommand::class));
$app->add($container->get(BatchModifyTaskCommand::class));
$app->add($container->get(BatchDeleteTaskCommand::class));

$app->add($container->get(BackupMakeTaskCommand::class));
$app->add($container->get(BackupRestoreTaskCommand::class));

$app->add($container->get(MigrationCreateCommand::class));
$app->add($container->get(MigrationRunCommand::class));
$app->run();