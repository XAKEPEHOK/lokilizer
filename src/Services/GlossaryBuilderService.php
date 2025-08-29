<?php
/**
 * Created for lokilizer
 * Date: 2025-02-14 22:47
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Services;

use Adbar\Dot;
use DiBify\DiBify\Manager\ModelManager;
use DiBify\DiBify\Manager\Transaction;
use PrinsFrank\Standards\Language\LanguageAlpha2;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\ApiRuntimeException;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Models\Glossary\Db\Storage\GlossaryRepo;
use XAKEPEHOK\Lokilizer\Models\Glossary\SpecialGlossary;
use XAKEPEHOK\Lokilizer\Models\LLM\LLMEndpoint;
use XAKEPEHOK\Lokilizer\Models\Localization\Db\RecordRepo;
use XAKEPEHOK\Lokilizer\Services\LLM\LLMService;

class GlossaryBuilderService
{

    public function __construct(
        private LLMService      $service,
        private RecordRepo      $recordRepo,
        private GlossaryRepo    $glossaryRepo,
        private GlossaryService $glossaryService,
        private ModelManager    $modelManager,
    )
    {
    }

    public function buildGlossary(string $keyPrefix, LLMEndpoint $llmModel): SpecialGlossary
    {
        $language = Current::getProject()->getPrimaryLanguage();

        $records = $this->recordRepo->findWithKeyPrefix($keyPrefix);
        if (empty($records)) {
            throw new ApiRuntimeException('Records not found for ' . $keyPrefix);
        }

        $primaryGlossary = $this->glossaryRepo->findPrimary();
        $primarySummary = $this->glossaryService->distillToString($primaryGlossary, ...$records);

        $glossary = $this->glossaryRepo->findByKeyPrefix($keyPrefix) ?? new SpecialGlossary($keyPrefix);

        $prompt = [
            "You are an expert in analyzing application translation files that use the JSON format. Below is a JSON",
            "block containing translations for a specific logical UI element. By 'logical UI element, I mean an independent",
            "component or set of translations related to a particular functional part of the application (for example, a",
            "registration form, product creation/editing form, statistics screen, etc.)",
        ];

        if ($primarySummary) {
            $prompt = array_merge($prompt, [
                "\n",
                "\n",
                'Additional context regarding the application (just for your understanding):',
                "\n",
                "<AdditionalContext>",
                "\n",
                $primarySummary,
                "\n",
                "</AdditionalContext>",
            ]);
        }

        if ($glossary->isComplete()) {
            $prompt = array_merge($prompt, [
                "\n",
                "\n",
                "Old glossary of this block: ",
                "\n",
                "<OldGlossary>",
                "\n",
                json_encode($glossary, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                "\n",
                "</OldGlossary>",
            ]);
        }

        $prompt = array_merge($prompt, [
            "\n",
            "\n",
            "Your task:",
            "\n",
        ]);

        if ($glossary->isComplete()) {
            $prompt = array_merge($prompt, [
                "- Based on the new JSON block and the old summary and glossary, create an updated brief summary of this translation block.",
                "\n",
            ]);
        } else {
            $prompt = array_merge($prompt, [
                "- Based on the new JSON block create an updated brief summary of this translation block.",
                "\n",
            ]);
        }

        $languages = $primaryGlossary->getLanguages();
        if (empty($languages)) {
            $languages = $this->recordRepo->fetchLanguages(true);
        }
        $languages = array_map(fn(LanguageAlpha2 $lang) => $lang->value, $languages);

        $prompt = array_merge($prompt, [
            "- Reflect the purpose, main functions, and features of the block.",
            "\n",
            "- Correct any outdated or superfluous information from the old summary.",
            "\n",
            "- Incorporate any new translation strings into the summary.",
            "\n",
//            "- Ensure that any specialized words and phrases especially those rarely used in everyday language are",
//            "accurately preserved and conveyed in your summary.",
//            ...(function () use ($primarySummary) {
//                if (!$primarySummary) {
//                    return [];
//                }
//                return [
//                    "\n",
//                    "- Skip and do not repeat terminology, that already exists in <AdditionalContext/>",
//                ];
//            })(),
//            "\n",
            "- Brief summary SHOULD BE IN " . strtoupper($language->name) . '!!!',
            "\n",
            "\n",
            "Response should be a JSON with keys:",
            "\n",
            "`summary` - that contains brief summary",
//            "\n",
//            "`glossary` - that contains array of objects with terminology",
//            "\n",
//            "Every terminology in glossary should be a JSON object with structure like:",
//            "\n",
//            json_encode([
//                'translations' => array_combine(
//                    $languages,
//                    array_map(fn(string $lang) => "Terminology in " . LanguageAlpha2::from($lang)->name . ". Fill if empty", $languages),
//                ),
//                'description' => 'terminology description',
//            ]),
        ]);

        $dot = new Dot();
        foreach ($records as $record) {
            $dot->set($record->getKey(), $record->getValue($language)->getStringContext());
        }

        $response = $this->service->query(
            prompt: implode(" ", $prompt),
            text: json_encode($dot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            model: $llmModel,
            format: [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'glossary',
                    'strict' => true,
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'summary' => ['type' => 'string'],
//                            'glossary' => [
//                                'type' => 'array',
//                                'items' => [
//                                    'type' => 'object',
//                                    'properties' => [
//                                        'translations' => [
//                                            'type' => 'object',
//                                            'properties' => array_combine(
//                                                $languages,
//                                                array_map(fn() => ['type' => 'string'], $languages),
//                                            ),
//                                            'required' => $languages,
//                                            'additionalProperties' => false
//                                        ],
//                                        'description' => [
//                                            'type' => 'string'
//                                        ]
//                                    ],
//                                    'required' => ['translations', 'description'],
//                                    'additionalProperties' => false
//                                ]
//                            ],
                        ],
                        'required' => ['summary', /*'glossary'*/],
                        'additionalProperties' => false
                    ],
                ],
            ],
        );

        $data = $response->getAsJson();

        $glossary->setSummary($data['summary']);
//        $items = [];
//        foreach ($data['glossary'] as $glossaryData) {
//            $translations = $glossaryData['translations'];
//            $primaryPhrase = $translations[$language->value];
//            unset($translations[$language->value]);
//
//            $phrases = [];
//            foreach ($translations as $lang => $translation) {
//                $phrases[] = new GlossaryPhrase(LanguageAlpha2::from($lang), $translation);
//            }
//
//            $items[] = new GlossaryItem(
//                new GlossaryPhrase($language, $primaryPhrase),
//                $glossaryData['description'],
//                ...$phrases
//            );
//        }
//        $glossary->setItems(...$items);
        $glossary->LLMCost()->add($response->calcPrice());

        $this->modelManager->commit(new Transaction([$glossary]));
        return $glossary;
    }

}