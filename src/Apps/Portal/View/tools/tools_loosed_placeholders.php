<?php

use League\Plates\Template\Template;
use PrinsFrank\Standards\Language\LanguageAlpha2;
use Slim\Http\ServerRequest;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use XAKEPEHOK\Lokilizer\Models\Localization\Record;

/** @var Template $this */
/** @var ServerRequest $request */
/** @var RouteUri $route */
/** @var LanguageAlpha2[] $languages */
/** @var array $form */
/** @var Record[][] $found */
/** @var callable $distill */

$this->layout('project_layout', ['request' => $request, 'title' => '🕳️ Loosed placeholders analyzer']) ?>


<form method="get" class="row row-cols-lg-auto g-3 align-items-center mt-2">

    <div class="row pt-4">
        <div class="col-md-3 col-sm-6 col-xs-12 mb-2">
            <label for="language" class="form-label">Language</label>
            <select class="form-select" id="language" name="language">
                <?php foreach ($languages as $lang): ?>
                    <option value="<?= $this->e($lang->value) ?>" <?= $form['language'] === $lang->value ? 'selected' : '' ?>>
                        <?= $this->e($lang->name) ?>
                        (<?= $this->e(strtoupper($lang->value)) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3 col-sm-6 col-xs-12 mb-2">
            <label for="verified" class="form-label">Verified</label>
            <select class="form-select" id="verified" name="verified">
                <option value="" <?= $form['verified'] === null ? 'selected' : '' ?>>Any</option>
                <option value="1" <?= $form['verified'] === true ? 'selected' : '' ?>>Yes</option>
                <option value="0" <?= $form['verified'] === false ? 'selected' : '' ?>>No</option>
            </select>
        </div>

        <div class="col-md-3 col-sm-6 col-xs-12 mb-2">
            <label for="startPunctuation" class="form-label">Start punctuation</label>
            <input type="text" class="form-control" id="startPunctuation"
                   value="<?= $this->e($form['startPunctuation']) ?>">
        </div>

        <div class="col-md-3 col-sm-6 col-xs-12 mb-2">
            <label for="endPunctuation" class="form-label">End punctuation</label>
            <input type="text" class="form-control" id="endPunctuation"
                   value="<?= $this->e($form['endPunctuation']) ?>">
        </div>

        <div class="col-md-3 col-sm-6 col-xs-12 mb-2">
            <label for="submit" class="form-label">&nbsp;</label>
            <div class="input-group">
                <button id="submit" type="submit" class="btn btn-primary">Find</button>
            </div>
        </div>
    </div>

</form>

<ul class="nav nav-tabs mt-4" id="myTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="start-lowercase-tab" data-bs-toggle="tab"
                data-bs-target="#start-lowercase-pane" type="button" role="tab">
            Start lowercase
            <?php if (count($found['startLowercase'])): ?>
                <span class="badge text-bg-danger"><?= count($found['startLowercase']) ?></span>
            <?php endif; ?>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="start-punctuation-tab" data-bs-toggle="tab"
                data-bs-target="#start-punctuation-pane"
                type="button" role="tab">
            Start punctuation
            <?php if (count($found['startPunctuation'])): ?>
                <span class="badge text-bg-danger"><?= count($found['startPunctuation']) ?></span>
            <?php endif; ?>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="end-punctuation-tab" data-bs-toggle="tab" data-bs-target="#end-punctuation-pane"
                type="button" role="tab">
            End punctuation
            <?php if (count($found['endPunctuation'])): ?>
                <span class="badge text-bg-danger"><?= count($found['endPunctuation']) ?></span>
            <?php endif; ?>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="start-whitespace-tab" data-bs-toggle="tab" data-bs-target="#start-whitespace-pane"
                type="button" role="tab">
            Start whitespace
            <?php if (count($found['startWhitespace'])): ?>
                <span class="badge text-bg-danger"><?= count($found['startWhitespace']) ?></span>
            <?php endif; ?>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="end-whitespace-tab" data-bs-toggle="tab" data-bs-target="#end-whitespace-pane"
                type="button" role="tab">
            End whitespace
            <?php if (count($found['endWhitespace'])): ?>
                <span class="badge text-bg-danger"><?= count($found['endWhitespace']) ?></span>
            <?php endif; ?>
        </button>
    </li>
</ul>


<div class="tab-content" id="myTabContent">
    <div class="tab-pane fade show active" id="start-lowercase-pane" role="tabpanel" tabindex="0">
        <?php foreach ($found['startLowercase'] as $record): ?>
            <?= $this->insert('widgets/_record_group', [
                'record' => $record,
                'languages' => $languages,
                'distill' => $distill,
            ]) ?>
        <?php endforeach; ?>
    </div>
    <div class="tab-pane fade" id="start-punctuation-pane" role="tabpanel" tabindex="0">
        <?php foreach ($found['startPunctuation'] as $record): ?>
            <?= $this->insert('widgets/_record_group', [
                'record' => $record,
                'languages' => $languages,
                'distill' => $distill,
            ]) ?>
        <?php endforeach; ?>
    </div>
    <div class="tab-pane fade" id="end-punctuation-pane" role="tabpanel" tabindex="0">
        <?php foreach ($found['endPunctuation'] as $record): ?>
            <?= $this->insert('widgets/_record_group', [
                'record' => $record,
                'languages' => $languages,
                'distill' => $distill,
            ]) ?>
        <?php endforeach; ?>
    </div>
    <div class="tab-pane fade" id="start-whitespace-pane" role="tabpanel" tabindex="0">
        <?php foreach ($found['startWhitespace'] as $record): ?>
            <?= $this->insert('widgets/_record_group', [
                'record' => $record,
                'languages' => $languages,
                'distill' => $distill,
            ]) ?>
        <?php endforeach; ?>
    </div>
    <div class="tab-pane fade" id="end-whitespace-pane" role="tabpanel" tabindex="0">
        <?php foreach ($found['endWhitespace'] as $record): ?>
            <?= $this->insert('widgets/_record_group', [
                'record' => $record,
                'languages' => $languages,
                'distill' => $distill,
            ]) ?>
        <?php endforeach; ?>
    </div>
</div>