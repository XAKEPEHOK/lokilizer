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
use XAKEPEHOK\Lokilizer\Models\User\Db\UserRepo;
use XAKEPEHOK\Lokilizer\Models\User\User;
use XAKEPEHOK\Lokilizer\Services\TokenService;
use Dflydev\FigCookies\FigResponseCookies;
use Dflydev\FigCookies\SetCookie;
use League\Plates\Engine;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use function Sentry\captureException;

class LoginAction extends RenderAction
{
    public function __construct(
        Engine $engine,
        private TokenService $tokenService,
        private UserRepo $userRepo,
    )
    {
        parent::__construct($engine);
    }

    public function __invoke(Request $request, Response $response): Response|ResponseInterface
    {
        $error = '';

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

                return FigResponseCookies::set(
                    $response->withRedirect((new RouteUri($request))('')),
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

        return $this->render($response, 'home/home_index', [
            'request' => $request,
            'form' => $params,
            'error' => $error,
        ]);
    }
}