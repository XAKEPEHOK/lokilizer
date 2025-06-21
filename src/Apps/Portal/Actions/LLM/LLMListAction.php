<?php

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Actions\LLM;

use League\Plates\Engine;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RenderAction;
use XAKEPEHOK\Lokilizer\Models\LLM\Db\LLMEndpointRepo;
use XAKEPEHOK\Lokilizer\Models\LLM\LLMEndpoint;

class LLMListAction extends RenderAction
{

    public function __construct(
        private LLMEndpointRepo $endpointRepo,
        Engine $renderer
    )
    {
        parent::__construct($renderer);
    }

    public function __invoke(Request $request, Response $response): Response
    {
        return $this->render($response, 'llm/llm_list', [
            'request' => $request,
            'fsp' => [
                'repo' => $this->endpointRepo,
                'columns' => [
                    'actions' => [
                        'header' => '',
                        'sortable' => null,
                        'filter' => null,
                        'type' => 'action',
                        'value' => fn(LLMEndpoint $endpoint) => [
                            '🔧' => "llm/{$endpoint->id()}",
                        ],

                    ],
                    'name' => [
                        'header' => "Name",
                        'filter' => 'string',
                        'sortable' => true,
                        'value' => fn(LLMEndpoint $endpoint) => $endpoint->getName()
                    ],
                    'uri' => [
                        'header' => "Uri",
                        'filter' => 'string',
                        'sortable' => true,
                        'value' => fn(LLMEndpoint $endpoint) => $endpoint->getUri()
                    ],
                    'model' => [
                        'header' => "Model",
                        'filter' => 'string',
                        'sortable' => true,
                        'value' => fn(LLMEndpoint $endpoint) => $endpoint->getModel()
                    ],
                    'pricing' => [
                        'header' => 'Pricing',
                        'filter' => false,
                        'value' => [
                            [
                                'type' => 'line',
                                'value' => fn(LLMEndpoint $endpoint) => "IN: \${$endpoint->getPricing()->inputPer1M} per 1M tokens",
                            ],
                            [
                                'type' => 'line',
                                'value' => fn(LLMEndpoint $endpoint) => "OUT: \${$endpoint->getPricing()->outputPer1M} per 1M tokens",
                            ],
                        ],
                    ],
                    'proxy' => [
                        'header' => 'Proxy',
                        'filter' => 'string',
                        'sortable' => true,
                        'value' => fn(LLMEndpoint $endpoint) => $endpoint->getProxy()
                    ],
                    'timeout' => [
                        'header' => 'Timeout',
                        'filter' => 'number',
                        'sortable' => true,
                        'value' => fn(LLMEndpoint $endpoint) => $endpoint->getTimeout()
                    ],
                    'cost' => [
                        'header' => 'Cost',
                        'filter' => 'number',
                        'sortable' => true,
                        'value' => fn(LLMEndpoint $endpoint) => '$' . round($endpoint->getCost()->getResult(), 2),
                    ],
                ],
            ],
        ]);
    }
}