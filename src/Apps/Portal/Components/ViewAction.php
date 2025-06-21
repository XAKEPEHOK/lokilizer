<?php
/**
 * Created for lokilizer
 * Date: 02.08.2024 03:15
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Components;

use DiBify\DiBify\Manager\ModelManager;
use League\Plates\Engine;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;

class ViewAction extends RenderAction
{

    public function __construct(
        Engine $renderer,
        private ModelManager $modelManager,
    )
    {
        parent::__construct($renderer);
    }

    public function __invoke(Request $request, Response $response): Response|ResponseInterface
    {
        $alias = $request->getAttribute('alias');
        $repo = $this->modelManager->getRepository($alias);
        $model = $repo->findById($request->getAttribute('id'));
        if ($model === null) {
            return $this->render($response, 'errors/not_found', [
                'request' => $request,
                'error' => ucfirst($alias) . ' not found',
            ]);
        }

        return $this->render($response, $alias . '/' . $alias . '_view', [
            'request' => $request,
            'modelManager' => $this->modelManager,
            $alias => $model,
        ]);
    }
}