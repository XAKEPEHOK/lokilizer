<?php
/**
 * Created for lokilizer
 * Date: 2025-02-16 23:15
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Apps\Console\Handle\Tasks;

use DiBify\DiBify\Manager\ModelManager;
use DiBify\DiBify\Manager\Transaction;
use PrinsFrank\Standards\Language\LanguageAlpha2;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use XAKEPEHOK\Lokilizer\Components\ColorType;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Models\LLM\Db\LLMEndpointRepo;
use XAKEPEHOK\Lokilizer\Models\LLM\LLMEndpoint;
use XAKEPEHOK\Lokilizer\Models\Localization\Components\SimpleValue;
use XAKEPEHOK\Lokilizer\Models\Localization\Db\RecordRepo;
use XAKEPEHOK\Lokilizer\Models\Localization\Record;
use XAKEPEHOK\Lokilizer\NotFoundException;
use XAKEPEHOK\Lokilizer\Services\TranslateService;
use function Sentry\captureException;

class BatchAITranslateTaskCommand extends BatchRecordsParallelCommand
{

    use RecordFilterTrait;

    private LanguageAlpha2 $language;
    private string $keyContains;
    private string $valueContains;
    private LLMEndpoint $llm;
    private bool $useSameValues;
    private bool $includeTranslatedWithoutWarnings;
    private bool $includeTranslatedWithWarnings;
    private bool $excludeWithSuggestions;
    private bool $excludeVerified;

    public function __construct(
        private ModelManager     $modelManager,
        private TranslateService $translateService,
        private LLMEndpointRepo  $llmEndpointRepo,
        RecordRepo               $recordRepo,
        ContainerInterface       $container,
    )
    {
        parent::__construct($recordRepo, $container);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = $input->getOption('recordId');
        $data = $this->getTaskData($input, $id === null);

        $this->language = LanguageAlpha2::from($data['language']);
        $this->keyContains = $data['keyContains'];
        $this->valueContains = $data['valueContains'];
        $this->llm = $this->llmEndpointRepo->findById($data['llm'], new NotFoundException('LLM not found'));
        $this->llmTimeout = $data['llmTimeout'];
        $this->useSameValues = boolval($data['useSameValues']);
        $this->includeTranslatedWithoutWarnings = boolval($data['includeTranslatedWithoutWarnings']);
        $this->includeTranslatedWithWarnings = boolval($data['includeTranslatedWithWarnings']);
        $this->excludeWithSuggestions = boolval($data['excludeWithSuggestions']);
        $this->excludeVerified = boolval($data['excludeVerified']);

        if (Current::getProject()->getPrimaryLanguage() === $this->language) {
            $this->finishProgress(ColorType::Danger, 'Primary language can not be translated to primary language :)');
            return self::SUCCESS;
        }

        if ($id) {
            $this->handleOne($id);
            return self::SUCCESS;
        }

        $this->addLogProgress('Exclude verified', $this->excludeVerified ? 'yes' : 'no', ColorType::Nothing);
        $this->addLogProgress('Exclude with suggestions', $this->excludeWithSuggestions ? 'yes' : 'no', ColorType::Nothing);
        $this->addLogProgress('Include translated with warnings', $this->includeTranslatedWithWarnings ? 'yes' : 'no', ColorType::Nothing);
        $this->addLogProgress('Include translated without warnings', $this->includeTranslatedWithoutWarnings ? 'yes' : 'no', ColorType::Nothing);
        $this->addLogProgress('Use same values', $this->useSameValues ? 'yes' : 'no', ColorType::Nothing);
        $this->addLogProgress('LLM Timeout', $this->llmTimeout, ColorType::Nothing);
        $this->addLogProgress('LLM', $this->llm->getName(), ColorType::Nothing);
        $this->addLogProgress('Value contains', $this->valueContains, ColorType::Nothing);
        $this->addLogProgress('Key contains', $this->keyContains, ColorType::Nothing);
        $this->addLogProgress('Language', $this->language->value, ColorType::Nothing);

        $recordsIds = $this->recordRepo->fetchIdsArray(false);
        $this->setMaxProgress(count($recordsIds));

        $this->handleParallelProcesses($input, $recordsIds);

        $this->addLogProgress('', '', ColorType::Nothing);
        $this->addLogProgress('Errors', $this->getCustomCounter('errors'), ColorType::Danger);
        $this->addLogProgress('Skipped', $this->getCustomCounter('skipped'), ColorType::Warning);
        $this->addLogProgress('Unchanged', $this->getCustomCounter('unchanged'), ColorType::Warning);
        $this->addLogProgress('Suggested', $this->getCustomCounter('suggested'), ColorType::Info);
        $this->addLogProgress('Translated same values', $this->getCustomCounter('translatedSame'), ColorType::Success);
        $this->addLogProgress('Translated by AI', $this->getCustomCounter('translated'), ColorType::Success);

        $this->finishProgress(ColorType::Success, 'Successfully handled');

        return self::SUCCESS;
    }

    protected function handleOne(string $id): void
    {
        $this->recordRepo->freeUpMemory();

        /** @var Record $record */
        $record = $this->recordRepo->findById($id);
        $value = $record->getValue($this->language) ?? $record->getPrimaryValue()::getEmpty($this->language);
        $suggest = $value->getSuggested();

        if ($this->earlySkip($record)) {
            return;
        }

        if ($this->useSameValues && $value instanceof SimpleValue) {

            $hasSecondary = Current::getProject()->getSecondaryLanguage() !== null;
            $isSecondary = $this->language === Current::getProject()->getSecondaryLanguage();

            /** @var SimpleValue $primary */
            $primary = $record->getPrimaryValue();
            $values = [$primary];

            if ($hasSecondary && !$isSecondary && $record->getSecondaryValue()) {
                $values[] = $record->getSecondaryValue();
            }

            $translated = $this->recordRepo->findAlreadyTranslated(
                $this->language,
                ...$values
            );

            if ($translated) {
                $this->incCustomCounter('translatedSame');
                $record->setValue($translated->getValue($this->language));
                $this->modelManager->commit(new Transaction([$record]));
                $this->addLogProgress($record->getKey(), "Translation given from `{$translated->getKey()}`", ColorType::Info);
                return;
            }
        }

        try {
            $this->translateService->translate($record, $this->language, $this->llm);
            $translated = $record->getValue($this->language);
            $translatedSuggest = $translated->getSuggested();

            if ($value->isEmpty() && !$translated->isEmpty()) {
                $this->incCustomCounter('translated');
            }

            if (!$value->isEmpty()) {
                if ($suggest === null && $translatedSuggest !== null) {
                    $this->incCustomCounter('suggested');
                }

                if ($suggest && $translatedSuggest && !$suggest->isEquals($translatedSuggest)) {
                    $this->incCustomCounter('suggested');
                }

                if ($suggest && $suggest->isEquals($translatedSuggest)) {
                    $this->incCustomCounter('unchanged');
                }
            }

            $this->modelManager->commit(new Transaction([$record]));
        } catch (Throwable $throwable) {
            $this->addLogProgress($record->getKey(), $throwable->getMessage(), ColorType::Danger);
            captureException($throwable);
            $this->incCustomCounter('errors');
        }
    }

    protected function earlySkip(Record $record): bool
    {
        if ($this->shouldSkipRecord($record, $this->language, $this->keyContains, $this->valueContains)) {
            $this->incCustomCounter('skipped');
            return true;
        }

        $value = $record->getValue($this->language) ?? $record->getPrimaryValue()::getEmpty($this->language);

        $isValueEmpty = $value->isEmpty();
        $isValueFilled = !$isValueEmpty;

        if ($value->verified && $this->excludeVerified) {
            $this->incCustomCounter('skipped');
            return true;
        }

        $excludeTranslatedWithoutWarnings = !$this->includeTranslatedWithoutWarnings;
        if ($isValueFilled && $value->getWarnings() === 0 && $excludeTranslatedWithoutWarnings) {
            $this->incCustomCounter('skipped');
            return true;
        }

        $excludeTranslatedWithWarnings = !$this->includeTranslatedWithWarnings;
        if ($isValueFilled && $value->getWarnings() > 0 && $excludeTranslatedWithWarnings) {
            $this->incCustomCounter('skipped');
            return true;
        }

        $hasSuggestion = $value->getSuggested() && !$value->getSuggested()->isEmpty();
        if ($hasSuggestion && $this->excludeWithSuggestions) {
            $this->incCustomCounter('skipped');
            return true;
        }

        return false;
    }

    protected function getTimeLimit(): int
    {
        return 60 * 60 * 12;
    }

    protected static function name(): string
    {
        return 'batchTranslate';
    }

    protected function channel(): string
    {
        return 'llm';
    }
}