<?php

use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use XAKEPEHOK\Lokilizer\Models\Project\Components\Role\Permission;
use XAKEPEHOK\Lokilizer\Models\Project\Project;
use XAKEPEHOK\Lokilizer\Models\User\User;
use League\Plates\Template\Template;
use Slim\Http\ServerRequest;

/** @var Template $this */
/** @var ServerRequest $request */
/** @var RouteUri $route */
/** @var Project[] $projects */

/** @var User $user */
$user = $request->getAttribute('user');

$this->layout('account_layout', ['request' => $request, 'title' => 'Projects']) ?>

<div class="row">
    <div class="col mx-auto">
        <div class="vstack gap-3 mt-3">
            <?php foreach ($projects as $project): ?>
                <div class="btn-group">
                    <a href="<?=$route("project/{$project->id()}/")?>" class="btn btn-primary btn-lg w-100">
                        📂
                        <?= $this->e($project->getName()) ?>
                    </a>
                    <?php if ($project->getUserRole($user)->can(Permission::MANAGE_PROJECT_SETTINGS)): ?>
                        <a href="<?=$route("project/{$project->id()}/settings")?>" class="btn btn-outline-primary btn-lg">⚙️</a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            <a href="<?=$route("project/create")?>" class="btn btn-success btn-lg w-100">
                ➕ Create project
            </a>
        </div>
    </div>
</div>