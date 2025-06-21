<?php

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Actions\LLM;

use DiBify\DiBify\Manager\Transaction;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\ApiRuntimeException;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Models\LLM\LLMEndpoint;
use XAKEPEHOK\Lokilizer\Models\LLM\LLMPricing;
use XAKEPEHOK\Lokilizer\Models\Project\Components\Role\Permission;

class LLMAddAction extends LLMAction
{

    public function __invoke(Request $request, Response $response): Response|ResponseInterface
    {
        Current::guard(Permission::MANAGE_LLM);
        $params = $this->parseParams($request);

        $error = '';
        $test = '';

        if ($request->isPost()) {
            try {
                $llm = new LLMEndpoint(
                    name: $params['name'],
                    uri: $params['uri'],
                    token: $params['token'],
                    model: $params['model'],
                    pricing: new LLMPricing($params['pricingInput'], $params['pricingOutput']),
                    timeout: $params['timeout'],
                );

                $llm->setProxy($params['proxy']);

                $test = $this->test($request, $llm);
                if ($test === null) {
                    $this->modelManager->commit(new Transaction([$llm]));
                    return $response->withRedirect((new RouteUri($request))("llm"));
                }
            } catch (ApiRuntimeException $exception) {
                $error = $exception->getMessage();
            }
        }

        return $this->render($response, 'llm/llm_add', [
            'request' => $request,
            'form' => $params,
            'test' => $test,
            'error' => $error,
        ]);
    }
}