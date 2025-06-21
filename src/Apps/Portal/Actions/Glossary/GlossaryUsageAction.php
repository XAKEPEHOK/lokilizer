<?php
/**
 * Created for lokilizer
 * Date: 2025-03-02 11:37
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Glossary;

use League\Plates\Engine;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RenderAction;
use XAKEPEHOK\Lokilizer\Models\Glossary\Db\Storage\GlossaryRepo;
use XAKEPEHOK\Lokilizer\Models\Localization\Db\RecordRepo;
use XAKEPEHOK\Lokilizer\Models\Localization\Record;
use XAKEPEHOK\Lokilizer\Services\GlossaryService;

class GlossaryUsageAction extends RenderAction
{

    public function __construct(
        private RecordRepo      $recordRepo,
        private GlossaryRepo    $glossaryRepo,
        private GlossaryService $glossaryService,
        Engine                  $renderer
    )
    {
        parent::__construct($renderer);
    }

    public function __invoke(Request $request, Response $response): Response|ResponseInterface
    {
        $glossaries = $this->glossaryRepo->findAll();
        $recordsIds = $this->recordRepo->fetchIdsArray();

        $usage = [];

        foreach ($recordsIds as $recordId) {
            $this->recordRepo->freeUpMemory();
            /** @var Record $record */
            $record = $this->recordRepo->findById($recordId);
            $string = $record->getPrimaryValue()->getStringContext();
            foreach ($glossaries as $glossary) {
                if (empty($glossary->getItems())) {
                    continue;
                }
                $items = $this->glossaryService->distillItems($glossary, $string);
                foreach ($items as $item) {
                    $glossaryId = $glossary->id()->get();
                    $phrase = $item->primary->phrase;
                    $usage[$glossaryId][$phrase] = ($usage[$glossaryId][$phrase] ?? 0) + 1;
                }
            }
        }

        foreach ($usage as $glossaryId => &$phrases) {
            $glossary = $glossaries[$glossaryId];
            foreach ($glossary->getItems() as $item) {
                $phrase = $item->primary->phrase;
                if (!isset($phrases[$phrase])) {
                    $phrases[$phrase] = 0;
                }
            }
            asort($phrases);
        }

        return $this->render($response, 'glossary/glossary_usage', [
            'request' => $request,
            'glossaries' => $glossaries,
            'usage' => $usage,
        ]);
    }
}