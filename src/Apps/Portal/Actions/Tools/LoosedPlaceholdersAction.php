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
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RenderAction;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Models\Localization\Db\RecordRepo;
use XAKEPEHOK\Lokilizer\Models\Localization\Record;
use XAKEPEHOK\Lokilizer\Services\GlossaryService;

class LoosedPlaceholdersAction extends RenderAction
{

    public function __construct(
        Engine                  $renderer,
        private RecordRepo      $repo,
        private GlossaryService $glossaryService,
    )
    {
        parent::__construct($renderer);
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $params = [
            'language' => $request->getQueryParam('language', Current::getProject()->getPrimaryLanguage()->value),
            'verified' => (function (string $value) {
                return match ($value) {
                    '1' => true,
                    '0' => false,
                    default => null,
                };
            })($request->getQueryParam('verified', '')),
            'startLowercase' => boolval(intval($request->getQueryParam('startLowercase', 1))),
            'startPunctuation' => trim($request->getQueryParam('startPunctuation', ',.:;&?!%)]}')),
            'endPunctuation' => trim($request->getQueryParam('endPunctuation', ',:&-([{@')),
            'startWhitespace' => boolval(intval($request->getQueryParam('startWhitespace', 1))),
            'endWhitespace' => boolval(intval($request->getQueryParam('endWhitespace', 1))),
        ];

        $found = [];

        $found['startLowercase'] = $this->repo->findValueByRegexArray(
            language: LanguageAlpha2::tryFrom($params['language']),
            regexArray: ['^[\\p{Ll}]'],
            andTrueOrFalse: false,
            verified: $params['verified'],
        );

        if (!empty($params['startPunctuation'])) {
            $found['startPunctuation'] = $this->repo->findValueByRegexArray(
                language: LanguageAlpha2::tryFrom($params['language']),
                regexArray: ['^[' . preg_quote($params['startPunctuation']) . ']'],
                andTrueOrFalse: false,
                verified: $params['verified'],
            );
        }

        if (!empty($params['endPunctuation'])) {
            $found['endPunctuation'] = $this->repo->findValueByRegexArray(
                language: LanguageAlpha2::tryFrom($params['language']),
                regexArray: ['[' . preg_quote($params['endPunctuation']) . ']$'],
                andTrueOrFalse: false,
                verified: $params['verified'],
            );
        }

        $found['startWhitespace'] = $this->repo->findValueByRegexArray(
            language: LanguageAlpha2::tryFrom($params['language']),
            regexArray: ['^\s'],
            andTrueOrFalse: false,
            verified: $params['verified'],
        );

        $found['endWhitespace'] = $this->repo->findValueByRegexArray(
            language: LanguageAlpha2::tryFrom($params['language']),
            regexArray: ['\s$'],
            andTrueOrFalse: false,
            verified: $params['verified'],
        );

        return $this->render($response, 'tools/tools_loosed_placeholders', [
            'request' => $request,
            'languages' => $this->repo->fetchLanguages(true),
            'form' => $params,
            'found' => $found,
            'distill' => fn(Record $record) => $this->glossaryService->getDistilled($record),
        ]);
    }
}