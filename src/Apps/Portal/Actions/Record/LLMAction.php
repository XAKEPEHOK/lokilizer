<?php
/**
 * Created for lokilizer
 * Date: 2025-01-29 19:19
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Record;

use DiBify\DiBify\Manager\ModelManager;
use DiBify\DiBify\Manager\Transaction;
use League\Plates\Engine;
use PrinsFrank\Standards\Language\LanguageAlpha2;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\ApiRuntimeException;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RenderAction;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Models\LLM\Db\LLMEndpointRepo;
use XAKEPEHOK\Lokilizer\Models\Localization\Db\RecordRepo;
use XAKEPEHOK\Lokilizer\Models\Localization\Record;
use XAKEPEHOK\Lokilizer\Models\Project\Components\Role\Permission;
use XAKEPEHOK\Lokilizer\Services\GlossaryService;
use XAKEPEHOK\Lokilizer\Services\TranslateService;
use function DI\value;

class LLMAction extends RenderAction
{

    public function __construct(
        Engine                   $renderer,
        private RecordRepo       $recordRepo,
        private TranslateService $translateService,
        private GlossaryService  $glossaryService,
        private ModelManager     $modelManager,
        private LLMEndpointRepo  $llmEndpointRepo,
    )
    {
        parent::__construct($renderer);
    }

    public function __invoke(Request $request, Response $response): Response|ResponseInterface
    {
        $exception = new RuntimeException('Invalid record id ' . $request->getAttribute('id'));

        $language = LanguageAlpha2::from($request->getAttribute('language'));

        $llm = $this->llmEndpointRepo->findById($request->getAttribute('llm'), new ApiRuntimeException('Invalid LLM model'));
        /** @var Record $record */
        $record = $this->recordRepo->findById($request->getAttribute('id'), $exception);

        Current::guard(Permission::TRANSLATE, $language);

        if ($record->isOutdated()) {
            throw $exception;
        }

        $this->translateService->translate($record, $language, $llm);
        $this->modelManager->commit(new Transaction([$record]));

        return $this->render($response, 'widgets/_record_group', [
            'request' => $request,
            'distill' => fn(Record $record) => $this->glossaryService->getDistilled($record),
            'record' => $record,
            'languages' => $this->recordRepo->fetchLanguages(true),
        ]);
    }
}