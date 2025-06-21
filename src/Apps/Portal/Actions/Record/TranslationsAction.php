<?php
/**
 * Created for sr-app
 * Date: 2025-01-15 01:22
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Record;

use League\Plates\Engine;
use PrinsFrank\Standards\Language\LanguageAlpha2;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RenderAction;
use XAKEPEHOK\Lokilizer\Models\Localization\Db\RecordRepo;
use XAKEPEHOK\Lokilizer\Models\Localization\PluralRecord;
use XAKEPEHOK\Lokilizer\Models\Localization\Record;
use XAKEPEHOK\Lokilizer\Models\Localization\SimpleRecord;
use XAKEPEHOK\Lokilizer\Services\GlossaryService;

class TranslationsAction extends RenderAction
{

    /** @var array LanguageAlpha2[] */
    private array $languages = [];

    public function __construct(
        Engine                           $renderer,
        private readonly RecordRepo      $recordRepo,
        private readonly GlossaryService $glossaryService,
    )
    {
        parent::__construct($renderer);
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $this->languages = $this->recordRepo->fetchLanguages(true);

        if ($this->recordRepo->count() === 0) {
            return $this->render($response, 'home/getting_started', [
                'request' => $request,
            ]);
        }

        return $this->render($response, 'record/record_translations', [
            'request' => $request,
            'languages' => $this->languages,
            'distill' => fn(Record $record) => $this->glossaryService->getDistilled($record),
            'fsp' => [
                'repo' => $this->recordRepo,
                'sort' => 'touchedAt',
                'columns' => [
                    'language' => [
                        'header' => 'Language',
                        'filter' => (function () {
                            $languages = $this->recordRepo->fetchLanguages(true);
                            return array_combine(
                                array_map(
                                    fn(LanguageAlpha2 $language) => $language->value,
                                    $languages
                                ),
                                array_map(
                                    fn(LanguageAlpha2 $language) => $language->name,
                                    $languages
                                ),
                            );
                        })(),
                    ],
                    'key' => [
                        'header' => 'Key',
                        'filter' => 'string',
                        'sortable' => true,
                    ],
                    'value' => [
                        'header' => "Value",
                        'filter' => 'string',
                        'sortable' => true,
                    ],
                    'comment' => [
                        'header' => "Comment",
                        'filter' => 'string',
                        'sortable' => true,
                    ],
                    'type' => [
                        'header' => 'Type',
                        'filter' => [
                            SimpleRecord::getModelAlias() => 'Simple',
                            PluralRecord::getModelAlias() => 'Plural',
                        ],
                    ],
                    'updatedAt' => [
                        'header' => 'Updated',
                        'filter' => 'datetime',
                        'sortable' => true,
                    ],
                    'touchedAt' => [
                        'header' => 'Touched',
                        'filter' => 'datetime',
                        'sortable' => true,
                    ],
                    'createdAt' => [
                        'header' => 'Created',
                        'filter' => 'datetime',
                        'sortable' => true,
                    ],
                    'suggested' => [
                        'header' => 'Suggestion',
                        'filter' => [
                            0 => '💤 No suggestion',
                            1 => '💡 Has suggestion',
                        ],
                    ],
                    'verified' => [
                        'header' => 'Verified',
                        'filter' => [
                            0 => '➖ Not verified',
                            1 => '🛡️ Verified',
                        ],
                    ],
                    'warnings' => [
                        'header' => 'Warnings',
                        'filter' => [
                            0 => '🟢 Valid',
                            1 => '⚠️ Has warnings',
                        ],
                        'sortable' => true,
                    ],
                    'position' => [
                        'header' => 'Position',
                        'filter' => 'number',
                        'sortable' => true,
                    ],
                ],
            ],
        ]);
    }

    protected function fspPrepareFilters(array $columns, array $params): array
    {
        $params['outdated'] = false;
        return parent::fspPrepareFilters($columns, $params);
    }
}