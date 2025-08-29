<?php
/**
 * Created for lokilizer
 * Date: 2025-02-10 15:10
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Glossary;

use DiBify\DiBify\Manager\ModelManager;
use DiBify\DiBify\Manager\Transaction;
use DiBify\DiBify\Model\Reference;
use League\Plates\Engine;
use MongoDB\Driver\Exception\BulkWriteException;
use PrinsFrank\Standards\Language\LanguageAlpha2;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use XAKEPEHOK\Lokilizer\Apps\Console\Handle\Tasks\GlossaryTranslateTaskCommand;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\ApiRuntimeException;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RenderAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Components\PublicExceptionInterface;
use XAKEPEHOK\Lokilizer\Models\Glossary\Db\Storage\GlossaryRepo;
use XAKEPEHOK\Lokilizer\Models\Glossary\GlossaryItem;
use XAKEPEHOK\Lokilizer\Models\Glossary\GlossaryPhrase;
use XAKEPEHOK\Lokilizer\Models\Glossary\SpecialGlossary;
use XAKEPEHOK\Lokilizer\Models\LLM\Db\LLMEndpointRepo;
use XAKEPEHOK\Lokilizer\Models\Localization\Db\RecordRepo;
use XAKEPEHOK\Lokilizer\Models\Project\Components\Role\Permission;

class GlossaryUpdateAction extends RenderAction
{

    public function __construct(
        private GlossaryRepo                 $glossaryRepo,
        private RecordRepo                   $recordRepo,
        private ModelManager                 $modelManager,
        private LLMEndpointRepo              $llmEndpointRepo,
        private GlossaryTranslateTaskCommand $glossaryTranslateTask,
        Engine                               $renderer
    )
    {
        parent::__construct($renderer);
    }

    public function __invoke(Request $request, Response $response): Response|ResponseInterface
    {
        $primaryGlossary = $this->glossaryRepo->findPrimary();

        if ($request->getAttribute('id') === 'primary') {
            $glossary = $primaryGlossary;
            $languages = array_unique(array_merge(
                $this->recordRepo->fetchLanguages(true, false),
                $glossary->getLanguages()
            ), SORT_REGULAR);
        } else if ($request->getAttribute('id') === 'new') {
            Current::guard(Permission::MANAGE_GLOSSARY);
            $glossary = new SpecialGlossary('*');
            $languages = $primaryGlossary->getLanguages();
        } else {
            $glossary = $this->glossaryRepo->findById($request->getAttribute('id'));
            $languages = $primaryGlossary->getLanguages();
        }

        $params = [
            'keyPrefix' => $request->getParsedBodyParam('keyPrefix', ($glossary instanceof SpecialGlossary ? $glossary->getKeyPrefix() : '')),
            'summary' => $request->getParsedBodyParam('summary', $glossary->getSummary() ?? ''),
        ];

        if ($params['keyPrefix'] === '*') {
            $params['keyPrefix'] = '';
        }

        if ($glossary === $primaryGlossary && Current::can(Permission::MANAGE_LANGUAGES)) {
            $addLang = LanguageAlpha2::tryFrom($request->getQueryParam('addLang', ''));
            if ($addLang) {
                Current::guard(Permission::MANAGE_GLOSSARY);
                $languages[] = $addLang;
                $llmModel = $this->llmEndpointRepo->findDefault();
                $uuid = $this->glossaryTranslateTask->publish([
                    'llm' => Reference::to($llmModel),
                    'glossary' => Reference::to($glossary),
                    'languages' => [$addLang->value],
                    'uri' => strval((new RouteUri($request))("glossary/{$glossary->id()}"))
                ]);
                return $response->withRedirect((new RouteUri($request))("progress/{$uuid}"));
            }
        }

        if ($request->getQueryParam('translate') !== null && Current::can(Permission::MANAGE_GLOSSARY)) {
            $llmModel = $this->llmEndpointRepo->findById(
                $request->getQueryParam('translate'),
                new ApiRuntimeException('Invalid LLM model')
            );

            $uuid = $this->glossaryTranslateTask->publish([
                'llm' => Reference::to($llmModel),
                'glossary' => Reference::to($glossary),
                'uri' => strval((new RouteUri($request))("glossary/{$glossary->id()}"))
            ]);

            return $response->withRedirect((new RouteUri($request))("progress/{$uuid}"));
        }

        $error = '';
        if ($request->isPost()) {
            Current::guard(Permission::MANAGE_GLOSSARY);

            try {
                if ($glossary instanceof SpecialGlossary) {

                    if ($request->getParsedBodyParam('delete')) {
                        $this->modelManager->commit(new Transaction([], [$glossary]));
                        return $response->withRedirect((new RouteUri($request))("glossary/list"));
                    }

                    $glossary->setKeyPrefix($params['keyPrefix']);
                }

                $glossary->setSummary($params['summary']);
                $primary = Current::getProject()->getPrimaryLanguage();
                $primaryRows = $request->getParsedBodyParam($primary->value, []);

                $items = [];
                for ($i = 0; $i < count($primaryRows); $i++) {
                    $translations = [];
                    foreach ($languages as $language) {
                        if ($language === $primary) {
                            continue;
                        }
                        $translations[] = new GlossaryPhrase($language, $request->getParsedBodyParam($language->value)[$i]);
                    }
                    $items[] = new GlossaryItem(
                        new GlossaryPhrase($primary, $primaryRows[$i]),
                        $request->getParsedBodyParam('description')[$i] ?? '',
                        ...$translations,
                    );
                }

                $glossary->setItems(...$items);

                $this->modelManager->commit(new Transaction([$glossary]));
                return $response->withRedirect((new RouteUri($request))("glossary/{$glossary->id()}"));
            } catch (PublicExceptionInterface $exception) {
                $error = $exception->getMessage();
            } catch (BulkWriteException $exception) {
                if ($exception->getCode() === 11000) {
                    $error = 'Key prefix already used';
                } else {
                    throw $exception;
                }
            }
        }

        return $this->render($response, 'glossary/glossary_update', [
            'request' => $request,
            'glossary' => $glossary,
            'form' => $params,
            'languages' => $languages,
            'error' => $error,
        ]);
    }
}