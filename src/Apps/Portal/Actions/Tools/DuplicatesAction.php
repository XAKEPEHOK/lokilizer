<?php
/**
 * Created for sr-app
 * Date: 2025-01-17 01:28
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Tools;

use League\Plates\Engine;
use PrinsFrank\Standards\Language\LanguageAlpha2;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RenderAction;
use XAKEPEHOK\Lokilizer\Models\Localization\Db\RecordRepo;
use XAKEPEHOK\Lokilizer\Models\Project\Project;

class DuplicatesAction extends RenderAction
{

    public function __construct(
        Engine             $renderer,
        private RecordRepo $repo,
    )
    {
        parent::__construct($renderer);
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $params = [
            'language' => $request->getQueryParam('language', ''),
            'caseSensitive' => boolval(intval($request->getQueryParam('caseSensitive', 0))),
            'min' => max(2, intval($request->getQueryParam('min', 5))),
            'max' => (function (string $value) {
                $value = trim($value);

                if (strlen($value) == 0) {
                    return null;
                }

                return max(intval($value), 2, count($this->repo->fetchLanguages()));
            })($request->getQueryParam('max', '')),
        ];

        $duplicates = $this->repo->findDuplicates(
            language: LanguageAlpha2::tryFrom($params['language']),
            caseSensitive: $params['caseSensitive'],
            min: $params['min'],
            max: $params['max'],
        );

        return $this->render($response, 'tools/tools_duplicates', [
            'request' => $request,
            'languages' => $this->repo->fetchLanguages(),
            'form' => $params,
            'duplicates' => $duplicates,
        ]);
    }
}