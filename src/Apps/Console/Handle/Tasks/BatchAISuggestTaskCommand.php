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
use XAKEPEHOK\Lokilizer\Models\Glossary\Db\Storage\GlossaryRepo;
use XAKEPEHOK\Lokilizer\Models\Glossary\Glossary;
use XAKEPEHOK\Lokilizer\Models\Glossary\PrimaryGlossary;
use XAKEPEHOK\Lokilizer\Models\Glossary\SpecialGlossary;
use XAKEPEHOK\Lokilizer\Models\LLM\Db\LLMEndpointRepo;
use XAKEPEHOK\Lokilizer\Models\LLM\LLMEndpoint;
use XAKEPEHOK\Lokilizer\Models\Localization\Components\AbstractValue;
use XAKEPEHOK\Lokilizer\Models\Localization\Db\RecordRepo;
use XAKEPEHOK\Lokilizer\Models\Localization\Record;
use XAKEPEHOK\Lokilizer\NotFoundException;
use XAKEPEHOK\Lokilizer\Services\GlossaryService;
use XAKEPEHOK\Lokilizer\Services\LLM\LLMService;
use XAKEPEHOK\Lokilizer\Services\LLMRecordService;
use function Sentry\captureException;

class BatchAISuggestTaskCommand extends BatchRecordsParallelCommand
{

    use RecordFilterTrait;

    private LanguageAlpha2 $language;
    private string $keyContains;
    private string $valueContains;
    private LLMEndpoint $llm;
    private string $prompt;
    private bool $excludeWithSuggestions;
    private bool $excludeVerified;

    public function __construct(
        private ModelManager     $modelManager,
        private GlossaryRepo     $glossaryRepo,
        private GlossaryService  $glossaryService,
        private LLMEndpointRepo  $llmEndpointRepo,
        private LLMRecordService $LLMRecordService,
        private LLMService       $LLMService,
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
        $this->prompt = $data['prompt'];
        $this->excludeWithSuggestions = boolval($data['excludeWithSuggestions']);
        $this->excludeVerified = boolval($data['excludeVerified']);

        if ($id) {
            try {
                $this->handleOne($id);
            } catch (Throwable $throwable) {
                captureException($throwable);
            }
            return self::SUCCESS;
        }

        $this->addLogProgress('Exclude verified', $this->excludeVerified ? 'yes' : 'no', ColorType::Nothing);
        $this->addLogProgress('Prompt', $this->prompt, ColorType::Nothing);
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
        $this->addLogProgress('Duplicates', $this->getCustomCounter('duplicates'), ColorType::Info);
        $this->addLogProgress('Suggested', $this->getCustomCounter('suggested'), ColorType::Info);

        $this->finishProgress(ColorType::Success, 'Successfully handled');

        return self::SUCCESS;
    }

    protected function handleOne(string $id): void
    {
        $this->recordRepo->freeUpMemory();

        /** @var Record $record */
        $record = $this->recordRepo->findById($id);
        $value = $record->getValue($this->language);

        if ($this->earlySkip($record)) {
            return;
        }

        try {
            $matchedGlossaries = array_filter(
                $this->glossaryRepo->findAll(),
                function (Glossary $glossary) use ($record) {
                    if ($glossary instanceof PrimaryGlossary) {
                        return true;
                    }

                    if ($glossary instanceof SpecialGlossary) {
                        return $glossary->isForKey($record->getKey());
                    }

                    return false;
                },
            );

            $glossaryTexts = [];
            foreach ($matchedGlossaries as $glossary) {
                $distilledString = $this->glossaryService->distillToString($glossary);
                if ($distilledString) {
                    $glossaryTexts[] = $distilledString;
                }
            }

            $languages = array_unique([
                $this->language,
                Current::getProject()->getPrimaryLanguage(),
                Current::getProject()->getSecondaryLanguage() ?? Current::getProject()->getPrimaryLanguage()
            ], SORT_REGULAR);

            $placeholder = Current::getProject()->getPlaceholdersFormat()->wrap('placeholder');
            $systemPrompt = implode(' ', [
                "You are an application translation assistant responsible for localizing application translation files",
                "from one language to another.",
                "\n",
                ...(function () use ($glossaryTexts) {
                    if (empty($glossaryTexts)) {
                        return [];
                    }

                    return [
                        "This is information about the application and its glossary:",
                        "\n",
                        "<glossary>",
                        "\n",
                        implode("\n\n", $glossaryTexts),
                        "\n",
                        "</glossary>",
                    ];
                })(),
                "\n",
                "\n",
                "You must analyze the meaning in the {$this->language->value} language, apply the actions described in the",
                "instructions, and return the processed value as a JSON object under the `value` key, maintaining the",
                "same data structure: ",
                "\n",
                "```" . json_encode(['value' => $value], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "```",
                "\n",
                "If no changes are required, return",
                json_encode(['value' => null]),
                "\n",
                "When translating, it is important to preserve existing line breaks \\n if present, as well as",
                "placeholders in the format {$placeholder}.",
                "\n",
                "\n",
                "```" . json_encode($this->LLMRecordService->representRecord($record, ...$languages), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "```"
            ]);

            $response = $this->LLMService->query(
                prompt: $systemPrompt,
                text: $this->prompt,
                model: $this->llm,
                format: [
                    'type' => 'json_schema',
                    'json_schema' => [
                        'name' => 'suggest' . ucfirst($record::getModelAlias()),
                        'strict' => true,
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'value' => $this->LLMRecordService->getJsonSchemaType($record, $this->language)
                            ],
                            'required' => ['value'],
                            'additionalProperties' => false
                        ],
                    ],
                ],
            );

            $jsonResponse = $response->getAsJson() ?? [];
            $rawValue = $jsonResponse['value'] ?? null;

            if (is_null($rawValue)) {
                $this->incCustomCounter('unchanged');
            } else {
                $suggested = AbstractValue::parse($record->getPrimaryValue()::getEmpty($this->language), $rawValue);
                $record->getValue($this->language)->setSuggested($suggested);
                $this->incCustomCounter('suggested');
            }

            $record->LLMCost()->add($response->calcPrice());
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

        $value = $record->getValue($this->language);
        if ($value === null) {
            $this->incCustomCounter('skipped');
            return true;
        }

        if ($value->verified && $this->excludeVerified) {
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
        return 'batchSuggest';
    }

    protected function channel(): string
    {
        return 'llm';
    }
}