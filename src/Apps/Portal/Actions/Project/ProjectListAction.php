<?php

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Project;

use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RenderAction;
use XAKEPEHOK\Lokilizer\Models\Project\Db\ProjectRepo;
use XAKEPEHOK\Lokilizer\Models\User\User;
use League\Plates\Engine;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;

class ProjectListAction extends RenderAction
{

    public function __construct(
        Engine                       $renderer,
        private readonly ProjectRepo $projectRepo,
    )
    {
        parent::__construct($renderer);
    }

    public function __invoke(Request $request, Response $response): Response|ResponseInterface
    {
        /** @var User $user */
        $user = $request->getAttribute('user');
        $projects = $this->projectRepo->findByUser($user);
        return $this->render($response, 'project/project_list', [
            'request' => $request,
            'projects' => $projects,
        ]);
    }
}