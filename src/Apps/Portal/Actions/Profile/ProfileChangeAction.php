<?php

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Profile;

use DateTimeZone;
use DiBify\DiBify\Manager\ModelManager;
use DiBify\DiBify\Manager\Transaction;
use League\Plates\Engine;
use PhpDto\EmailAddress\EmailAddress;
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
use XAKEPEHOK\Lokilizer\Models\User\Components\HumanName\HumanName;
use XAKEPEHOK\Lokilizer\Models\User\Components\Theme;
use function Sentry\captureException;

class ProfileChangeAction extends RenderAction
{

    public function __construct(
        Engine                        $engine,
        private readonly ModelManager $modelManager,
    )
    {
        parent::__construct($engine);
    }

    public function __invoke(Request $request, Response $response): Response|ResponseInterface
    {
        $error = '';

        $user = Current::getUser();

        $theme = $user->getTheme();

        if ($request->isPost()) {
            try {
                $user->setEmail(new EmailAddress($request->getParsedBodyParam('email')));
                $user->setName(new HumanName(
                    firstName: $request->getParsedBodyParam('firstName'),
                    lastName: $request->getParsedBodyParam('lastName')
                ));

                try {
                    $user->setTimezone(new DateTimeZone($request->getParsedBodyParam('timezone', 'UTC')));
                } catch (Throwable) {
                    throw new ApiRuntimeException('Invalid timezone');
                }

                $theme = Theme::tryFrom($request->getParsedBodyParam('theme', $user->getTheme()->value));
                if (is_null($theme)) {
                    throw new ApiRuntimeException('Invalid theme');
                }

                $user->setTheme($theme);

                $this->modelManager->commit(new Transaction([$user]));

                $response->withRedirect((new RouteUri($request))('profile'));
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

        return $this->render($response, 'profile/profile_index', [
            'request' => $request,
            'email' => $user->getEmail()->get(),
            'firstName' => $request->getParsedBodyParam('firstName', $user->getName()->getFirstName()),
            'lastName' => $request->getParsedBodyParam('lastName', $user->getName()->getLastName()),
            'timezone' => $request->getParsedBodyParam('timezone', $user->getTimezone()->getName()),
            'theme' => $theme,
            'error' => $error,
        ]);
    }
}