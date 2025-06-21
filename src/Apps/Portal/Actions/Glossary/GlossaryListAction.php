<?php
/**
 * Created for lokilizer
 * Date: 2025-02-12 17:46
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Glossary;

use League\Plates\Engine;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RenderAction;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Models\Glossary\Db\Storage\GlossaryRepo;

class GlossaryListAction extends RenderAction
{

    public function __construct(
        Engine $renderer,
        private GlossaryRepo $glossaryRepo,
    )
    {
        parent::__construct($renderer);
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $project = Current::getProject();

        return $this->render($response, 'glossary/glossary_list', [
            'request' => $request,
            'fsp' => [
                'repo' => $this->glossaryRepo,
//                'sort' => 'touchedAt',
                'columns' => [
//                    'actions' => [
//                        'header' => '',
//                        'sortable' => null,
//                        'filter' => null,
//                        'type' => 'widget',
//                        'value' => fn(BillingRecord $record) => ['widgets/actions/_billing', [
//                            'record' => $record,
//                            'text' => '',
//                        ]],
//                    ],
                ],
            ],
        ]);
    }
}