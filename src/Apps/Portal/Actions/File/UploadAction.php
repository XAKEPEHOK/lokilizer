<?php
/**
 * Created for sr-app
 * Date: 2025-01-15 01:22
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Actions\File;

use JsonException;
use League\Plates\Engine;
use Nyholm\Psr7\UploadedFile;
use PrinsFrank\Standards\Language\LanguageAlpha2;
use RuntimeException;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use Symfony\Component\Filesystem\Filesystem;
use XAKEPEHOK\Lokilizer\Apps\Console\Handle\Tasks\FileUploadTaskCommand;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RenderAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Models\Project\Components\Role\Permission;
use XAKEPEHOK\Path\Path;

class UploadAction extends RenderAction
{

    public function __construct(
        Engine                        $renderer,
        private readonly Filesystem $filesystem,
        private readonly FileUploadTaskCommand $taskCommand,
    )
    {
        parent::__construct($renderer);
    }

    public function __invoke(Request $request, Response $response): Response
    {
        Current::guard(Permission::FILE_UPLOADS);

        $params = [
            'language' => $request->getParsedBodyParam('language', ''),
        ];

        $error = '';
        if ($request->isPost()) {
            try {
                $language = LanguageAlpha2::tryFrom($params['language']);
                if (is_null($language)) {
                    throw new RuntimeException('Invalid language');
                }

                /** @var UploadedFile $file */
                $file = $request->getUploadedFiles()['file'] ?? null;
                if (is_null($file)) {
                    throw new RuntimeException('No file uploaded');
                }

                if ($file->getError() !== UPLOAD_ERR_OK) {
                    throw new RuntimeException('File uploading error');
                }

                $json = $file->getStream()->getContents();
                try {
                    $data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
                } catch (JsonException) {
                    throw new RuntimeException('Invalid json file');
                }

                $directory = Path::root()->down('runtime/uploads/')->down(Current::getProject()->id());
                $path = $directory->down(md5($json) . '.json');

                if (!$this->filesystem->exists($directory)) {
                    $this->filesystem->mkdir($directory);
                }

                $file->moveTo(strval($path));

                $uuid = $this->taskCommand->publish([
                    'title' => 'Uploading file ' . $file->getClientFilename(),
                    'path' => $path,
                    'language' => $language,
                ]);

                return $response->withRedirect((new RouteUri($request))("progress/{$uuid}"));

            } catch (RuntimeException $exception) {
                $error = $exception->getMessage();
            }
        }

        return $this->render($response, 'file/file_upload', [
            'request' => $request,
            'form' => $params,
            'error' => $error,
        ]);
    }
}