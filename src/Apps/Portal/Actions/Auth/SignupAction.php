<?php

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Auth;

use DateTimeZone;
use Dflydev\FigCookies\FigResponseCookies;
use Dflydev\FigCookies\Modifier\SameSite;
use Dflydev\FigCookies\SetCookie;
use DiBify\DiBify\Manager\ModelManager;
use DiBify\DiBify\Manager\Transaction;
use League\Plates\Engine;
use OTPHP\TOTP;
use PhpDto\EmailAddress\EmailAddress;
use PhpDto\EmailAddress\Exception\InvalidEmailAddressException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use Throwable;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\ApiRuntimeException;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RenderAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use XAKEPEHOK\Lokilizer\Components\PublicExceptionInterface;
use XAKEPEHOK\Lokilizer\Models\Project\Components\UserRole;
use XAKEPEHOK\Lokilizer\Models\Project\Db\ProjectRepo; // Добавляем
use XAKEPEHOK\Lokilizer\Models\Project\Project; // Добавляем
use XAKEPEHOK\Lokilizer\Models\User\Components\HumanName\HumanName;
use XAKEPEHOK\Lokilizer\Models\User\Components\Password\Password;
use XAKEPEHOK\Lokilizer\Models\User\Db\UserRepo;
use XAKEPEHOK\Lokilizer\Models\User\User;
use XAKEPEHOK\Lokilizer\Models\User\UserTOTP;
use XAKEPEHOK\Lokilizer\Services\InviteService\InviteService; // Добавляем
use XAKEPEHOK\Lokilizer\Services\TokenService;
use function Sentry\captureException;

class SignupAction extends RenderAction
{
    private ContainerInterface $container;

    public function __construct(
        Engine                        $engine,
        private readonly UserRepo     $userRepo,
        private readonly ModelManager $modelManager,
        private readonly TokenService $service,
        private readonly ProjectRepo $projectRepo, // Подключаем ProjectRepo
        private readonly InviteService $inviteService, // Подключаем InviteService
        ContainerInterface            $container,
    )
    {
        parent::__construct($engine);
        $this->container = $container;
    }

