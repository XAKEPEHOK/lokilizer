<?php
/**
 * Created for sr-app
 * Date: 2025-01-15 01:22
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Actions\File;

use League\Plates\Engine;
use PrinsFrank\Standards\Language\LanguageAlpha2;
use RuntimeException;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RenderAction;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Models\Localization\Db\RecordRepo;
use XAKEPEHOK\Lokilizer\Models\Localization\Record;
use XAKEPEHOK\Lokilizer\Models\Project\Components\Role\Permission;

class DownloadAction extends RenderAction
{

    public function __construct(
        Engine                      $renderer,
        private readonly RecordRepo $recordRepo,
    )
    {
        parent::__construct($renderer);
    }

    public function __invoke(Request $request, Response $response): Response
    {
        Current::guard(Permission::FILE_DOWNLOADS);

        $formatter = Current::getProject()->getFileFormatter()->factory();

        $params = [
            'language' => $request->getParsedBodyParam('language', ''),
            'withEmpty' => boolval($request->getParsedBodyParam('withEmpty')),
            'withOutdated' => boolval($request->getParsedBodyParam('withOutdated')),
            'options' => (function () use ($formatter, $request) {
                $options = $formatter::exportOptions();
                $result = [];
                foreach ($options as $name => $variants) {
                    $default = array_key_first($variants);
                    $value = $request->getParsedBodyParam("option_" . base64_encode($name), $default);
                    $result[$name] = isset($variants[$value]) ? $value : $default;
                }
                return $result;
            })(),
        ];

        $error = '';
        if ($request->isPost()) {
            try {
                $language = LanguageAlpha2::tryFrom($params['language']);
                if (is_null($language)) {
                    throw new RuntimeException('Invalid language');
                }

                $records = $this->recordRepo->findAll(
                    withOutdated: $params['withOutdated']
                );

                usort($records, function (Record $a, Record $b) {
                    return $a->getPosition() <=> $b->getPosition();
                });

                if (!$params['withEmpty']) {
                    $records = array_filter($records, fn (Record $record) => !$record->getValue($language)->isEmpty());
                }

                $fileRepresentation = Current::getProject()->getFileFormatter()->factory()->export($language, $params['options'], ...$records);

                return $response
                    ->withHeader('Content-type', 'application/octet-stream')
                    ->withHeader('Content-Disposition', 'attachment; filename="' . urlencode($fileRepresentation->filename) . '"')
                    ->withHeader('Content-Length', $fileRepresentation->size)
                    ->write($fileRepresentation->content);

            } catch (RuntimeException $exception) {
                $error = $exception->getMessage();
            }
        }

        return $this->render($response, 'file/file_download', [
            'request' => $request,
            'form' => $params,
            'error' => $error,
            'formatter' => $formatter,
            'languages' => $this->recordRepo->fetchLanguages(),
        ]);
    }
}