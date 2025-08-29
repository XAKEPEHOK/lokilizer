<?php

namespace XAKEPEHOK\Lokilizer\Apps\Console\Handle\Tasks;

use DiBify\DiBify\Manager\ModelManager;
use DiBify\DiBify\Manager\Transaction;
use DiBify\DiBify\Model\Reference;
use PrinsFrank\Standards\Language\LanguageAlpha2;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use XAKEPEHOK\Lokilizer\Apps\Console\Handle\HandleTaskCommand;
use XAKEPEHOK\Lokilizer\Components\ColorType;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Models\Glossary\Db\Storage\GlossaryRepo;
use XAKEPEHOK\Lokilizer\Models\Glossary\Glossary;
use XAKEPEHOK\Lokilizer\Models\Glossary\GlossaryPhrase;
use XAKEPEHOK\Lokilizer\Models\Glossary\PrimaryGlossary;
use XAKEPEHOK\Lokilizer\Models\Glossary\SpecialGlossary;
use XAKEPEHOK\Lokilizer\Models\LLM\LLMEndpoint;
use XAKEPEHOK\Lokilizer\Services\LLM\LLMService;

class GlossaryTranslateTaskCommand extends HandleTaskCommand
{

    public function __construct(
        ContainerInterface $container,
        private GlossaryRepo $glossaryRepo,
        private LLMService $LLMService,
        private ModelManager $modelManager,
    )
    {
        parent::__construct($container);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $data = $this->getTaskData($input);

        $primaryGlossary = $this->glossaryRepo->findPrimary();

        /** @var Glossary|PrimaryGlossary|SpecialGlossary $glossary */
        $glossary = Reference::fromArray($data['glossary'])->getModel();
        $this->addLogProgress(
            'Glossary',
            $glossary instanceof PrimaryGlossary ? 'Primary' : $glossary->getKeyPrefix(),
            ColorType::Nothing
        );

        /** @var LLMEndpoint $llm */
        $llm = Reference::fromArray($data['llm'])->getModel();
        $this->addLogProgress(
            'LLM',
            $llm->getName(),
            ColorType::Nothing
        );

        $passedLanguages = array_unique(array_map(
            fn(string $lang) => LanguageAlpha2::from($lang),
            $data['languages'] ?? []
        ));

        if (empty($passedLanguages)) {
            $passedLanguages = $primaryGlossary->getLanguages();
        }

        $sourceLanguages = Current::getProject()->getLanguages();

        /** @var LanguageAlpha2[] $targetLanguages */
        $targetLanguages = array_values(array_filter(
            $passedLanguages,
            fn(LanguageAlpha2 $lang) => !in_array($lang, $sourceLanguages)
        ));

        $languages = [...$sourceLanguages, ...$targetLanguages];

        $loosedTranslations = [];

        $total = 0;
        foreach ($glossary->getItems() as $item) {
            foreach ($targetLanguages as $language) {
                if ($item->getByLanguage($language) === null) {
                    $loosedTranslations[$language->value] = true;
                    $total++;
                }
            }
        }
        $this->setMaxProgress($total);

        $this->addLogProgress(
            'Languages',
            implode(', ', array_map(fn(LanguageAlpha2 $lang) => $lang->name, $targetLanguages)),
            ColorType::Nothing
        );

        foreach ($targetLanguages as $language) {

            $promptLanguages = array_map(
                fn(LanguageAlpha2 $lang) => $lang->value,
                [...$sourceLanguages, $language],
            );

            if (!isset($loosedTranslations[$language->value])) {
                $this->addLogProgress(
                    $language->name,
                    "✅ Everything already translated",
                    ColorType::Info
                );
                continue;
            }

            $this->addLogProgress(
                'Translating to',
                $language->name,
                ColorType::Info
            );

            $prompt = [
                "You are working with a glossary used for translating an application into other languages. Your task is to ",
                "analyze each application term and its description, and if it is not translated, translate it into ",
                $language->name
            ];

            if ($primaryGlossary->getSummary()) {
                $prompt = array_merge($prompt, [
                    "\n",
                    "\n",
                    'Additional context regarding the application (just for your understanding):',
                    "\n",
                    "<AdditionalContext>",
                    "\n",
                    $primaryGlossary->getSummary(),
                    "\n",
                    "</AdditionalContext>",
                ]);
            }

            if ($primaryGlossary !== $glossary && $glossary->getSummary()) {
                $prompt = array_merge($prompt, [
                    "\n",
                    "\n",
                    'Additional context about current glossary scope (just for your understanding):',
                    "\n",
                    "<AdditionalContextScope>",
                    "\n",
                    $glossary->getSummary(),
                    "\n",
                    "</AdditionalContextScope>",
                ]);
            }

            $prompt = array_merge($prompt, [
                "\n",
                "Response should be a JSON object with structure like:",
                "\n",
                json_encode(
                    array_combine(
                        $promptLanguages,
                        array_map(fn(string $lang) => "Terminology in " . LanguageAlpha2::from($lang)->name . ". Fill if empty", $promptLanguages),
                    ),
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ),
            ]);

            foreach ($glossary->getItems() as $item) {

                $phrase = $item->getByLanguage($language);
                if ($phrase !== null) {
                    continue;
                }

                $text = [];

                if (!empty($item->description)) {
                    $text[] = "<StringContext>{$item->description}</StringContext>";
                    $text[] = "\n";
                }

                $translations = [];
                foreach ($sourceLanguages as $sourceLanguage) {
                    $translations[$sourceLanguage->value] = $item->getByLanguage($sourceLanguage)?->phrase ?? '';
                }
                $translations[$language->value] = '';

                $text[] = json_encode($translations, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                try {
                    $response = $this->LLMService->query(
                        prompt: implode(" ", $prompt),
                        text: implode("\n", $text),
                        model: $llm,
                        format: [
                            'type' => 'json_schema',
                            'json_schema' => [
                                'name' => 'glossaryPhrases',
                                'strict' => true,
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => array_combine(
                                        $promptLanguages,
                                        array_map(fn() => ['type' => 'string'], $promptLanguages),
                                    ),
                                    'required' => $languages,
                                    'additionalProperties' => false
                                ],
                            ],
                        ],
                        failAttempts: 1
                    );

                    $responseData = $response->getAsJson();

                    if (!isset($responseData[$language->value]) || !is_string($responseData[$language->value])) {
                        $this->addLogProgress($language->name, "LLM return invalid data", ColorType::Danger);
//                        $this->addLogProgress($language->name, $response->text, ColorType::Danger);
                        continue;
                    }

                    $phrase = new GlossaryPhrase($language, $responseData[$language->value]);
                    $item->addTranslation($phrase);

                    $glossary->LLMCost()->add($response->calcPrice());
                    $this->modelManager->commit(new Transaction([$glossary]));

                    $this->addLogProgress($item->primary, $phrase->phrase, ColorType::Success);

                } catch (Throwable $throwable) {
                    $this->addLogProgress($language->name, $throwable->getMessage(), ColorType::Danger);
                }

                $this->incCurrentProgress();
            }
        }

        $this->finishProgress(ColorType::Info, $data['uri']);
        return self::SUCCESS;
    }

    protected function getTimeLimit(): int
    {
        return 300;
    }

    protected static function name(): string
    {
        return 'glossary-translate';
    }

    protected function channel(): string
    {
        return 'glossary';
    }
}