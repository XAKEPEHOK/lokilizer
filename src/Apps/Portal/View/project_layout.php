<?php

use DiBify\DiBify\Model\Reference;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\MenuItem;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use XAKEPEHOK\Lokilizer\Models\Project\Components\Role\Permission;
use XAKEPEHOK\Lokilizer\Models\Project\Project;
use XAKEPEHOK\Lokilizer\Models\User\User;
use Slim\Http\ServerRequest;

/** @var ServerRequest $request */
/** @var string $title */
/** @var ?string $subtitle */

$subtitle = $subtitle ?? null;

$this->layout('_layout', ['request' => $request, 'title' => $title]);
$route = new RouteUri($request);

/** @var User $user */
$user = $request->getAttribute('user');

/** @var Project $project */
$project = $request->getAttribute('project');

$menu = [
    '🔤 Translations' => '',
    '📜 Glossary' => [
        '📗 Common' => new MenuItem('glossary/primary', Permission::MANAGE_GLOSSARY),
        '📙 Special' => new MenuItem('glossary/list', Permission::MANAGE_GLOSSARY),
        '📊 Usage' => new MenuItem('glossary/usage', Permission::MANAGE_GLOSSARY),
    ],
    '🏭 Batch' => [
        '📤 Upload translation' => new MenuItem('upload', Permission::FILE_UPLOADS),
        '📥 Download translation' => new MenuItem('download', Permission::FILE_DOWNLOADS),
        null,
        '🔤 AI Translate' => new MenuItem('batch/translate', Permission::BATCH_AI),
        '💡 AI Suggest' => new MenuItem('batch/suggest', Permission::BATCH_AI),
        null,
        '✂️ Modify' => new MenuItem('batch/modify', Permission::BATCH_MODIFY),
        '🗑️ Delete' => new MenuItem('batch/delete', Permission::MANAGE_PROJECT_SETTINGS),
        null,
        ...(function () use ($project) {
            /** @var Redis $redis */
            $redis = $this->redis();
            $list = $redis->lrange("batch:latest:{$project->id()}", 0, -1);
            if (!is_array($list)) {
                $list = [];
            }
            $items = [];
            foreach ($list as $item) {
                $itemData = json_decode($item, true);
                $items[$itemData['title']] = new MenuItem("progress/{$itemData['uuid']}", Permission::BATCH_HISTORY);
            }
            return $items;
        })()
    ],
    '🛠️ Tools' => [
        '📢 Alert message' => new MenuItem('alert-message', Permission::ALERT_MESSAGE),
        null,
        '🔠 Text translate' => 'text-translate',
        '🔢 Plurals' => 'plurals',
//        '🏷️ Keys analyzer' => 'keys',
        '🏘️ Groups analyzer' => 'groups',
        '👯‍ Duplicates analyzer' => 'duplicates',
        '🕳️ Loosed placeholders analyzer' => 'loosed-placeholders',
    ],
]
?>
<div style="height: 100svh;">
    <nav class="navbar navbar-expand-md bg-body-tertiary mb-3">
        <div class="container">
            <a class="navbar-brand" href="<?= $route('') ?>">
                <img src="/logo_mini.png" alt="<?= $this->e($_ENV['PROJECT_NAME']) ?>" height="35">
                <?= $this->e($project->getName()) ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-layout">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbar-layout">
                <?= $this->insert('_menu', ['menu' => $menu]) ?>
                <?= $this->insert('_menu_account', ['menu' => [
                    '🛟 Help' => 'getting-started',
                    '⚙️ Settings' => [
                        '💾 Make backup' => new MenuItem('backup/make', Permission::BACKUP_MAKE),
                        '♻️ Restore backup' => new MenuItem('backup/restore', Permission::BACKUP_RESTORE),
                        null,
                        '👥 Users' => new MenuItem('users', Permission::MANAGE_USERS),
                        '⚙️ Project settings' => new MenuItem('settings', Permission::MANAGE_PROJECT_SETTINGS),
                        '🧠 LLM endpoints' => new MenuItem('llm', Permission::MANAGE_LLM),
                    ],
                ]]) ?>
            </div>
    </nav>
    <div class="container px-3 pb-5 position-relative overflow-x-auto" style="min-height: 80svh">
        <?php if (!empty($_GET['_alert'])): ?>
            <div class="alert alert-<?= $_GET['_alert_type'] ?? 'info' ?>">
                <?= $this->e($_GET['_alert']) ?>
            </div>
        <?php endif; ?>

        <?php
        /** @var Redis $redis */
        $redis = $this->redis();
        $key = "alert:{$project->id()}";
        $alert = $redis->hGetAll($key);
        if (empty($alert)) {
            $alert = null;
        } else {
            try {
                $reference = Reference::create(User::getModelAlias(), $alert['user']);
                /** @var User $user */
                $user = $reference->getModel();
                $alert['user'] = $user;
            } catch (Error) {
                $alert['user'] = null;
            }
        }
        ?>

        <?php if ($alert): ?>
            <div class="alert alert-<?= $alert['type'] ?>">
                <?php if ($alert['user']): ?>
                    <span class="badge text-bg-primary">
                        <?= $this->e($alert['user']->getName()) ?>
                    </span>
                <?php endif; ?>
                <?= $this->e($alert['text']) ?>
            </div>
        <?php endif; ?>

        <div class="d-flex mb-3">
            <h1 class="<?= is_null($subtitle) ? 'w-100' : 'w-75' ?>"><?= $this->e($title) ?></h1>
            <?php if ($subtitle): ?>
                <div class="w-25 text-secondary text-end"><?= $this->e($subtitle) ?></div>
            <?php endif; ?>
        </div>

        <?= $this->section('content') ?>
    </div>
</div>