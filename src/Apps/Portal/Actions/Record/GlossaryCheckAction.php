<?php
/**
 * Created for lokilizer
 * Date: 2025-01-29 19:19
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Record;

use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RenderAction;

class GlossaryCheckAction extends RenderAction
{

    public function __invoke(Request $request, Response $response): Response|ResponseInterface
    {
        $pairs = [];
        return $response->write(json_encode($pairs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

}