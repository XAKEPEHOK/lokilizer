<?php
/**
 * Created for sr-app
 * Date: 2025-01-16 20:21
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Project;

use DiBify\DiBify\Manager\ModelManager;
use DiBify\DiBify\Manager\Transaction;
use League\Plates\Engine;
use PrinsFrank\Standards\Language\LanguageAlpha2;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RenderAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Components\Parsers\FileFormatter;
use XAKEPEHOK\Lokilizer\Models\Project\Components\EOLFormat;
use XAKEPEHOK\Lokilizer\Models\Project\Components\PlaceholderFormat;
use XAKEPEHOK\Lokilizer\Models\Project\Components\Role\Permission;

class ProjectSettingsAction extends RenderAction
{

    public function __construct(
        Engine $renderer,
        private readonly ModelManager $modelManager,
    )
    {
        parent::__construct($renderer);
    }

    public function __invoke(Request $request, Response $response): Response|ResponseInterface
    {
        Current::guard(Permission::MANAGE_PROJECT_SETTINGS);
        $project = Current::getProject();

        $params = [
            'name' => trim($request->getParsedBodyParam('name', $project->getName())),
            'primary' => $request->getParsedBodyParam('primary', $project->getPrimaryLanguage()->value),
            'secondary' => $request->getParsedBodyParam('secondary', $project->getSecondaryLanguage()?->value ?? ''),
            'llm' => $request->getParsedBodyParam('llm', $project->getDefaultLLM()?->id()->get()),
            'placeholders' => $request->getParsedBodyParam('placeholders', $project->getPlaceholdersFormat()->value),
            'eol' => $request->getParsedBodyParam('eol', base64_encode($project->getEOLFormat()->value)),
            'fileFormatter' => $request->getParsedBodyParam('fileFormatter', FileFormatter::I18NEXT->value),
        ];

        $error = '';
        if ($request->isPost()) {
            try {
                $primary = LanguageAlpha2::tryFrom($params['primary']);
                if (is_null($primary)) {
                    throw new RuntimeException('Invalid language');
                }

                $secondary = LanguageAlpha2::tryFrom($params['secondary']);
                if ($primary === $secondary) {
                    $secondary = null;
                }

                $placeholders = PlaceholderFormat::tryFrom($params['placeholders']);
                if (is_null($placeholders)) {
                    throw new RuntimeException('Invalid placeholders format');
                }

                $eol = EOLFormat::tryFrom(base64_decode($params['eol']));
                if (is_null($eol)) {
                    throw new RuntimeException('Invalid eol format');
                }

                $fileFormat = FileFormatter::tryFrom($params['fileFormatter']);
                if (is_null($fileFormat)) {
                    throw new RuntimeException('Invalid file format');
                }

                if (!isset(Current::getLLMEndpoints()[$params['llm']])) {
                    throw new RuntimeException('Invalid LLM endpoint');
                }

                $project->setName($params['name']);
//                $project->setPrimaryLanguage($primary);
                $project->setSecondaryLanguage($secondary);
                $project->setPlaceholders($placeholders);
                $project->setEOLFormat($eol);
                $project->setFileFormatter($fileFormat);
                $project->setDefaultLLM(Current::getLLMEndpoints()[$params['llm']]);

                $this->modelManager->commit(new Transaction([$project]));

                return $response->withRedirect((new RouteUri($request))(""));

            } catch (RuntimeException $exception) {
                $error = $exception->getMessage();
            }
        }

        return $this->render($response, 'project/project_settings', [
            'request' => $request,
            'project' => $project,
            'form' => $params,
            'error' => $error,
        ]);
    }

}