<?php

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Components;

use XAKEPEHOK\Lokilizer\Models\Project\Project;
use Psr\Http\Message\UriInterface;
use Slim\Http\ServerRequest;

class RouteUri
{

    private ServerRequest $request;

    public function __construct(ServerRequest $request)
    {
        $this->request = $request;
    }

    public function __invoke(string $route = null, bool $withScope = true): UriInterface
    {
        if ($route === null) {
            return $this->request->getUri()->withQuery('');
        }

        $uri = $this->request->getUri()->withQuery('');

        /** @var Project $project */
        $project = $this->request->getAttribute('project');
        if ($project && $withScope) {
            return $uri->withPath("/project/{$project->id()}/" . $route);
        }

        return $uri->withPath("/" . $route);
    }

    public static function home(): string
    {
        $port = $_ENV['APP_ENV'] === 'prod' ? '' : ":{$_ENV['APP_PORT']}";
        return "https://{$_ENV['PROJECT_DOMAIN']}{$port}/";
    }

}