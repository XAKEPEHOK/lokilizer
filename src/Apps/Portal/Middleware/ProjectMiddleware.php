<?php

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Middleware;

use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Models\LLM\Db\LLMEndpointRepo;
use XAKEPEHOK\Lokilizer\Models\Project\Project;
use XAKEPEHOK\Lokilizer\Models\Project\Db\ProjectRepo;
use XAKEPEHOK\Lokilizer\Models\User\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpException;
use Slim\Http\ServerRequest;
use Slim\Routing\RouteContext;

class ProjectMiddleware
{

    public function __construct(
        private ProjectRepo $projectRepo,
        private LLMEndpointRepo $llmEndpointRepo,
    )
    {
    }

    public function __invoke(ServerRequest $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $exception403 = new HttpException($request, 'Project not found or you have no access to it', 403);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();

        /** @var Project $project */
        $project = $this->projectRepo->findById($route->getArgument('projectId'), $exception403);
        Current::setProject($project);

        Current::setLLMEndpoints(...$this->llmEndpointRepo->findAll());

        /** @var User $user */
        $user = $request->getAttribute('user');

        if (!$project->hasUser($user)) {
            throw $exception403;
        }

        Current::setProject($project);

        return $handler->handle(
            $request
                ->withAttribute('project', $project)
                ->withAttribute('startedAt', microtime(true))
        );
    }

}