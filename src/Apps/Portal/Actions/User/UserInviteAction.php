<?php

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Actions\User;

use League\Plates\Engine;
use PrinsFrank\Standards\Language\LanguageAlpha2;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use Throwable;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\ApiRuntimeException;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RenderAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Components\PublicExceptionInterface;
use XAKEPEHOK\Lokilizer\Models\Localization\Db\RecordRepo;
use XAKEPEHOK\Lokilizer\Models\Project\Components\Role\Permission;
use XAKEPEHOK\Lokilizer\Models\Project\Components\Role\Role;
use XAKEPEHOK\Lokilizer\Services\InviteService\InviteService;
use function Sentry\captureException;

class UserInviteAction extends RenderAction
{

    public function __construct(
        Engine                         $engine,
        private readonly RecordRepo    $recordRepo,
        private readonly InviteService $inviteService,
    )
    {
        parent::__construct($engine);
    }

    public function __invoke(Request $request, Response $response): Response|ResponseInterface
    {
        Current::guard(Permission::MANAGE_USERS);

        $error = '';

        $languages = $this->recordRepo->fetchLanguages(true);

        $role = Role::tryFrom($request->getParsedBodyParam('role', Role::Guest->value));
        $selectedLanguages = [];

        if ($request->isPost()) {

            try {
                if (!$role) {
                    throw new ApiRuntimeException('Invalid role');
                }

                try {
                    $selectedLanguages = array_map(
                        fn(string $language) => LanguageAlpha2::from($language),
                        $request->getParsedBodyParam('selectedLanguages', [])
                    );
                } catch (Throwable) {
                    throw new ApiRuntimeException('Invalid language');
                }

                $selectedLanguages = array_filter(
                    $selectedLanguages,
                    fn(LanguageAlpha2 $language) => in_array($language, $languages)
                );

                if ($role->can(Permission::MANAGE_LANGUAGES)) {
                    $selectedLanguages = $this->recordRepo->fetchLanguages(true);
                }

                $this->inviteService->generate($role, ...$selectedLanguages);
                return $response->withRedirect((new RouteUri($request))('users'));
            } catch (PublicExceptionInterface $exception) {
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

        return $this->render($response, 'user/user_invite', [
            'request' => $request,
            'role' => $request->getParsedBodyParam('role', Role::Guest->value),
            'languages' => $languages,
            'selectedLanguages' => $selectedLanguages,
            'error' => $error,
        ]);
    }
}