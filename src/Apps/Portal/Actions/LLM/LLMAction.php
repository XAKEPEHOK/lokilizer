<?php

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Actions\LLM;

use DiBify\DiBify\Manager\ModelManager;
use League\Plates\Engine;
use Slim\Http\ServerRequest as Request;
use Throwable;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\ApiRuntimeException;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RenderAction;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Models\LLM\LLMEndpoint;

abstract class LLMAction extends RenderAction
{

    public function __construct(
        Engine $renderer,
        protected readonly ModelManager $modelManager,
    )
    {
        parent::__construct($renderer);
    }

    protected function parseParams(Request $request, ?LLMEndpoint $endpoint = null): array
    {
        return [
            'name' => trim($request->getParsedBodyParam('name', $endpoint?->getName() ?? '')),
            'uri' => $request->getParsedBodyParam('uri', $endpoint?->getUri() ?? ''),
            'token' => $request->getParsedBodyParam('token', $endpoint?->getToken() ?? ''),
            'model' => $request->getParsedBodyParam('model', $endpoint?->getModel() ?? ''),
            'pricingInput' => floatval($request->getParsedBodyParam('pricingInput', $endpoint?->getPricing()->inputPer1M ?? 0)),
            'pricingOutput' => floatval($request->getParsedBodyParam('pricingOutput', $endpoint?->getPricing()->outputPer1M ?? 0)),
            'proxy' => $request->getParsedBodyParam('proxy', $endpoint?->getProxy() ?? ''),
            'timeout' => floatval($request->getParsedBodyParam('timeout', $endpoint?->getTimeout() ?? 120)),
        ];
    }

    public function test(Request $request, LLMEndpoint $endpoint): ?string
    {
        if (!($request->isPost() && $request->getParsedBodyParam('test') === '1')) {
            return null;
        }
        try {
            $prompt = "Give short answer to passed question in " . Current::getProject()->getPrimaryLanguage()->name;
            return $endpoint->query($prompt, 'Who are you?')->text;
        } catch (Throwable $throwable) {
            throw new ApiRuntimeException('Error. Please, check LLM uri, model name or token');
        }
    }

}