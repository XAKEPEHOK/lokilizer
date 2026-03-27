<?php

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Project;

use DiBify\DiBify\Manager\ModelManager;
use DiBify\DiBify\Manager\Transaction;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use XAKEPEHOK\Lokilizer\Models\Project\Components\Role\Permission;

class ProjectDeleteAction
{
    public function __construct(
        private readonly ModelManager $modelManager,
    ) {
    }

    public function __invoke(ServerRequest $request, Response $response): Response
    {
        // Проект уже загружен ProjectMiddleware и доступен как атрибут
        $project = $request->getAttribute('project');

        $user = $request->getAttribute('user');
        if (!$user) {
            return $response->withStatus(403);
        }

        $userRole = $project->getUserRole($user);
        if (!$userRole || !$userRole->can(Permission::MANAGE_PROJECT_SETTINGS)) {
            return $response->withStatus(403);
        }

        // Создаём транзакцию с указанием, что проект нужно УДАЛИТЬ
        $transaction = new Transaction(deleted: [$project]);

        // Коммитим через ModelManager — как в ProjectCreateAction
        $this->modelManager->commit($transaction);

        // Перенаправляем — транзакция закоммитится автоматически middleware
        return $response->withHeader('Location', '/')->withStatus(302);
    }
}