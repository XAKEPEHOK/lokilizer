<?php

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Auth;

use Dflydev\FigCookies\Modifier\SameSite;
use PhpDto\EmailAddress\EmailAddress;
use PhpDto\EmailAddress\Exception\InvalidEmailAddressException;
use Throwable;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\ApiRuntimeException;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RenderAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use XAKEPEHOK\Lokilizer\Components\PublicExceptionInterface;
use XAKEPEHOK\Lokilizer\Models\Project\Db\ProjectRepo;
use XAKEPEHOK\Lokilizer\Models\User\Db\UserRepo;
use XAKEPEHOK\Lokilizer\Models\User\User;
use XAKEPEHOK\Lokilizer\Services\InviteService\InviteService;
use XAKEPEHOK\Lokilizer\Services\TokenService;
use Dflydev\FigCookies\FigResponseCookies;
use Dflydev\FigCookies\SetCookie;
use League\Plates\Engine;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use function Sentry\captureException;

class LoginAction extends RenderAction
{
    private ContainerInterface $container;

    public function __construct(
        Engine $engine,
        private TokenService $tokenService,
        private UserRepo $userRepo,
        private ProjectRepo $projectRepo, // Включаем ProjectRepo
        private InviteService $inviteService, // Включаем InviteService
        ContainerInterface $container,
    )
    {
        parent::__construct($engine);
        $this->container = $container;
    }

    public function __invoke(Request $request, Response $response): Response|ResponseInterface
    {
        $error = '';

        // --- Проверка сессии для inviteFlag ---
        $inviteFlag = false;
        $pendingInviteData = $_SESSION['pending_invite'] ?? null;

        if ($pendingInviteData && is_array($pendingInviteData)) {
            $sessProjectId = $pendingInviteData['projectId'] ?? null;
            $sessInviteId = $pendingInviteData['inviteId'] ?? null;

            if ($sessProjectId && $sessInviteId) {
                $project = $this->projectRepo->findById($sessProjectId);
                // Используем getInviteByIdForProject, так как Current::getProject() может быть не установлен
                $invite = $this->inviteService->getInviteByIdForProject($sessInviteId, $sessProjectId);

                if ($project && $invite && $invite->isValid()) {
                    $inviteFlag = true;
                } else {
                    // Приглашение недействительно, очищаем сессию
                    unset($_SESSION['pending_invite']);
                }
            } else {
                unset($_SESSION['pending_invite']);
            }
        }
        // --- /Проверка сессии ---

        $params = [
            'email' => $request->getParsedBodyParam('email', ''),
            'password' => $request->getParsedBodyParam('password', ''),
            'otp' => $request->getParsedBodyParam('otp', ''),
        ];

        if ($request->isPost()) {
            try {
                /** @var User $user */
                $user = $this->userRepo->findByEmail(new EmailAddress($params['email']));

                if (!$user) {
                    throw new ApiRuntimeException('User with passed email does not exist');
                }

                $isDev = $_ENV['APP_ENV'] === 'dev' && $params['otp'] === '000000';
                $isOtpValid = $user->getTOTP()->verify($params['otp']) || $isDev;
                $isPasswordValid = $user->getPassword()->verify($params['password']);

                if (!$isOtpValid || !$isPasswordValid) {
                    throw new ApiRuntimeException('Invalid password or OTP');
                }

                // --- Проверка GET-параметров для перенаправления после входа ---
                $redirectAfterLogin = false;
                $redirectProjectId = null;
                $redirectInviteId = null;

                $pendingInviteProjectId = $request->getQueryParam('pending_invite_project_id');
                $pendingInviteId = $request->getQueryParam('pending_invite_id');

                if ($pendingInviteProjectId && $pendingInviteId) {
                    // Проверяем действительность приглашения по полученным ID
                    $project = $this->projectRepo->findById($pendingInviteProjectId);
                    $invite = $this->inviteService->getInviteByIdForProject($pendingInviteId, $pendingInviteProjectId); // Используем метод без Current

                    if ($project && $invite && $invite->isValid()) {
                        $redirectAfterLogin = true;
                        $redirectProjectId = $pendingInviteProjectId;
                        $redirectInviteId = $pendingInviteId;
                    }
                    // Если приглашение недействительно, просто продолжаем стандартное перенаправление
                }

                // Если пользователь успешно вошёл и был pending_invite, обрабатываем его
                if ($pendingInviteData) {
                    $sessProjectId = $pendingInviteData['projectId'] ?? null;
                    $sessInviteId = $pendingInviteData['inviteId'] ?? null;

                    $project = $this->projectRepo->findById($sessProjectId);
                    // Этот вызов может потребовать Current::getProject, но если пользователь вошёл,
                    // возможно, он уже установлен где-то. Или используем getInviteByIdForProject с sessProjectId.
                    $invite = $this->inviteService->getInviteById($sessInviteId);

                    if ($project && $invite && $invite->isValid() && !$project->hasUser($user)) {
                        // Принимаем приглашение
                        $project->setUser(new UserRole(
                            $user,
                            $invite->role,
                            ...$invite->languages
                        ));
                        // Current::setProject($project); // Убедимся, что Current::getProject() будет работать для revoke
                        $this->inviteService->revoke($invite);
                        $this->modelManager->commit(new Transaction([$project]));
                        // Очищаем сессию
                        unset($_SESSION['pending_invite']);
                        // Перенаправляем на проект (или на главную страницу проекта)
                        $redirectResponse = $response->withRedirect((new RouteUri($request))(''));
                        return FigResponseCookies::set(
                            $redirectResponse,
                            $this->tokenService->getCookieToken($user)
                        );
                    } else {
                        // Приглашение больше не корректно или пользователь уже в проекте
                        unset($_SESSION['pending_invite']);
                    }
                }

                // --- Перенаправление после входа ---
                if ($redirectAfterLogin && $redirectProjectId && $redirectInviteId) {
                    // Перенаправление на страницу принятия приглашения
                    $redirectUri = $request->getUri()->withPath("/project/{$redirectProjectId}/invite/{$redirectInviteId}")->withQuery('');
                    $redirectResponse = $response->withRedirect($redirectUri);
                } else {
                    // Стандартное перенаправление
                    $redirectResponse = $response->withRedirect((new RouteUri($request))(''));
                }

                return FigResponseCookies::set(
                    //$response->withRedirect((new RouteUri($request))('')),
                    $redirectResponse,
                    $this->tokenService->getCookieToken($user)
                );

            } catch (PublicExceptionInterface|InvalidEmailAddressException $exception) {
                $error = $exception->getMessage();
            } catch (Throwable $throwable) {
                if ($_ENV['APP_ENV'] === 'dev') {
                    $error = 'Internal ServerError: ' . $throwable->getMessage();
                } else {
                    $error = 'Internal Server Error';
                }
                captureException($throwable);
            }
        }

        $allowSignup = $this->container->get('ALLOW_SIGNUP');
        $originalUri = $request->getUri()->__toString();
        $referrerHeader = $request->getHeaderLine('Referer');

        return $this->render($response, 'home/home_index', [
            'request' => $request,
            'form' => $params,
            'error' => $error,
            'allowSignup' => $allowSignup,
            'inviteFlag' => $inviteFlag, // <-- Передаём флаг
            'debugInfo' => [
                'originalUri' => $originalUri,
                'referrerHeader' => $referrerHeader,
            ],
        ]);
    }
}