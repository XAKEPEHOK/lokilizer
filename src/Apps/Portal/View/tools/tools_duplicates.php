<?php

use League\Plates\Template\Template;
use PrinsFrank\Standards\Language\LanguageAlpha2;
use Slim\Http\ServerRequest;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;

/** @var Template $this */
/** @var ServerRequest $request */
/** @var RouteUri $route */
/** @var array $languages */
/** @var array $form */
/** @var array $duplicates */

$this->layout('project_layout', ['request' => $request, 'title' => '👯 Duplicate analyzer']) ?>


<form method="get" class="row row-cols-lg-auto g-3 align-items-center mt-2">
    <div class="row">
        <div class="col-md-3 col-sm-6 col-xs-12 mb-2">
            <label for="language" class="form-label">Language</label>
            <select class="form-select" id="language" name="language">
                <option value="" <?= empty($form['language']) ? 'selected' : '' ?>>Any language</option>
                <?php foreach ($languages as $lang): ?>
                    <option value="<?= $this->e($lang) ?>" <?= $form['language'] === $lang ? 'selected' : '' ?>>
                        <?= $this->e((LanguageAlpha2::from($lang))->name) ?>
                        (<?= $this->e(strtoupper($lang)) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3 col-sm-6 col-xs-12 mb-2">
            <label for="caseSensitive" class="form-label">Case sensitive</label>
            <select class="form-select" id="caseSensitive" name="caseSensitive">
                <option value="1" <?= $form['caseSensitive'] === true ? 'selected' : '' ?>>Yes</option>
                <option value="0" <?= $form['caseSensitive'] === false ? 'selected' : '' ?>>No</option>
            </select>
        </div>

        <div class="col-md-3 col-sm-6 col-xs-12 mb-2">
            <label for="min" class="form-label">Min count</label>
            <div class="input-group">
                <input type="number" class="form-control" id="min" name="min" value="<?= $this->e($form['min']) ?>" min="2"
                       required>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 col-xs-12 mb-2">
            <label for="min" class="form-label">Max count</label>
            <div class="input-group">
                <input type="number" class="form-control" id="max" name="max" value="<?= $this->e($form['max']) ?>" min="<?=count($languages)?>">
            </div>
        </div>


        <div class="col-md-3 col-sm-6 col-xs-12 mb-2">
            <label for="submit" class="form-label">&nbsp;</label>
            <div class="input-group">
                <button id="submit" type="submit" class="btn btn-primary">Find</button>
            </div>
        </div>
    </div>
</form>

<div class="accordion mt-3" id="duplicates">
    <?php foreach ($duplicates as $duplicate): ?>
        <div class="accordion-item">
            <div class="accordion-header">
                <button class="accordion-button collapsed p-1" type="button" data-bs-toggle="collapse" data-bs-target="#<?= md5($duplicate['_id']) ?>">
                    <span class="hstack gap-2 w-100 px-2">
                        <?php foreach ($duplicate['values'] as $value): ?>
                            <?php
                            $value = $this->e($value);
                            if (empty($value)) {
                                $value = '&nbsp;';
                            }
                            ?>
                            <code class="m-0 py-0 px-2 border border-1 border-secondary"><?= $value ?></code>
                        <?php endforeach; ?>
                        <?php $sameKeys = count($duplicate['keys']) - count(array_unique($duplicate['keys'])); ?>
                        <div class="text-end ms-auto">
                            <?php if ($sameKeys > 0): ?>
                                <span class="p-2 badge rounded-pill text-bg-danger"><?= $this->e($duplicate['count']) ?></span>
                            <?php endif; ?>
                        <span class="p-2 badge rounded-pill text-bg-warning"><?= $this->e($duplicate['count']) ?></span>
                        </div>
                    </span>
                </button>
            </div>
            <div id="<?= md5($duplicate['_id']) ?>" class="accordion-collapse collapse" data-bs-parent="#duplicates">
                <div class="accordion-body">
                    <ul>
                        <?php foreach ($duplicate['keys'] as $key): ?>
                            <li><code><?= $this->e($key) ?></code></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
