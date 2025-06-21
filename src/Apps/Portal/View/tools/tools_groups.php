<?php

use League\Plates\Template\Template;
use PrinsFrank\Standards\Language\LanguageAlpha2;
use Slim\Http\ServerRequest;
use XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Tools\GroupsAction;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use XAKEPEHOK\Lokilizer\Models\Localization\Record;

/** @var Template $this */
/** @var ServerRequest $request */
/** @var RouteUri $route */
/** @var array $form */
/** @var Record[] $dangling */
/** @var Record[][] $groups */
/** @var int $keys */
/** @var int $parents */

$this->layout('project_layout', ['request' => $request, 'title' => '🏘️ Groups analyzer']) ?>

<form method="get" class="row row-cols-lg-auto g-3 align-items-center mt-2">
    <div class="col-12">
        <label for="sortBy" class="form-label">Sort by</label>
        <select class="form-select" id="sortBy" name="sortBy">
            <?php foreach ([GroupsAction::SORT_BY_COUNT, GroupsAction::SORT_BY_KEY_NAME] as $sortBy): ?>
                <option value="<?= $this->e($sortBy) ?>" <?= $form['sortBy'] === $sortBy ? 'selected' : '' ?>>
                    <?= $this->e(ucfirst($sortBy)) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-12">
        <label for="importantContext" class="form-label">Important context</label>
        <select class="form-select" id="importantContext" name="importantContext">
            <option value="1" <?= $form['importantContext'] === true ? 'selected' : '' ?>>Yes</option>
            <option value="0" <?= $form['importantContext'] === false ? 'selected' : '' ?>>No</option>
        </select>
    </div>

    <div class="col-12">
        <label for="withOutdated" class="form-label">With outdated</label>
        <select class="form-select" id="withOutdated" name="withOutdated">
            <option value="1" <?= $form['withOutdated'] === true ? 'selected' : '' ?>>Yes</option>
            <option value="0" <?= $form['withOutdated'] === false ? 'selected' : '' ?>>No</option>
        </select>
    </div>


    <div class="col-12">
        <label for="submit" class="form-label">&nbsp;</label>
        <div class="input-group">
            <button id="submit" type="submit" class="btn btn-primary">Find</button>
        </div>
    </div>
</form>

<div class="mt-2">
    <span class="badge text-bg-primary">Parents: <?=count($groups)?></span>
    <?php
    $count = 0;
    foreach ($groups as $group) {
        $count+= count($group);
    }
    ?>
    <span class="badge text-bg-info">Keys: <?=$count?></span>
</div>

<div class="accordion mt-3" id="groups">
    <?php if (count($dangling) > 0): ?>
        <div class="accordion-item">
            <div class="accordion-header">
                <button class="accordion-button collapsed p-1" type="button" data-bs-toggle="collapse"
                        data-bs-target="#dangling">
                    <span class="hstack gap-2 w-100 px-2">
                        <span class="badge text-bg-warning">Dangling</span>
                        <span class="p-2 ms-auto badge rounded-pill text-bg-warning"><?= count($dangling) ?></span>
                    </span>
                </button>
            </div>
            <div id="dangling" class="accordion-collapse collapse" data-bs-parent="#groups">
                <div class="accordion-body pt-0">
                    <?php foreach ($dangling as $record): ?>
                        <div class="row border-bottom pt-3">
                            <div class="col-6 text-break text-wrap">
                                <code class="text-wrap text-break"><?= $this->e($record->getParent()) ?></code>
                            </div>
                            <div class="col-6 text-break text-wrap">
                                <code class="text-wrap text-break"><?= $this->e($record->getKey()) ?></code>
                                <?= $this->insert('widgets/_record_view', ['record' => $record]) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <?php foreach ($groups as $path => $group): ?>
        <div class="accordion-item">
            <div class="accordion-header">
                <div class="accordion-button collapsed p-1" data-bs-toggle="collapse"
                        data-bs-target="#<?= md5($path) ?>">
                    <div class="hstack gap-2 w-100 px-2">
                        <div>
                            <?=$this->insert('widgets/_glossary_build_button', ['key' => $path, 'class' => 'btn-sm'])?>
                            <code class="ps-2"><?= $this->e($path) ?></code>
                        </div>
                        <div class="ms-auto">
                            <span class="p-2 badge rounded-pill text-bg-warning"><?= count($group) ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <div id="<?= md5($path) ?>" class="accordion-collapse collapse" data-bs-parent="#groups">
                <div class="accordion-body pt-0">
                    <?php foreach ($group as $record): ?>
                        <div class="row border-bottom pt-3">
                            <div class="col-6 text-break text-wrap">
                                <code class="text-wrap text-break"><?= $this->e($record->getParent()) ?></code>
                            </div>
                            <div class="col-6 text-break text-wrap">
                                <code class="text-wrap text-break"><?= $this->e($record->getKey()) ?></code>
                                <?= $this->insert('widgets/_record_view', ['record' => $record]) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
