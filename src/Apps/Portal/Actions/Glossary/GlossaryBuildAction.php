<?php
/**
 * Created for lokilizer
 * Date: 2025-02-15 03:21
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Glossary;

use League\Plates\Engine;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\ApiRuntimeException;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RenderAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use XAKEPEHOK\Lokilizer\Models\LLM\Db\LLMEndpointRepo;
use XAKEPEHOK\Lokilizer\Services\GlossaryBuilderService;

class GlossaryBuildAction extends RenderAction
{

    public function __construct(
        Engine $renderer,
        private GlossaryBuilderService $builderService,
        private LLMEndpointRepo  $llmEndpointRepo,

    )
    {
        parent::__construct($renderer);
    }

    public function __invoke(Request $request, Response $response): Response|ResponseInterface
    {
        $build = $request->getParsedBodyParam('build');
        list($keyPrefix, $llmId) = json_decode(base64_decode($build), true);
        $llm = $this->llmEndpointRepo->findById($llmId, new ApiRuntimeException('Invalid LLM model'));
        $glossary = $this->builderService->buildGlossary($keyPrefix, $llm);
        return $response->withRedirect((new RouteUri($request))("glossary/{$glossary->id()}"));
    }
}