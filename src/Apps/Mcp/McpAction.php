<?php

namespace XAKEPEHOK\Lokilizer\Apps\Mcp;

use Mcp\Server\Transport\Http\Middleware\CorsMiddleware;
use Mcp\Server\Transport\Http\Middleware\DnsRebindingProtectionMiddleware;
use Mcp\Server\Transport\StreamableHttpTransport;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Models\Project\Db\ProjectRepo;

class McpAction
{

    public function __construct(
        private readonly ProjectRepo $projectRepo,
        private readonly McpServerFactory $serverFactory,
    )
    {
    }

    public function __invoke(Request $request, Response $response): Response|ResponseInterface
    {
        $project = $this->projectRepo->findById($request->getAttribute('projectId'));

        if (!preg_match('/^Bearer\s+(.+)$/i', $request->getHeaderLine('Authorization'), $matches)
            || $project->getMcpToken() === null
            || $project->getMcpToken() !== $matches[1]
        ) {
            return $response
                ->withStatus(401)
                ->withHeader('WWW-Authenticate', 'Bearer realm="Lokilizer MCP"');
        }

        Current::setProject($project);

        $transport = new StreamableHttpTransport(
            $request,
            middleware: [
                new CorsMiddleware(),
                new DnsRebindingProtectionMiddleware(allowedHosts: [$_ENV['PROJECT_DOMAIN']]),
            ]
        );

        return $this->serverFactory->create()->run($transport);
    }

}
