<?php
/**
 * Created for lokilizer
 * Date: 2025-02-16 22:57
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Batch;

use League\Plates\Engine;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use XAKEPEHOK\Lokilizer\Apps\Console\Handle\Tasks\BatchDeleteTaskCommand;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RenderAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Models\Project\Components\Role\Permission;

class BatchDeleteAction extends RenderAction
{
    public function __construct(
        Engine                      $renderer,
        private readonly BatchDeleteTaskCommand  $taskCommand,
    )
    {
        parent::__construct($renderer);
    }

    public function __invoke(Request $request, Response $response): Response
    {
        Current::guard(Permission::MANAGE_PROJECT_SETTINGS);

        $params = [
            'includeActual' => boolval($request->getParsedBodyParam('includeActual', false)),
            'includeOutdated' => boolval($request->getParsedBodyParam('includeOutdated', false)),
        ];

        $error = '';
        if ($request->isPost()) {
            $uuid = $this->taskCommand->publish([
                'title' => 'Batch delete',
                ...$params
            ]);

            return $response->withRedirect((new RouteUri($request))("progress/{$uuid}"));
        }

        return $this->render($response, 'batch/batch_delete', [
            'request' => $request,
            'form' => $params,
            'error' => $error,
        ]);

    }
}