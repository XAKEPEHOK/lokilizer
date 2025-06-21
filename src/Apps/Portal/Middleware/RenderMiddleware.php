<?php
/**
 * Created for lokilizer
 * Date: 2025-01-20 14:26
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Middleware;

use League\Plates\Engine;
use Nyholm\Psr7\Stream;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\ServerRequest;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Response;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;

abstract class RenderMiddleware
{

    public function __construct(
        private Engine $renderer,
    )
    {
    }

    protected function render(ServerRequest $request, string $template, array $data): ResponseInterface
    {
        $stream = Stream::create($this->renderer->render($template, [
            'request' => $request,
            'route' => new RouteUri($request),
            ...$data,
        ]));
        $response = new Response(404, null, $stream);
        return new \Slim\Http\Response($response, new StreamFactory());
    }

}