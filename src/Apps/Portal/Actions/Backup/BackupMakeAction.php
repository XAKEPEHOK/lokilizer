<?php
/**
 * Created for lokilizer
 * Date: 2025-02-13 19:49
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Backup;

use League\Plates\Engine;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use XAKEPEHOK\Lokilizer\Apps\Console\Handle\Tasks\BackupMakeTaskCommand;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RenderAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Models\Project\Components\Role\Permission;

class BackupMakeAction extends RenderAction
{

    public function __construct(
        private BackupMakeTaskCommand $taskCommand,
        Engine                        $renderer
    )
    {
        parent::__construct($renderer);
    }


    public function __invoke(Request $request, Response $response): Response|ResponseInterface
    {
        Current::guard(Permission::BACKUP_MAKE);

        $uuid = $this->taskCommand->publish([
            'title' => 'Backup'
        ]);
        return $response->withRedirect((new RouteUri($request))("progress/{$uuid}"));
    }
}