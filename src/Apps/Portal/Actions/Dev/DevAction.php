<?php
/**
 * Created for lokilizer
 * Date: 2025-02-13 21:14
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Dev;

use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RenderAction;

class DevAction extends RenderAction
{

    public function __invoke(Request $request, Response $response): Response|ResponseInterface
    {
        return $response->withJson([]);
    }
}