    public function __invoke(Request $request, Response $response): Response|ResponseInterface
    {
        $error = '';

        // --- Проверка GET-параметров для inviteFlag ---
        $inviteProjectId = $request->getQueryParam('invite_project_id'); // Получаем projectId из GET
        $inviteId = $request->getQueryParam('invite_id'); // Получаем inviteId из GET

        $inviteFlag = false;
        $pendingInvite = null; // Объект приглашения
        $pendingProject = null; // Объект проекта

        if ($inviteProjectId && $inviteId) {
            $pendingProject = $this->projectRepo->findById($inviteProjectId);
            $pendingInvite = $this->inviteService->getInviteByIdForProject($inviteId, $inviteProjectId); // Используем новый метод

            if ($pendingProject && $pendingInvite && $pendingInvite->isValid()) {
                $inviteFlag = true;
            }
            // Если приглашение недействительно, $inviteFlag останется false, и проверки $allowSignup/$allowedEmails сработают как обычно.
        }
        // --- /Проверка GET-параметров ---
        // ... (остальной код проверок, например, $allowSignup и $allowedEmails)
        $allowSignup = $this->container->get('ALLOW_SIGNUP');
        $allowedEmails = $this->container->get('SIGNUP_ALLOWED_EMAILS');

        if (!$inviteFlag && $allowSignup !== true) {
            $error = 'Registration is currently disabled.';
        } else if (!$inviteFlag && !empty($allowedEmails)) {
            $submittedEmail = $request->getParsedBodyParam('email', '');
            if (!empty($submittedEmail)) {
                $isAllowed = false;
                foreach ($allowedEmails as $pattern) {
                    $pattern = trim($pattern);
                    if ($this->emailMatchesPattern($submittedEmail, $pattern)) {
                        $isAllowed = true;
                        break;
                    }
                }
                if (!$isAllowed) {
                    $error = 'Registration is not allowed for this email address.';
                }
            }
        }

        if ($request->isPost() && !empty($error) && !$inviteFlag) {
             // ... (отрисовка формы с ошибкой, если проверки не прошли и это не по приглашению)
        }

        $provisioningUri = $request->getParsedBodyParam(
            'provisioningUri',
            (function () {
                $totp = TOTP::create();
                $totp->setLabel($_ENV['PROJECT_DOMAIN']);
                return (string)($totp->getProvisioningUri());
            })()
        );

        $secondFA = $request->getParsedBodyParam('secondFA', '');

        $user = null;
        if ($request->isPost() && empty($error)) {
            try {
                // ... (создание пользователя, проверки паролей и т.д.)
                $user = new User(
                    name: new HumanName(
                        $request->getParsedBodyParam('firstName', ''),
                        $request->getParsedBodyParam('lastName', ''),
                    ),
                    email: new EmailAddress($request->getParsedBodyParam('email', '')),
                    password: new Password($request->getParsedBodyParam('password', '')),
                );

                if ($this->userRepo->findByEmail($user->getEmail())) {
                    throw new ApiRuntimeException('User with this email already registered');
                }

                if ($request->getParsedBodyParam('password') !== $request->getParsedBodyParam('passwordRepeat')) {
                    throw new ApiRuntimeException('Password repeat not match');
                }

                $userTotp = new UserTOTP($provisioningUri);
                if (!$userTotp->verify($secondFA)) {
                    throw new ApiRuntimeException('Invalid 2FA key');
                }

                try {
                    $user->setTimezone(new DateTimeZone($request->getParsedBodyParam('timezone', 'UTC')));
                } catch (Throwable) {
                    throw new ApiRuntimeException('Invalid timezone');
                }

                $user->setTOTP($userTotp);
                $this->modelManager->commit(new Transaction([$user]));

                // --- Логика для приглашения (если inviteFlag = true) ---
                if ($inviteFlag && $pendingInvite && $pendingProject) {
                    // Проверяем, что пользователь всё ещё не в проекте (на всякий случай)
                    if (!$pendingProject->hasUser($user)) {
                        // --- Установим Current::setProject перед работой с проектом и приглашением ---
                        \XAKEPEHOK\Lokilizer\Components\Current::setProject($pendingProject);

                        // Добавляем пользователя в проект по данным приглашения
                        $pendingProject->setUser(new UserRole(
                            $user,
                            $pendingInvite->role,
                            ...$pendingInvite->languages
                        ));

                        // Отзываем приглашение (теперь Current::getProject() инициализирован)
                        $this->inviteService->revoke($pendingInvite);
                        // Сохраняем изменения в проекте (теперь Current::getProject() инициализирован)
                        $this->modelManager->commit(new Transaction([$pendingProject]));
                    }
                }

                // --- ИЗМЕНЕНО: Перенаправление ---
                // Если была информация о приглашении, перенаправляем на главную страницу проекта
                // В противном случае, на главную страницу приложения
                $redirectUri = (new RouteUri($request))(''); // <-- Это /project/{projectId}/ для проекта или / для главной
                if ($inviteFlag && $pendingProject) {
                     // Явно указываем путь к проекту
                     $redirectUri = $request->getUri()->withPath("/project/{$pendingProject->id()->get()}/")->withQuery('');
                }
                // --- /ИЗМЕНЕНО ---

                return FigResponseCookies::set(
                    $response->withRedirect($redirectUri),
                    $this->service->getCookieToken($user)
                );

            } catch (PublicExceptionInterface|InvalidEmailAddressException $exception) {
                $error = $exception->getMessage();
            } catch (Throwable $throwable) {
                if ($_ENV['APP_ENV'] === 'dev') {
                    // Выводим полное сообщение об ошибке в dev-режиме
                    $error = 'Internal ServerError: ' . $throwable->getMessage() . ' in ' . $throwable->getFile() . ' on line ' . $throwable->getLine();
                } else {
                    $error = 'Internal Server Error';
                }
                captureException($throwable);
            }
        }

        // --- ИЗМЕНЕНО: Передача inviteFlag в шаблон ---
        // При GET-запросе (показ формы) или при POST с ошибками
        return $this->render($response, 'auth/signup_index', [
            'request' => $request,
            'email' => $user?->getEmail() ?? $request->getParsedBodyParam('email', ''),
            'firstName' => $request->getParsedBodyParam('firstName', ''),
            'lastName' => $request->getParsedBodyParam('lastName', ''),
            'password' => $request->getParsedBodyParam('password', ''),
            'passwordRepeat' => $request->getParsedBodyParam('passwordRepeat', ''),
            'timezone' => $request->getParsedBodyParam('timezone', 'UTC'),
            'secondFA' => $secondFA,
            'provisioningUri' => $provisioningUri,
            'error' => $error,
            'allowSignup' => $allowSignup,
            'inviteFlag' => $inviteFlag, // <-- Передаём флаг в шаблон
        ]);
        // --- /ИЗМЕНЕНО ---
    }

    private function emailMatchesPattern($email, $pattern): bool
    {
        $pattern = preg_quote($pattern, '#');
        $pattern = str_replace('\*', '.*', $pattern);
        $pattern = '#^' . $pattern . '$#i';
        return (bool) preg_match($pattern, $email);
    }
}