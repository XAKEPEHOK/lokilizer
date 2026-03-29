<?php

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Project;

use DiBify\DiBify\Manager\ModelManager;
use DiBify\DiBify\Manager\Transaction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RenderAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Models\Project\Components\UserRole;
use XAKEPEHOK\Lokilizer\Models\Project\Db\ProjectRepo;
use XAKEPEHOK\Lokilizer\Models\Project\Project;
use League\Plates\Engine;
use Psr\Container\ContainerInterface; // <-- Добавляем импорт
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use XAKEPEHOK\Lokilizer\Services\InviteService\InviteService;
use XAKEPEHOK\Lokilizer\Models\User\User;
use XAKEPEHOK\Lokilizer\Services\TokenService;
use Dflydev\FigCookies\Cookies;
use DiBify\DiBify\Manager\ModelManager as ModelManagerService;

class ProjectInviteAction extends RenderAction
{
    // Добавляем поле для хранения контейнера
    private ContainerInterface $container;

    public function __construct(
        Engine        $renderer,
        private readonly ProjectRepo $projectRepo,
        private readonly InviteService $inviteService,
        private readonly ModelManager $modelManager,
        private readonly TokenService $tokenService,
        private readonly ModelManagerService $modelManagerService,
        ContainerInterface $container, // <-- Внедряем контейнер
    )
    {
        parent::__construct($renderer);
        $this->container = $container; // <-- Сохраняем в поле
    }

    public function __invoke(Request $request, Response $response): Response|ResponseInterface
    {
        $projectId = $request->getAttribute('projectId');
        $inviteId = $request->getAttribute('inviteId');

        // Проверяем валидность приглашения ДО проверки авторизации
        /** @var Project $project */
        $project = $this->projectRepo->findById($projectId);
        if (!$project) {
            return $this->render($response, 'errors/not_found', [
                'request' => $request,
                'error' => 'Project with passed id was not found',
            ]);
        }

        $invite = $this->inviteService->getInviteByIdForProject($inviteId, $projectId);

        if (!$invite) {
            return $this->render($response, 'errors/not_found', [
                'request' => $request,
                'error' => 'Invite link expired',
            ]);
        }

        // Получаем пользователя вручную
        $uuid = Cookies::fromRequest($request)->get('uuid')?->getValue() ?? '';
        error_log("DEBUG ProjectInviteAction: UUID from cookie: '$uuid'");
        $user = $this->tokenService->parseCookieToken($uuid);

        if ($user !== null) {
            /** @var User $user */
            $user = $this->modelManagerService->refreshOne($user);
            Current::setUser($user);
        }

        if ($user === null) {
            // Пользователь НЕ авторизован
            // Отрисовываем home_index.php НАПРЯМУЮ с inviteFlag = true
            $allowSignup = $this->container->get('ALLOW_SIGNUP'); // <-- Теперь корректно получаем из контейнера
            $inviteFlag = true;

            // --- ПЕРЕДАЕМ projectId и inviteId для возможного редиректа после входа ---
            $projectInviteIdForLogin = $projectId; // <-- Передаём projectId
            $projectInviteIdValueForLogin = $inviteId; // <-- Передаём inviteId
            // --- /ПЕРЕДАЕМ projectId и inviteId ---
            
            if ($_ENV['APP_ENV'] === 'dev') {
                error_log("DEBUG ProjectInviteAction: Rendering home_index with allowSignup: " . ($allowSignup ? 'TRUE' : 'FALSE') . " and inviteFlag: " . ($inviteFlag ? 'TRUE' : 'FALSE'));
            }

            return $this->render($response, 'home/home_index', [
                'request' => $request,
                'allowSignup' => $allowSignup,
                'inviteFlag' => $inviteFlag,
                'projectInviteIdForLogin' => $projectInviteIdForLogin, // <-- Передаём projectId
                'projectInviteIdValueForLogin' => $projectInviteIdValueForLogin, // <-- Передаём inviteId
                'debugInfo' => [
                    'originalUri' => $request->getUri()->__toString(), // URI - /project/.../invite/...
                    'referrerHeader' => $request->getHeaderLine('Referer'),
                ],
            ]);
        }

        // Пользователь авторизован, проверяем доступ к проекту
        Current::setProject($project);

        if (!$project->hasUser($user)) {
            if ($request->isPost()) {
                $project->setUser(new UserRole(
                    $user,
                    $invite->role,
                    ...$invite->languages
                ));

                $this->inviteService->revoke($invite);
                $this->modelManager->commit(new Transaction([$project]));
                return $response->withRedirect((new RouteUri($request))(''));
            }

            return $this->render($response, 'project/project_invite', [
                'request' => $request,
                'project' => $project,
                'invite' => $invite,
            ]);
        } else {
            return $response->withRedirect((new RouteUri($request))(''));
        }
    }
}