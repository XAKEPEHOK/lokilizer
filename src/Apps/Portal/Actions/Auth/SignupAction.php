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
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use Throwable;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\ApiRuntimeException;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RenderAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use XAKEPEHOK\Lokilizer\Components\PublicExceptionInterface;
use XAKEPEHOK\Lokilizer\Models\User\Components\HumanName\HumanName;
use XAKEPEHOK\Lokilizer\Models\User\Components\Password\Password;
use XAKEPEHOK\Lokilizer\Models\User\Db\UserRepo;
use XAKEPEHOK\Lokilizer\Models\User\User;
use XAKEPEHOK\Lokilizer\Models\User\UserTOTP;
use XAKEPEHOK\Lokilizer\Services\TokenService;
use function Sentry\captureException;

class SignupAction extends RenderAction
{

    public function __construct(
        Engine                        $engine,
        private readonly UserRepo     $userRepo,
        private readonly ModelManager $modelManager,
        private readonly TokenService $service,
    )
    {
        parent::__construct($engine);
    }

    public function __invoke(Request $request, Response $response): Response|ResponseInterface
    {
        $error = '';

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
        if ($request->isPost()) {
            try {
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

                return FigResponseCookies::set(
                    $response->withRedirect((new RouteUri($request))('')),
                    $this->service->getCookieToken($user)
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

        return $this->render($response, 'auth/signup_index', [
            'request' => $request,
            'email' => $user?->getEmail() ?? '',
            'firstName' => $request->getParsedBodyParam('firstName', ''),
            'lastName' => $request->getParsedBodyParam('lastName', ''),
            'password' => $request->getParsedBodyParam('password', ''),
            'passwordRepeat' => $request->getParsedBodyParam('passwordRepeat', ''),
            'timezone' => $request->getParsedBodyParam('timezone', 'UTC'),
            'secondFA' => $secondFA,
            'provisioningUri' => $provisioningUri,
            'error' => $error,
        ]);
    }
}