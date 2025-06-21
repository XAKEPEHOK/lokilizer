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
use XAKEPEHOK\Lokilizer\Apps\Console\Handle\Tasks\BatchAISuggestTaskCommand;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\ApiRuntimeException;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RenderAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Models\Glossary\Db\Storage\GlossaryRepo;
use XAKEPEHOK\Lokilizer\Models\LLM\Db\LLMEndpointRepo;
use XAKEPEHOK\Lokilizer\Models\Project\Components\Role\Permission;

class BatchAISuggestAction extends RenderAction
{
    public function __construct(
        Engine                                     $renderer,
        private readonly GlossaryRepo              $glossaryRepo,
        private readonly BatchAISuggestTaskCommand $taskCommand,
        private LLMEndpointRepo  $llmEndpointRepo,

    )
    {
        parent::__construct($renderer);
    }

    public function __invoke(Request $request, Response $response): Response
    {
        Current::guard(Permission::BATCH_AI);

        $llm = $this->llmEndpointRepo->findDefault();

        $languages = $this->glossaryRepo->findPrimary()->getLanguages();

        $defaultTrue = !$request->isPost();
        $params = [
            'language' => $request->getParsedBodyParam('language', ''),
            'keyContains' => $request->getParsedBodyParam('keyContains', ''),
            'valueContains' => $request->getParsedBodyParam('valueContains', ''),
            'llm' => $request->getParsedBodyParam('llm', $llm->id()->get()),
            'llmTimeout' => $request->getParsedBodyParam('llmTimeout', 120),
            'prompt' => $request->getParsedBodyParam('prompt', ''),
            'excludeWithSuggestions' => boolval($request->getParsedBodyParam('excludeWithSuggestions', $defaultTrue)),
            'excludeVerified' => boolval($request->getParsedBodyParam('excludeVerified', false)),
        ];

        $error = '';
        if ($request->isPost()) {
            try {
                $language = LanguageAlpha2::tryFrom($params['language']);
                if (is_null($language) || !in_array($language, $languages)) {
                    throw new ApiRuntimeException('Invalid language');
                }

                $this->llmEndpointRepo->findById($params['llm'], new ApiRuntimeException('Invalid LLM model'));

                Current::guard(Permission::BATCH_AI, $language);
                $uuid = $this->taskCommand->publish([
                    'title' => 'AI Batch suggest',
                    ...$params
                ]);

                return $response->withRedirect((new RouteUri($request))("progress/{$uuid}"));

            } catch (ApiRuntimeException $exception) {
                $error = $exception->getMessage();
            }
        }

        return $this->render($response, 'batch/batch_ai_suggest', [
            'request' => $request,
            'form' => $params,
            'error' => $error,
            'languages' => $languages,
        ]);

    }
}