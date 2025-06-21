<?php

use League\Plates\Template\Template;
use Slim\Http\ServerRequest;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Models\Glossary\Glossary;
use XAKEPEHOK\Lokilizer\Models\Glossary\PrimaryGlossary;
use XAKEPEHOK\Lokilizer\Models\Glossary\SpecialGlossary;
use XAKEPEHOK\Lokilizer\Models\Project\Components\Role\Permission;

/** @var Template $this */
/** @var ServerRequest $request */
/** @var RouteUri $route */
/** @var Glossary[] $glossaries */
/** @var array $usage */

$this->layout('project_layout', ['request' => $request, 'title' => '📊 Glossary usage']);
?>
<?php foreach ($glossaries as $glossary): ?>
    <?php if (empty($glossary->getItems())) continue; ?>
    <div class="card mb-4">
        <div class="card-header">
            <?= $glossary instanceof PrimaryGlossary ? '📗 Primary' : '' ?>
            <?= $glossary instanceof SpecialGlossary ? ('📙 For key  <code>' . $this->e($glossary->getKeyPrefix()) . '</code>') : '' ?>
        </div>
        <div class="card-body">
            <ul>
                <?php foreach ($usage[$glossary->id()->get()] ?? [] as $phrase => $count): ?>
                    <li>
                        <?php
                        $classColor = match ($count) {
                            0 => 'danger',
                            1 => 'warning',
                            2 => 'warning',
                            3 => 'warning',
                            default => 'success',
                        }
                        ?>
                        <span class="badge text-bg-<?= $classColor ?>"><?= $this->e($count) ?></span>
                        <span class="badge text-bg-info">
                        <?= $this->e($phrase) ?>
                    </span>
                        <?= $this->e($glossary->getItemByPrimaryPhrase($phrase)->description ?? '') ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="card-footer">
            <?php if (Current::can(Permission::MANAGE_GLOSSARY)): ?>
                <a href="<?= $route("glossary/{$glossary->id()}") ?>" class="btn btn-primary btn-sm">Edit</a>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>
