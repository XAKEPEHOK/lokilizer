<?php

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Actions;

use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RenderAction;

class GettingStartedAction extends RenderAction
{

    public function __invoke(Request $request, Response $response): Response|ResponseInterface
    {
        return $this->render($response, 'home/getting_started', [
            'request' => $request,
        ]);
    }
}