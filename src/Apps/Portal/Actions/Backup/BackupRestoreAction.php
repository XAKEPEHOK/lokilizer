<?php
/**
 * Created for sr-app
 * Date: 2025-01-15 01:22
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Backup;

use League\Plates\Engine;
use Nyholm\Psr7\UploadedFile;
use RuntimeException;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use Symfony\Component\Filesystem\Filesystem;
use XAKEPEHOK\Lokilizer\Apps\Console\Handle\Tasks\BackupRestoreTaskCommand;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RenderAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Models\Project\Components\Role\Permission;
use XAKEPEHOK\Path\Path;

class BackupRestoreAction extends RenderAction
{

    public function __construct(
        Engine                        $renderer,
        private readonly Filesystem $filesystem,
        private readonly BackupRestoreTaskCommand $taskCommand,
    )
    {
        parent::__construct($renderer);
    }

    public function __invoke(Request $request, Response $response): Response
    {
        Current::guard(Permission::FILE_UPLOADS);

        $params = [
            'llm' => boolval($request->getParsedBodyParam('llm')),
            'glossary' => boolval($request->getParsedBodyParam('glossary')),
            'records' => boolval($request->getParsedBodyParam('records')),
        ];

        $error = '';
        if ($request->isPost()) {
            try {
                /** @var UploadedFile $file */
                $file = $request->getUploadedFiles()['file'] ?? null;
                if (is_null($file)) {
                    throw new RuntimeException('No file uploaded');
                }

                if ($file->getError() !== UPLOAD_ERR_OK) {
                    throw new RuntimeException('File uploading error');
                }

                $content = $file->getStream()->getContents();
                $directory = Path::root()->down('runtime/uploads/')->down(Current::getProject()->id());
                $path = $directory->down(md5($content) . '.json');

                if (!$this->filesystem->exists($directory)) {
                    $this->filesystem->mkdir($directory);
                }

                $file->moveTo(strval($path));

                $uuid = $this->taskCommand->publish([
                    'title' => 'Restore backup ' . $file->getClientFilename(),
                    'path' => $path,
                    ...$params
                ]);

                return $response->withRedirect((new RouteUri($request))("progress/{$uuid}"));

            } catch (RuntimeException $exception) {
                $error = $exception->getMessage();
            }
        }

        return $this->render($response, 'file/backup_restore', [
            'request' => $request,
            'form' => $params,
            'error' => $error,
        ]);
    }
}