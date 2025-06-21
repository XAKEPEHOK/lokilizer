<?php
/**
 * Created for lokilizer
 * Date: 2025-02-05 23:42
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Actions\User;

use League\Plates\Engine;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RenderAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Models\Localization\Db\RecordRepo;
use XAKEPEHOK\Lokilizer\Models\Project\Components\Role\Permission;
use XAKEPEHOK\Lokilizer\Services\InviteService\InviteService;

class UserListAction extends RenderAction
{

    public function __construct(
        private RecordRepo $recordRepo,
        private InviteService $inviteService,
        Engine $renderer
    )
    {
        parent::__construct($renderer);
    }

    public function __invoke(Request $request, Response $response): Response|ResponseInterface
    {
        Current::guard(Permission::MANAGE_USERS);
        $project = Current::getProject();

        if ($request->isPost()) {
            $this->inviteService->revoke($request->getParsedBodyParam('revoke'));
            return $response->withRedirect((new RouteUri($request))('users'));
        }

        return $this->render($response, 'user/user_list', [
            'request' => $request,
            'languages' => $this->recordRepo->fetchLanguages(true),
            'invites' => $this->inviteService->getInvites(),
            'users' => $project->getUsers(),
        ]);
    }
}