<?php
/**
 * Created for lokilizer
 * Date: 2025-01-22 03:31
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Actions;

use League\Plates\Engine;
use Psr\Http\Message\ResponseInterface;
use Redis;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use Throwable;
use XAKEPEHOK\Lokilizer\Apps\Console\Handle\HandleTaskCommand;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RenderAction;

class ProgressAction extends RenderAction
{

    public function __construct(
        private Redis $redis,
        Engine        $renderer
    )
    {
        parent::__construct($renderer);
    }

    public function __invoke(Request $request, Response $response): Response|ResponseInterface
    {
        $uuid = $request->getAttribute('uuid');

        if (!$this->redis->exists("progress:task:{$uuid}:current")) {
            return $this->render($response, 'progress/progress', [
                'request' => $request,
                'current' => null,
                'max' => null,
                'logs' => [],
                'finish' => null,
            ]);
        }

        if ($request->isPost()) {
            $this->redis->setex("progress:task:{$uuid}:stop", HandleTaskCommand::TTL, 1);
        }

        $current = $this->redis->get("progress:task:{$uuid}:current");
        $max = $this->redis->get("progress:task:{$uuid}:max");
        if ($max === false) {
            $max = null;
        }

        try {
            $finish = json_decode($this->redis->get("progress:task:{$uuid}:finish"), true, flags: JSON_THROW_ON_ERROR);
            if (!is_array($finish)) {
                $finish = null;
            }
        } catch (Throwable) {
            $finish = null;
        }

        $logs = $this->redis->lrange("progress:task:{$uuid}:logs", 0, -1);
        if (!$logs) {
            $logs = [];
        }

        $logs = array_map(
            fn(string $data) => json_decode($data, true, flags: JSON_THROW_ON_ERROR),
            $logs
        );

        $title = trim(strval($this->redis->get("progress:task:{$uuid}:title")));
        if (empty($title)) {
            $title = null;
        }

        return $this->render($response, 'progress/progress', [
            'request' => $request,
            'title' => $title,
            'current' => $current,
            'max' => $max,
            'logs' => $logs,
            'finish' => $finish,
        ]);
    }
}