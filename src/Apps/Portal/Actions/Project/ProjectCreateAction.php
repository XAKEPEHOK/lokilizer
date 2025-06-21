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
use XAKEPEHOK\Lokilizer\Models\LLM\LLMEndpoint;
use XAKEPEHOK\Lokilizer\Models\LLM\LLMPricing;
use XAKEPEHOK\Lokilizer\Models\Project\Components\EOLFormat;
use XAKEPEHOK\Lokilizer\Models\Project\Components\PlaceholderFormat;
use XAKEPEHOK\Lokilizer\Models\Project\Project;

class ProjectCreateAction extends RenderAction
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
        $params = [
            'name' => trim($request->getParsedBodyParam('name', '')),
            'primary' => $request->getParsedBodyParam('primary', LanguageAlpha2::English->value),
            'secondary' => $request->getParsedBodyParam('secondary', ''),
            'placeholders' => $request->getParsedBodyParam('placeholders', PlaceholderFormat::JS->value),
            'eol' => $request->getParsedBodyParam('eol', base64_encode(EOLFormat::N->value)),
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

                $project = new Project($params['name'], Current::getUser());
                $project->setPrimaryLanguage($primary);
                $project->setSecondaryLanguage($secondary);
                $project->setPlaceholders($placeholders);
                $project->setEOLFormat($eol);
                $project->setFileFormatter($fileFormat);

                Current::setProject($project);

                $gpt4_1 = new LLMEndpoint(
                    'ChatGPT 4.1',
                    'https://api.openai.com/v1',
                    '',
                    'gpt-4.1',
                    new LLMPricing(2, 8),
                );

                $deepseek = new LLMEndpoint(
                    'Deepseek v3',
                    'https://api.deepseek.com/v1',
                    '',
                    'deepseek-chat',
                    new LLMPricing(0.27, 1.1),
                );

                $deepseek = new LLMEndpoint(
                    'Deepseek',
                    'https://api.deepseek.com/v1',
                    '',
                    'deepseek-chat',
                    new LLMPricing(0.27, 1.1),
                );

                $deepseek_v3 = new LLMEndpoint(
                    'DeepSeek-V3-0324 (by Deepinfra.com)',
                    'https://api.deepinfra.com/v1/openai',
                    '',
                    'deepseek-ai/DeepSeek-V3-0324',
                    new LLMPricing(0.3, 0.88),
                );

                $project->setDefaultLLM($gpt4_1);

                $this->modelManager->commit(new Transaction([$project, $gpt4_1, $deepseek, $deepseek_v3]));

                return $response->withRedirect((new RouteUri($request))("project/{$project->id()}/glossary/primary"));

            } catch (RuntimeException $exception) {
                $error = $exception->getMessage();
            }
        }

        return $this->render($response, 'project/project_create', [
            'request' => $request,
            'form' => $params,
            'error' => $error,
        ]);
    }

}