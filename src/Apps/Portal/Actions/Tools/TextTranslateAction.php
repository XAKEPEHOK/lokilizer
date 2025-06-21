<?php
/**
 * Created for sr-app
 * Date: 2025-01-15 01:22
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Tools;

use League\Plates\Engine;
use PrinsFrank\Standards\Language\LanguageAlpha2;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\ApiRuntimeException;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RenderAction;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Models\Glossary\Db\Storage\GlossaryRepo;
use XAKEPEHOK\Lokilizer\Models\LLM\Db\LLMEndpointRepo;
use XAKEPEHOK\Lokilizer\Services\GlossaryService;
use XAKEPEHOK\Lokilizer\Services\LLM\LLMService;

class TextTranslateAction extends RenderAction
{

    public function __construct(
        Engine                  $renderer,
        private GlossaryRepo    $glossaryRepo,
        private GlossaryService $glossaryService,
        private LLMService      $llmService,
        private LLMEndpointRepo  $llmEndpointRepo,
    )
    {
        parent::__construct($renderer);
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $llm = $this->llmEndpointRepo->findDefault();

        $params = [
            'language' => $request->getParsedBodyParam('language', Current::getProject()->getSecondaryLanguage()->value),
            'prompt' => $request->getParsedBodyParam('prompt', ''),
            'llm' => $request->getParsedBodyParam('llm', $llm->id()->get()),
            'text' => $request->getParsedBodyParam('text', ''),
        ];

        $primaryGlossary = $this->glossaryRepo->findPrimary();
        $languages = array_filter(
            $primaryGlossary->getLanguages(),
            fn(LanguageAlpha2 $language) => Current::getProject()->getPrimaryLanguage() !== $language
        );

        $error = '';
        $translated = '';
        $cost = 0;
        $glossaries = [];
        if ($request->isPost()) {
            try {
                $language = LanguageAlpha2::tryFrom($params['language']);
                if (is_null($language) || !in_array($language, $languages)) {
                    throw new ApiRuntimeException('Invalid language');
                }

                $llm = $this->llmEndpointRepo->findById($params['llm'], new ApiRuntimeException('Invalid LLM model'));

                $distilledPrimary = clone $primaryGlossary;
                $distilledPrimary->setItems(
                    ...$this->glossaryService->distillItems($primaryGlossary, $params['text'])
                );

                if (count($distilledPrimary->getItems())) {
                    $glossaries[] = $distilledPrimary;
                }

                $glossaryText = $this->glossaryService->glossaryToString($distilledPrimary);

                $prompt = implode(' ', [
                    "You are a professional translator responsible for translating texts for news articles and reports.",
                    "These news articles and reports are related to a mobile, desktop, or web application. Your task is to",
                    "familiarize yourself with the application and its glossary and translate the text while considering",
                    "the glossary, preserving the original style of the text, and adapting the translation to {$language->name}.",
                    "\n",
                    ...(function () use ($glossaryText) {
                        if (is_null($glossaryText)) {
                            return [];
                        }

                        return [
                            "This is information about the application and its glossary:",
                            "\n",
                            "<glossary>\n{$glossaryText}\n</glossary>",
                        ];
                    })(),
                    "\n",
                    "\n",
                    $params['prompt']
                ]);

                $llmResponse = $this->llmService->query(
                    prompt: $prompt,
                    text: $params['text'],
                    model: $llm,
                );

                $cost = $llmResponse->calcPrice();
                $translated = $llmResponse->text;
            } catch (ApiRuntimeException $exception) {
                $error = $exception->getMessage();
            }
        }

        return $this->render($response, 'tools/tools_text_translate', [
            'request' => $request,
            'error' => $error,
            'form' => $params,
            'languages' => $languages,
            'translated' => $translated,
            'cost' => $cost,
            'glossaries' => $glossaries,
        ]);
    }
}