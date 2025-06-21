<?php

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Project;

use DiBify\DiBify\Manager\ModelManager;
use DiBify\DiBify\Manager\Transaction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RenderAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Models\Project\Components\UserRole;
use XAKEPEHOK\Lokilizer\Models\Project\Db\ProjectRepo;
use XAKEPEHOK\Lokilizer\Models\Project\Project;
use League\Plates\Engine;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use XAKEPEHOK\Lokilizer\Services\InviteService\InviteService;

class ProjectInviteAction extends RenderAction
{

    public function __construct(
        Engine        $renderer,
        private readonly ProjectRepo $projectRepo,
        private readonly InviteService $inviteService,
        private readonly ModelManager $modelManager,
    )
    {
        parent::__construct($renderer);
    }

    public function __invoke(Request $request, Response $response): Response|ResponseInterface
    {
        /** @var Project $project */
        $project = $this->projectRepo->findById($request->getAttribute('projectId'));
        if (!$project) {
            return $this->render($response, 'errors/not_found', [
                'request' => $request,
                'error' => 'Project with passed id was not found',
            ]);
        }

        Current::setProject($project);
        $invite = $this->inviteService->getInviteById($request->getAttribute('inviteId'));

        if (!$invite) {
            return $this->render($response, 'errors/not_found', [
                'request' => $request,
                'error' => 'Invite link expired',
            ]);
        }

        if ($request->isPost()) {
            $project->setUser(new UserRole(
                Current::getUser(),
                $invite->role,
                ...$invite->languages
            ));

            $this->inviteService->revoke($invite);
            $this->modelManager->commit(new Transaction([$project]));
            return $response->withRedirect((new RouteUri($request))(''));
        }

        return $this->render($response, 'project/project_invite', [
            'request' => $request,
            'project' => $project,
            'invite' => $invite,
        ]);
    }
}