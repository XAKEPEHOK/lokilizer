<?php
/**
 * Created for lokilizer
 * Date: 2025-01-31 15:22
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Services;

use PrinsFrank\Standards\Language\LanguageAlpha2;
use TypeError;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Models\Glossary\Db\Storage\GlossaryRepo;
use XAKEPEHOK\Lokilizer\Models\Glossary\Glossary;
use XAKEPEHOK\Lokilizer\Models\Glossary\GlossaryItem;
use XAKEPEHOK\Lokilizer\Models\Glossary\SpecialGlossary;
use XAKEPEHOK\Lokilizer\Models\LLM\LLMEndpoint;
use XAKEPEHOK\Lokilizer\Models\Localization\Components\AbstractPluralValue;
use XAKEPEHOK\Lokilizer\Models\Localization\Components\AbstractValue;
use XAKEPEHOK\Lokilizer\Models\Localization\Db\RecordRepo;
use XAKEPEHOK\Lokilizer\Models\Localization\PluralRecord;
use XAKEPEHOK\Lokilizer\Models\Localization\Record;
use XAKEPEHOK\Lokilizer\Models\Localization\SimpleRecord;
use XAKEPEHOK\Lokilizer\Services\LLM\LLMService;
use XAKEPEHOK\Lokilizer\Services\LLM\Models\LLMResponse;

class TranslateService extends LLMRecordService
{

    public function __construct(
        private LLMService      $service,
        private GlossaryRepo    $glossaryRepo,
        private GlossaryService $glossaryService,
        private RecordRepo      $recordRepo,
    )
    {
        parent::__construct($this->recordRepo);
    }

    public function translate(Record $record, LanguageAlpha2 $language, LLMEndpoint $llmModel): Record
    {
        if ($record instanceof PluralRecord) {
            return $this->translatePlural($record, $language, $llmModel);
        }

        if ($record instanceof SimpleRecord) {
            return $this->translateSimple($record, $language, $llmModel);
        }

        throw new TypeError('Invalid class type');
    }

    protected function translateSimple(SimpleRecord $record, LanguageAlpha2 $to, LLMEndpoint $llmModel): Record
    {
        $prompt = $this->commonPrompt($record, $to);

        $representation = $this->representRecord($record, ...$this->getLanguages());
        $representation['from'] = $representation['values'];
        unset($representation['values']);

        $response = $this->service->query(
            prompt: $prompt,
            text: json_encode($representation, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            model: $llmModel,
            format: [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'plural',
                    'strict' => true,
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'comment' => ['type' => 'string'],
                            'value' => $this->getJsonSchemaType($record, $to),
                        ],
                        'required' => ['comment', 'value'],
                        'additionalProperties' => false
                    ],
                ],
            ],
        );

        return $this->handleChanges($response, $record, $to);
    }

    protected function translatePlural(PluralRecord $record, LanguageAlpha2 $to, LLMEndpoint $llmModel): Record
    {
        $prompt = $this->commonPrompt($record, $to);
        $prompt .= implode(" ", [
            "You need to translate the strings while considering pluralization into {$to->name} which has the following",
            "{$record->getType()} categories:",
            "\n",
            $this->pluralCategories($to, $record->getType()),
            "\n",
            ""
        ]);

        $representation = $this->representRecord($record, ...$this->getLanguages());
        $representation['from'] = $representation['values'];
        unset($representation['values']);

        $response = $this->service->query(
            prompt: $prompt,
            text: json_encode($representation, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            model: $llmModel,
            format: [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'plural',
                    'strict' => true,
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'comment' => ['type' => 'string'],
                            'value' => $this->getJsonSchemaType($record, $to)
                        ],
                        'required' => ['comment', 'value'],
                        'additionalProperties' => false
                    ],
                ],
            ],
        );

        return $this->handleChanges($response, $record, $to);
    }

    private function handleChanges(LLMResponse $response, Record $record, LanguageAlpha2 $language): Record
    {
        $data = $response->getAsJson();

        if (empty($record->getComment())) {
            $record->setComment($data['comment']);
        }

        $newValue = AbstractValue::parse($record->getPrimaryValue()::getEmpty($language), $data['value']);

        $currentValue = $record->getValue($language);
        if (!$currentValue || $currentValue->isEmpty()) {
            $record->setValue($newValue);
        } else {
            $currentValue->setSuggested($newValue);
        }

        $record->LLMCost()->add($response->calcPrice());
        return $record;
    }

    private function commonPrompt(Record $record, LanguageAlpha2 $to): string
    {
        $languages = $this->getLanguages();
        $primaryLang = $languages[0];

        $inputExample = [];
        foreach ($languages as $language) {
            $inputExample['from'][$language->name] = [
                'context' => 'Any type optional context here in ' . $language->value . ' language',
                'value' => 'Value, that should be translated to ' . $to->name . ' language',
            ];
        }

        $primaryGlossary = $this->glossaryRepo->findPrimary();
        $glossaries = [
            $this->distillToString($primaryGlossary, $to, $record),
            ...array_map(
                fn(SpecialGlossary $specialGlossary) => $this->distillToString($specialGlossary, $to, $record),
                $this->glossaryRepo->findForKeys($record->getKey())
            )
        ];

        $glossaries = array_filter($glossaries);
        $glossary = [];
        if (!empty($glossaries)) {
            $glossary[] = "This is important information about the application and its glossary. USE TERMINOLOGY FROM GLOSSARY WHILE TRANSLATING:";
            $glossary[] = "\n";
            $glossary[] = "<glossary>\n" . implode("\n\n", $glossaries) . "\n</glossary>";
        }

        $placeholder = Current::getProject()->getPlaceholdersFormat()->wrap('placeholder');
        return implode(" ", [
            "You are a professional translator of websites and computer applications. You will receive strings from an",
            "application localization file and translate them into another language. When translating, it is important",
            "to preserve existing line breaks \\n if present, as well as placeholders in the format {$placeholder}.",
            "\n",
            "\n",
            ...$glossary,
            "\n",
            "\n",
            "The translation data will be provided in JSON format:",
            "\n",
            json_encode($inputExample, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            "\n",
            "To better understand the context and improve translation quality, the `from` key contains the same values",
            "along with the context but in different languages to help grasp the semantics of the sentence. The `from.value`",
            "key contains the value that needs to be translated (it can be either a string or an object). The `from.context`",
            "key contains neighboring strings that help better understand the usage context of the sentence.",
            "\n",
            "You should analyze the strings and their context, write a brief comment on what the text is about, translate",
            "`from.value` into {$to->name} language, and output the following JSON format:",
            "\n",
            "\n",
            json_encode([
                "comment" => "A brief comment in {$primaryLang->name} language on what the value is about WITHOUT words like 'Text is about...'",
                "value" => "Value translated to {$to->name} language",
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            "\n",
            "\n",
        ]);
    }

    private function distillToString(Glossary $glossary, LanguageAlpha2 $language, Record $record): ?string
    {
        $summary = $glossary->getSummary();
        if (is_null($summary)) {
            return null;
        }

        $items = $this->glossaryService->distillItems($glossary, $record);
        if (!empty($items)) {
            $project = Current::getProject();
            $strings = array_map(
                function (GlossaryItem $item) use ($project, $language) {
                    $text = $item->toString($project->getPrimaryLanguage(), $project->getSecondaryLanguage());

                    if ($phrase = $item->getByLanguage($language)) {
                        $text.= "<TranslationDictionary>";
                        $text.= "Translate phrase \"{$item->primary->phrase}\" to \"{$language->name}\" as \"{$phrase->phrase}\". ";
                        if ($project->getSecondaryLanguage() && $item->getByLanguage($project->getSecondaryLanguage())) {
                            $text.= "Translate phrase \"{$item->getByLanguage($project->getSecondaryLanguage())->phrase}\" to \"{$language->name}\" as \"{$phrase->phrase}\". Adjust the capitalization of the terms to fit naturally within the surrounding text. ";
                        }
                        $text.="</TranslationDictionary>";
                    }

                    return $text;
                },
                $items
            );
            $summary.= "\n\n## " . implode("\n\n## ", $strings);
        }

        return $summary;
    }

    /**
     * @return LanguageAlpha2[]
     */
    private function getLanguages(): array
    {
        $project = Current::getProject();
        $primaryLang = $project->getPrimaryLanguage();
        $secondaryLang = $project->getSecondaryLanguage();
        return array_filter([$primaryLang, $secondaryLang]);
    }

    private function pluralCategories(LanguageAlpha2 $language, string $type): string
    {
        $text = [];
        foreach (AbstractPluralValue::getCategoriesForLanguage($language, $type) as $category) {
            $text[] = "- `{$category}`";
        }
        return implode("\n", $text);
    }

}