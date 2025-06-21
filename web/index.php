<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 14.06.2017 14:15
 */

use XAKEPEHOK\Lokilizer\Apps\Portal\Actions\GettingStartedAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Actions\LLM\LLMAddAction;
use XAKEPEHOK\Lokilizer\Apps\Http\Components\ErrorHandler;
use XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Auth\LogoutAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Backup\BackupRestoreAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Batch\BatchAISuggestAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Batch\BatchDeleteAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Batch\BatchModifyAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Batch\BatchAITranslateAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Dev\DevAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Glossary\GlossaryBuildAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Glossary\GlossaryListAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Glossary\GlossaryUpdateAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Glossary\GlossaryUsageAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Actions\LLM\LLMListAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Actions\LLM\LLMUpdateAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Profile\PasswordChangeAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Profile\ProfileChangeAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Backup\BackupMakeAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Project\ProjectCreateAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Project\ProjectInviteAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Actions\File\DownloadAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Record\GlossaryCheckAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Record\LLMAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Record\SaveAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Auth\SignupAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Tools\AlertMessageAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Tools\DuplicatesAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Tools\GroupsAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Record\TranslationsAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Actions\File\UploadAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Actions\ProgressAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Project\ProjectSettingsAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Project\ProjectListAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Auth\LoginAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Tools\LoosedPlaceholdersAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Tools\PluralsAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Tools\TextTranslateAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Actions\User\UserInviteAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Actions\User\UserListAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Actions\User\UserRoleUpdateAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Middleware\AuthMiddleware;
use XAKEPEHOK\Lokilizer\Apps\Portal\Middleware\ProjectMiddleware;
use XAKEPEHOK\Lokilizer\Apps\Portal\Middleware\RoutingMiddleware;
use XAKEPEHOK\Lokilizer\Components\Db\Storage\Mongo\MongoStorage;
use DI\Bridge\Slim\Bridge;
use League\Plates\Engine;
use Psr\Container\ContainerInterface;
use RKA\Middleware\IpAddress;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use Slim\Routing\RouteCollectorProxy;

/** @var ContainerInterface $container */
$container = require __DIR__ . '/../_container.php';

$app = Bridge::create($container);
$app->addRoutingMiddleware();

$errorMiddleware = $app->addErrorMiddleware($_ENV['APP_ENV'] !== 'prod', true, true);
$errorMiddleware->setDefaultErrorHandler(new ErrorHandler($app->getResponseFactory(), $container->get(Engine::class)));

$app->addMiddleware(new IpAddress(true, explode(',', '')));

$app->add($container->get(RoutingMiddleware::class));

$app->map(['GET', 'POST'], '/signup', SignupAction::class);
$app->map(['GET', 'POST'], '/login', LoginAction::class);
$app->map(['GET', 'POST'], '/logout', LogoutAction::class);

$app->group('', function (RouteCollectorProxy $group) use ($container) {
    $group->get('/[project[/]]', ProjectListAction::class);
    $group->group('/profile', function (RouteCollectorProxy $group) use ($container) {
        $group->map(['GET', 'POST'], '', ProfileChangeAction::class);
        $group->map(['GET', 'POST'], '/password', PasswordChangeAction::class);
    });
    $group->map(['GET', 'POST'], '/project/create', ProjectCreateAction::class);
    $group->map(['GET', 'POST'], '/project/{projectId}/invite/{inviteId}', ProjectInviteAction::class);
    $group->group('/project/{projectId}', function (RouteCollectorProxy $group) use ($container) {

        if ($_ENV['APP_ENV'] === 'dev') {
            $group->get('/_dev', DevAction::class);
        }

        $group->group('/users', function (RouteCollectorProxy $group) use ($container) {
            $group->map(['GET', 'POST'], '', UserListAction::class);
            $group->map(['GET', 'POST'], '/invite', UserInviteAction::class);
            $group->map(['GET', 'POST'], '/{id}', UserRoleUpdateAction::class);
        });
        $group->map(['GET', 'POST'], '/settings', ProjectSettingsAction::class);
        $group->map(['GET', 'POST'], '/upload', UploadAction::class);
        $group->map(['GET', 'POST'], '/download', DownloadAction::class);
        $group->group('/glossary', function (RouteCollectorProxy $group) use ($container) {
            $group->post('/_build', GlossaryBuildAction::class);
            $group->get('/usage', GlossaryUsageAction::class);
            $group->map(['GET', 'POST'],'/list', GlossaryListAction::class);
            $group->map(['GET', 'POST'],'[/{id}]', GlossaryUpdateAction::class);
        });
        $group->map(['GET', 'POST'], '/progress/{uuid}', ProgressAction::class);
        $group->get('[/]', TranslationsAction::class);
        $group->post('/_llm/{id}/{language}/{llm}', LLMAction::class);
        $group->post('/_save/{id}', SaveAction::class);

        if ($_ENV['APP_ENV'] === 'dev') {
            $group->post('/_glossary/{id}', GlossaryCheckAction::class);
        }

        $group->group('/backup', function (RouteCollectorProxy $group) use ($container) {
            $group->map(['GET'],'/make', BackupMakeAction::class);
            $group->map(['GET', 'POST'],'/restore', BackupRestoreAction::class);
        });

        $group->group('/batch', function (RouteCollectorProxy $group) use ($container) {
            $group->map(['GET', 'POST'],'/translate', BatchAITranslateAction::class);
            $group->map(['GET', 'POST'],'/suggest', BatchAISuggestAction::class);
            $group->map(['GET', 'POST'],'/modify', BatchModifyAction::class);
            $group->map(['GET', 'POST'],'/delete', BatchDeleteAction::class);
        });

        $group->group('/llm', function (RouteCollectorProxy $group) use ($container) {
            $group->map(['GET'],'', LLMListAction::class);
            $group->map(['GET', 'POST'],'/add', LLMAddAction::class);
            $group->map(['GET', 'POST'],'/{id}', LLMUpdateAction::class);
        });

        $group->map(['GET', 'POST'],'/alert-message', AlertMessageAction::class);
        $group->get('/duplicates', DuplicatesAction::class);
        $group->get('/groups', GroupsAction::class);
        $group->get('/plurals', PluralsAction::class);
        $group->get('/getting-started', GettingStartedAction::class);
        $group->map(['GET', 'POST'], '/text-translate', TextTranslateAction::class);
        $group->get('/loosed-placeholders', LoosedPlaceholdersAction::class);


    })->add($container->get(ProjectMiddleware::class));
})->add($container->get(AuthMiddleware::class));

if ($_ENV['APP_ENV'] !== 'prod') {
    $app->get('/phpinfo', function (Request $request, Response $response) {
        phpinfo();
        return $response;
    });

    MongoStorage::$logQueries = true;
}

$app->run();