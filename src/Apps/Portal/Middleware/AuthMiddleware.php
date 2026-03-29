<?php

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Middleware;

use League\Plates\Engine;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Models\User\User;
use XAKEPEHOK\Lokilizer\Services\TokenService;
use Dflydev\FigCookies\Cookies;
use DiBify\DiBify\Manager\ModelManager;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpException;
use Slim\Http\ServerRequest;
use XAKEPEHOK\Lokilizer\Models\Project\Db\ProjectRepo; // Добавляем репозиторий проекта
use XAKEPEHOK\Lokilizer\Services\InviteService\InviteService; // Добавляем сервис приглашений

class AuthMiddleware extends RenderMiddleware
{
    private ContainerInterface $container;

    public function __construct(
        private readonly TokenService $tokenService,
        private readonly ModelManager $modelManager,
        private readonly ProjectRepo $projectRepo, // Включаем ProjectRepo
        private readonly InviteService $inviteService, // Включаем InviteService
        Engine                        $engine,
        ContainerInterface            $container,
    )
    {
        parent::__construct($engine);
        $this->container = $container;
    }

    public function __invoke(ServerRequest $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $uuid = Cookies::fromRequest($request)->get('uuid')?->getValue() ?? '';
        if ($_ENV['APP_ENV'] === 'dev') {
            error_log("DEBUG AuthMiddleware: UUID from cookie: '$uuid'");
        }
        $user = $this->tokenService->parseCookieToken($uuid);

        // --- Проверка сессии для неавторизованного пользователя ---
        $inviteFlag = false;
        $pendingInviteData = $_SESSION['pending_invite'] ?? null;

        if ($_ENV['APP_ENV'] === 'dev') {
            error_log("DEBUG AuthMiddleware: Session data: " . print_r($pendingInviteData, true));
        }

        if ($pendingInviteData && is_array($pendingInviteData)) {
            $sessProjectId = $pendingInviteData['projectId'] ?? null;
            $sessInviteId = $pendingInviteData['inviteId'] ?? null;

            if ($_ENV['APP_ENV'] === 'dev') {
                error_log("DEBUG AuthMiddleware: Session InviteId: $sessInviteId, ProjectId: $sessProjectId");
            }

            if ($sessProjectId && $sessInviteId) {
                $project = $this->projectRepo->findById($sessProjectId);
                $invite = $this->inviteService->getInviteByIdForProject($sessInviteId, $sessProjectId);

                if ($_ENV['APP_ENV'] === 'dev') {
                    error_log("DEBUG AuthMiddleware: Project found: " . ($project ? 'YES' : 'NO'));
                    error_log("DEBUG AuthMiddleware: Invite found: " . ($invite ? 'YES' : 'NO'));

                    if ($invite) {
                        error_log("DEBUG AuthMiddleware: Invite is valid: " . ($invite->isValid() ? 'YES' : 'NO'));
                    }
                }

                if ($project && $invite && $invite->isValid()) {
                    $inviteFlag = true;
                    if ($_ENV['APP_ENV'] === 'dev') {
                        error_log("DEBUG AuthMiddleware: inviteFlag set to TRUE");
                    }
                } else {
                    if ($_ENV['APP_ENV'] === 'dev') {
                        error_log("DEBUG AuthMiddleware: inviteFlag stays FALSE, clearing session");
                    }
                    unset($_SESSION['pending_invite']);
                }
            } else {
                if ($_ENV['APP_ENV'] === 'dev') {
                    error_log("DEBUG AuthMiddleware: Invalid session data format, clearing session");
                }
                unset($_SESSION['pending_invite']);
            }
        } else {
            error_log("DEBUG AuthMiddleware: No pending invite in session");
        }
        // --- /Проверка сессии ---

        $exception403 = new HttpException($request, 'Not authorized', 403);

        if ($user === null) {
            $allowSignup = $this->container->get('ALLOW_SIGNUP');
            $originalUri = $request->getUri()->__toString();
            $referrerHeader = $request->getHeaderLine('Referer');

            if ($_ENV['APP_ENV'] === 'dev') {
                error_log("DEBUG AuthMiddleware: Rendering home_index with inviteFlag: " . ($inviteFlag ? 'TRUE' : 'FALSE'));
            }

            // Передаём inviteFlag в шаблон
            return $this->render($request, 'home/home_index', [
                'request' => $request,
                'allowSignup' => $allowSignup,
                'inviteFlag' => $inviteFlag, // <-- Передаём флаг
                'debugInfo' => [
                    'originalUri' => $originalUri,
                    'referrerHeader' => $referrerHeader,
                ],
            ]);
        }

        // --- Код для авторизованного пользователя ---
        date_default_timezone_set($user->getTimezone()->getName());

        /** @var User $user */
        $user = $this->modelManager->refreshOne($user);

        Current::setUser($user);

        // Если пользователь авторизовался и в сессии были данные о приглашении,
        // возможно, нужно автоматически перенаправить его на страницу принятия приглашения
        if ($pendingInviteData) {
            $sessProjectId = $pendingInviteData['projectId'] ?? null;
            $sessInviteId = $pendingInviteData['inviteId'] ?? null;

            // Проверяем валидность приглашения снова
            $project = $this->projectRepo->findById($sessProjectId);
            $invite = $this->inviteService->getInviteById($sessInviteId);

            if ($project && $invite && $invite->isValid()) {
                // Очищаем сессию
                unset($_SESSION['pending_invite']);
                // Перенаправляем на страницу принятия приглашения
                $newUri = $request->getUri()->withPath("/project/{$sessProjectId}/invite/{$sessInviteId}");
                return $response->withRedirect($newUri);
            } else {
                // Приглашение недействительно, очищаем сессию
                unset($_SESSION['pending_invite']);
            }
        }

        return $handler->handle($request->withAttribute('user', $user));
    }
}