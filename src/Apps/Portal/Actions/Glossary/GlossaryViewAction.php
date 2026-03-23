<?php

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Glossary;

use League\Plates\Engine;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RenderAction;
use XAKEPEHOK\Lokilizer\Models\Glossary\Db\Storage\GlossaryRepo;
use XAKEPEHOK\Lokilizer\Models\Localization\Db\RecordRepo;

class GlossaryViewAction extends RenderAction
{

    public function __construct(
        private GlossaryRepo $glossaryRepo,
        private RecordRepo   $recordRepo,
        Engine               $renderer
    )
    {
        parent::__construct($renderer);
    }

    public function __invoke(Request $request, Response $response): Response|ResponseInterface
    {
        $primaryGlossary = $this->glossaryRepo->findPrimary();

        if ($request->getAttribute('id') === 'primary') {
            $glossary = $primaryGlossary;
            $languages = array_unique(array_merge(
                $this->recordRepo->fetchLanguages(true, false),
                $glossary->getLanguages()
            ), SORT_REGULAR);
        } else {
            $glossary = $this->glossaryRepo->findById($request->getAttribute('id'));
            $languages = $primaryGlossary->getLanguages();
        }

        return $this->render($response, 'glossary/glossary_view', [
            'request' => $request,
            'glossary' => $glossary,
            'languages' => $languages,
        ]);
    }
}
