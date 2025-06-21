<?php

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Auth;

use Dflydev\FigCookies\FigResponseCookies;
use Dflydev\FigCookies\SetCookie;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;

class LogoutAction
{

    public function __invoke(Request $request, Response $response): Response|ResponseInterface
    {
        return FigResponseCookies::set(
            $response->withRedirect((new RouteUri($request))('')),
            SetCookie::create('uuid', '')
                ->withDomain($_ENV['PROJECT_DOMAIN'])
                ->withHttpOnly()
                ->withSecure()
                ->withExpires(-1)
                ->withPath("/")
        );
    }
}