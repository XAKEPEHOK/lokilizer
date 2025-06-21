<?php

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Actions\LLM;

use DiBify\DiBify\Manager\ModelManager;
use DiBify\DiBify\Manager\Transaction;
use League\Plates\Engine;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\ApiRuntimeException;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Models\LLM\Db\LLMEndpointRepo;
use XAKEPEHOK\Lokilizer\Models\LLM\LLMEndpoint;
use XAKEPEHOK\Lokilizer\Models\LLM\LLMPricing;
use XAKEPEHOK\Lokilizer\Models\Project\Components\Role\Permission;

class LLMUpdateAction extends LLMAction
{

    public function __construct(
        Engine $renderer,
        ModelManager $modelManager,
        private LLMEndpointRepo $endpointRepo,
    )
    {
        parent::__construct($renderer, $modelManager);
    }

    public function __invoke(Request $request, Response $response): Response|ResponseInterface
    {
        Current::guard(Permission::MANAGE_LLM);

        $error = '';
        $test = '';

        /** @var LLMEndpoint $llm */
        $llm = $this->endpointRepo->findById(
            $request->getAttribute('id'),
            new ApiRuntimeException('LLM with passed id not found')
        );

        $params = $this->parseParams($request, $llm);

        if ($request->isPost()) {

            if ($request->getQueryParam('delete') == '1') {
                $this->modelManager->commit(new Transaction([], [$llm]));
                return $response->withRedirect((new RouteUri($request))("llm"));
            }

            try {
                $llm->setName($params['name']);
                $llm->setUri($params['uri']);
                $llm->setProxy($params['proxy']);
                $llm->setToken($params['token']);
                $llm->setModel($params['model']);
                $llm->setPricing(new LLMPricing($params['pricingInput'], $params['pricingOutput']));
                $llm->setTimeout($params['timeout']);

                $test = $this->test($request, $llm);
                if ($test === null) {
                    $this->modelManager->commit(new Transaction([$llm]));
                    return $response->withRedirect((new RouteUri($request))("llm"));
                }
            } catch (ApiRuntimeException $exception) {
                $error = $exception->getMessage();
            }
        }

        return $this->render($response, 'llm/llm_update', [
            'request' => $request,
            'form' => $params,
            'test' => $test,
            'error' => $error,
            'llm' => $llm,
        ]);
    }
}