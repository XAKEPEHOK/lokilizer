<?php
/**
 * Created for sr-app
 * Date: 2025-01-15 01:22
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Tools;

use League\Plates\Engine;
use Redis;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\ApiRuntimeException;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RenderAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use XAKEPEHOK\Lokilizer\Components\ColorType;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Models\Project\Components\Role\Permission;

class AlertMessageAction extends RenderAction
{

    public function __construct(
        Engine        $renderer,
        private Redis $redis,
    )
    {
        parent::__construct($renderer);
    }

    public function __invoke(Request $request, Response $response): Response
    {
        Current::guard(Permission::ALERT_MESSAGE);

        $projectId = Current::getProject()->id()->get();
        $key = "alert:{$projectId}";

        $current = $this->redis->hGetAll($key);
        if (!$current) {
            $current = [];
        }

        $params = [
            'text' => trim($request->getParsedBodyParam('text', $current['text'] ?? '')),
            'type' => $request->getParsedBodyParam('type', $current['type'] ?? ColorType::Info->value),
        ];

        $error = '';
        if ($request->isPost()) {

            $type = ColorType::tryFrom($params['type']);
            if (is_null($type)) {
                throw new ApiRuntimeException('Invalid LLM model');
            }

            if (empty($params['text'])) {
                $this->redis->del($key);
            } else {
                $this->redis->hMset($key, [
                    'text' => $params['text'],
                    'user' => Current::getUser()->id()->get(),
                    'type' => $type->value,
                ]);
            }
            return $response->withRedirect((new RouteUri($request))("alert-message"));
        }

        return $this->render($response, 'tools/tools_alert_message', [
            'request' => $request,
            'error' => $error,
            'form' => $params,
        ]);
    }
}