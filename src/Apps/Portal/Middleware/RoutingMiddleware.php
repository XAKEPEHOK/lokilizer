<?php

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Http\ServerRequest;

readonly class RoutingMiddleware
{


    public function __invoke(ServerRequest $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = $request->withUri($request->getUri()
            ->withHost($_ENV['PROJECT_DOMAIN'])
            ->withPort($_ENV['APP_ENV'] === 'prod' ? null : $_ENV['APP_PORT'])
            ->withScheme('https')
        );

        return $handler->handle($request);
    }

}