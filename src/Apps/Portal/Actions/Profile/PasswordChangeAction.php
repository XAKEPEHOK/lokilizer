<?php

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Profile;

use Dflydev\FigCookies\FigResponseCookies;
use DiBify\DiBify\Manager\ModelManager;
use DiBify\DiBify\Manager\Transaction;
use League\Plates\Engine;
use PhpDto\EmailAddress\Exception\InvalidEmailAddressException;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use Throwable;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\ApiRuntimeException;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RenderAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Components\PublicExceptionInterface;
use XAKEPEHOK\Lokilizer\Models\User\Components\Password\Password;
use XAKEPEHOK\Lokilizer\Services\TokenService;
use function Sentry\captureException;

class PasswordChangeAction extends RenderAction
{

    public function __construct(
        Engine                        $engine,
        private readonly ModelManager $modelManager,
        private readonly TokenService $service,
    )
    {
        parent::__construct($engine);
    }

    public function __invoke(Request $request, Response $response): Response|ResponseInterface
    {
        $error = '';

        $user = Current::getUser();

        if ($request->isPost()) {
            try {
                if (!$user->getPassword()->verify($request->getParsedBodyParam('currentPassword'))) {
                    throw new ApiRuntimeException('Invalid current password');
                }

                if ($request->getParsedBodyParam('newPassword') !== $request->getParsedBodyParam('passwordRepeat')) {
                    throw new ApiRuntimeException('Password repeat not match');
                }

                $user->setPassword(new Password($request->getParsedBodyParam('newPassword')));

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

        return $this->render($response, 'profile/profile_password', [
            'request' => $request,
            'currentPassword' => $request->getParsedBodyParam('currentPassword', ''),
            'newPassword' => $request->getParsedBodyParam('newPassword', ''),
            'passwordRepeat' => $request->getParsedBodyParam('passwordRepeat', ''),
            'error' => $error,
        ]);
    }
}