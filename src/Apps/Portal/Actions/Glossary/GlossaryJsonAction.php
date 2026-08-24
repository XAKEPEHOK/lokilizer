<?php
/**
 * Created for lokilizer
 * Date: 2026-08-24
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Glossary;

use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpException;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Models\Glossary\Db\Storage\GlossaryRepo;
use XAKEPEHOK\Lokilizer\Models\Glossary\GlossaryItem;
use XAKEPEHOK\Lokilizer\Models\Glossary\SpecialGlossary;
use XAKEPEHOK\Lokilizer\Models\Project\Db\ProjectRepo;

class GlossaryJsonAction
{

    public function __construct(
        private ProjectRepo  $projectRepo,
        private GlossaryRepo $glossaryRepo,
    )
    {
    }

    public function __invoke(Request $request, Response $response): Response|ResponseInterface
    {
        $exception404 = new HttpException($request, 'Project not found', 404);

        $project = $this->projectRepo->findById($request->getAttribute('projectId'), $exception404);
        Current::setProject($project);

        $glossaries = [];
        foreach ($this->glossaryRepo->findAll() as $glossary) {
            $data = [
                'id' => (string) $glossary->id(),
                'summary' => $glossary->getSummary(),
                'glossary' => array_values(array_filter(array_map(
                    fn(GlossaryItem $item) => $item->jsonSerialize(),
                    $glossary->getItems()
                ))),
            ];

            if ($glossary instanceof SpecialGlossary) {
                $data['keyPrefix'] = $glossary->getKeyPrefix();
            }

            $glossaries[] = array_filter($data, fn($value) => $value !== null && $value !== []);
        }

        return $response->withJson($glossaries, null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
