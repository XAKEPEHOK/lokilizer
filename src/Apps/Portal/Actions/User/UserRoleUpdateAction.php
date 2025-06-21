<?php

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Actions\User;

use DiBify\DiBify\Manager\ModelManager;
use DiBify\DiBify\Manager\Transaction;
use League\Plates\Engine;
use PhpDto\EmailAddress\Exception\InvalidEmailAddressException;
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
use XAKEPEHOK\Lokilizer\Models\Project\Components\UserRole;
use XAKEPEHOK\Lokilizer\Models\User\User;
use function Sentry\captureException;

class UserRoleUpdateAction extends RenderAction
{

    public function __construct(
        Engine                        $engine,
        private readonly RecordRepo $recordRepo,
        private readonly ModelManager $modelManager,
    )
    {
        parent::__construct($engine);
    }

    public function __invoke(Request $request, Response $response): Response|ResponseInterface
    {
        Current::guard(Permission::MANAGE_USERS);

        $error = '';

        $project = Current::getProject();
        $userRole = null;
        foreach ($project->getUsers() as $role) {
            if ($role->user->id()->isEqual($request->getAttribute('id'))) {
                $userRole = $role;
            }
        }

        if (!$userRole) {
            return $this->render($response, 'errors/not_found', [
                'request' => $request,
                'error' => 'User with passed id was not found in project',
            ]);
        }

        /** @var User $user */
        $user = $userRole->user->getModel();

        $languages = $this->recordRepo->fetchLanguages(true);
        $selectedLanguages = $userRole->languages;
        if ($userRole->can(Permission::MANAGE_LANGUAGES)) {
            $selectedLanguages = $this->recordRepo->fetchLanguages(true);
        }

        if ($request->isPost()) {

            if ($request->getParsedBodyParam('delete') === '1') {
                $project->removeUser($user);
                $this->modelManager->commit(new Transaction([$project]));
                return $response->withRedirect((new RouteUri($request))('users'));
            }

            try {
                $role = Role::tryFrom($request->getParsedBodyParam('role', ''));
                if (!$role) {
                    throw new ApiRuntimeException('Invalid role');
                }

                try {
                    $selectedLanguages = array_map(
                        fn (string $language) => LanguageAlpha2::from($language),
                        $request->getParsedBodyParam('selectedLanguages', [])
                    );
                } catch (Throwable) {
                    throw new ApiRuntimeException('Invalid language');
                }

                $selectedLanguages = array_filter(
                    $selectedLanguages,
                    fn (LanguageAlpha2 $language) => in_array($language, $languages)
                );

                if ($userRole->can(Permission::MANAGE_LANGUAGES)) {
                    $selectedLanguages = $this->recordRepo->fetchLanguages(true);
                }


                $role = new UserRole($user, $role, ...$selectedLanguages);
                $project->setUser($role);

                $this->modelManager->commit(new Transaction([$project]));
                return $response->withRedirect((new RouteUri($request))('users'));
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

        return $this->render($response, 'user/user_role_update', [
            'request' => $request,
            'user' => $user,
            'role' => $request->getParsedBodyParam('role', $userRole->role->value),
            'languages' => $languages,
            'selectedLanguages' => $selectedLanguages,
            'error' => $error,
        ]);
    }
}