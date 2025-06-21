<?php
/**
 * Created for lokilizer
 * Date: 2025-01-29 19:19
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Record;

use Adbar\Dot;
use DiBify\DiBify\Manager\ModelManager;
use DiBify\DiBify\Manager\Transaction;
use League\Plates\Engine;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RenderAction;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Models\Localization\Components\AbstractValue;
use XAKEPEHOK\Lokilizer\Models\Localization\Db\RecordRepo;
use XAKEPEHOK\Lokilizer\Models\Localization\Record;
use XAKEPEHOK\Lokilizer\Models\Project\Components\Role\Permission;
use XAKEPEHOK\Lokilizer\Services\GlossaryService;

class SaveAction extends RenderAction
{

    public function __construct(
        Engine $renderer,
        private RecordRepo $recordRepo,
        private GlossaryService $glossaryService,
        private ModelManager $modelManager,
    )
    {
        parent::__construct($renderer);
    }

    public function __invoke(Request $request, Response $response): Response|ResponseInterface
    {
        $exception = new RuntimeException('Invalid record id ' . $request->getAttribute('id'));

        /** @var Record $record */
        $record = $this->recordRepo->findById($request->getAttribute('id'), $exception);
        if ($record->isOutdated()) {
            throw $exception;
        }

        $params = new Dot($request->getParsedBody());
        $transaction = new Transaction([$record]);

        $languages = $this->recordRepo->fetchLanguages(true);
        $isPrimaryChanged = false;
        foreach ($languages as $language) {
            if (!Current::can(Permission::TRANSLATE, $language)) {
                continue;
            }

            $value = $record->getValue($language) ?? $record->getPrimaryValue()::getEmpty($language);
            $value = AbstractValue::parse($value, $params->get("value.{$language->value}"));
            $hasChanges = $record->setValue($value);
            $value = $record->getValue($language);

            $isPrimary = $record->getPrimaryValue()->getLanguage() === $language;
            if ($isPrimary) {
                $isPrimaryChanged = $hasChanges;
            }

            $suggested = AbstractValue::parse($value, $params->get("suggested.{$language->value}", []));
            $value->setSuggested($suggested->isEmpty() ? null : $suggested);

            $primary = $record->getPrimaryValue();
            $warnings = $value->validate($record);
            $value->setWarnings($warnings);

            $newVerificationState = boolval(intval($params->get("verification.{$language->value}", 0)));
            if ($isPrimary) {
                if ($value->getWarnings() === 0 || !$hasChanges) {
                    $value->verified = $newVerificationState;
                }
            } else {
                if (!$isPrimaryChanged && ($value->getWarnings() === 0 || !$hasChanges)) {
                    $value->verified = $newVerificationState;
                }
            }
        }

        $record->setComment($params->get('comment', ''));

        $this->modelManager->commit($transaction);
        return $this->render($response, 'widgets/_record_group', [
            'request' => $request,
            'record' => $record,
            'distill' => fn(Record $record) => $this->glossaryService->getDistilled($record),
            'languages' => $languages
        ]);
    }
}