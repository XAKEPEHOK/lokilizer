<?php
/**
 * Created for sr-app
 * Date: 2025-01-15 01:22
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Tools;

use League\Plates\Engine;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RenderAction;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Models\Localization\Components\CardinalPluralValue;

class PluralsAction extends RenderAction
{

    public function __construct(
        Engine                        $renderer,
    )
    {
        parent::__construct($renderer);
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $params = [
            'language' => $request->getQueryParam('language', Current::getProject()->getPrimaryLanguage()->value),
            'type' => $request->getQueryParam('type', CardinalPluralValue::getType()),
        ];

        return $this->render($response, 'tools/tools_plurals', [
            'request' => $request,
            'form' => $params,
        ]);
    }
}