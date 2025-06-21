<?php
/**
 * Created for lokilizer
 * Date: 2025-02-16 22:57
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Batch;

use League\Plates\Engine;
use PrinsFrank\Standards\Language\LanguageAlpha2;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use XAKEPEHOK\Lokilizer\Apps\Console\Handle\Tasks\BatchModifyTaskCommand;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\ApiRuntimeException;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RenderAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Models\Localization\Db\RecordRepo;
use XAKEPEHOK\Lokilizer\Models\Project\Components\Role\Permission;

class BatchModifyAction extends RenderAction
{
    public function __construct(
        Engine                      $renderer,
        private readonly RecordRepo $recordRepo,
        private readonly BatchModifyTaskCommand  $taskCommand,
    )
    {
        parent::__construct($renderer);
    }

    public function __invoke(Request $request, Response $response): Response
    {
        Current::guard(Permission::BATCH_MODIFY);

        $params = [
            'language' => $request->getParsedBodyParam('language', ''),
            'keyContains' => $request->getParsedBodyParam('keyContains', ''),
            'valueContains' => $request->getParsedBodyParam('valueContains', ''),
            'trim' => boolval($request->getParsedBodyParam('trim', false)),
            'revalidate' => boolval($request->getParsedBodyParam('revalidate', true)),
            'removeVerification' => boolval($request->getParsedBodyParam('removeVerification', false)),
            'removeComment' => boolval($request->getParsedBodyParam('removeComment', false)),
            'removeValue' => boolval($request->getParsedBodyParam('removeValue', false)),
            'removeSuggested' => boolval($request->getParsedBodyParam('removeSuggested', false)),
            'includeOutdated' => boolval($request->getParsedBodyParam('includeOutdated', false)),
        ];

        $error = '';
        if ($request->isPost()) {
            try {
                $language = LanguageAlpha2::tryFrom($params['language']);
                if (is_null($language)) {
                    throw new ApiRuntimeException('Invalid language');
                }

                Current::guard(Permission::BATCH_MODIFY, $language);
                $uuid = $this->taskCommand->publish([
                    'title' => 'Batch modify',
                    ...$params
                ]);

                return $response->withRedirect((new RouteUri($request))("progress/{$uuid}"));

            } catch (ApiRuntimeException $exception) {
                $error = $exception->getMessage();
            }
        }

        return $this->render($response, 'batch/batch_modify', [
            'request' => $request,
            'form' => $params,
            'error' => $error,
            'languages' => $this->recordRepo->fetchLanguages(),
        ]);

    }
}