<?php

use League\Plates\Template\Template;
use PrinsFrank\Standards\Language\LanguageAlpha2;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use XAKEPEHOK\Lokilizer\Models\Glossary\Glossary;
use XAKEPEHOK\Lokilizer\Models\Localization\Record;

/** @var Template $this */
/** @var RouteUri $route */
/** @var Record $record */
/** @var LanguageAlpha2[] $languages */
/** @var callable $distill */

/** @var Glossary[] $glossaries */
$glossaries = $distill($record);
$cost = 0;
?>
<form
        class="record-input-group card my-4 record-input-form"
        method="post"
        action="<?= $route("_save/{$record->id()}") ?>"
        id="form-<?= $this->e($record->id()) ?>"
>
    <div class="card-header">
        <div class="d-flex">
            <div class="w-75 pe-4">
                <code>
                    <?= $this->e($record->getKey()) ?>
                </code>
                <div class="text-secondary" style="font-size: 0.8em">
                    Touched: <strong><?= $this->insert('widgets/_timeago', ['datetime' => $record->getTouchedAt()]) ?></strong>
                    <span class="text-secondary">
                        | Changed: <strong><?= $this->insert('widgets/_timeago', ['datetime' => $record->getUpdatedAt()]) ?></strong>
                    </span>
                    <span class="text-secondary">
                        | Cost: <strong>$<?= round($record->LLMCost()->getResult(), 4) ?></strong>
                    </span>
                </div>
            </div>
            <div class="w-25 hstack gap-2 justify-content-end">
                <a
                        class="btn btn-outline-secondary <?= empty($record->getComment()) ? 'collapsed' : '' ?>"
                        data-bs-toggle="collapse"
                        title="Comment"
                        href="#comment-<?= $record->id() ?>"
                >
                    🗨️
                </a>
                <button
                        title="Save"
                        class="btn btn-outline-secondary"
                        type="submit">
                    💾
                </button>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="vstack gap-3">
            <textarea
                    id="comment-<?= $record->id() ?>"
                    rows="1"
                    name="comment"
                    class="textarea-autosize submit-ctrl-s w-100 p-3 border border-1 rounded-2 border-info-subtle bg-info-subtle text-info-emphasis mb-2 collapse <?= empty($record->getComment()) ? '' : 'show' ?>"
            ><?= $this->e($record->getComment()) ?></textarea>
            <?php foreach ($languages as $index => $lang): ?>
                <?= $this->insert('widgets/_record_value', [
                    'record' => $record,
                    'value' => $record->getValue($lang) ?? $record->getPrimaryValue()::getEmpty($lang),
                    'group' => $languages,
                    'withComment' => $index === 0,
                ]) ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php if (!empty($glossaries)): ?>
        <div class="card-footer text-body-secondary">
            <?= $this->insert('widgets/_distilled_glossary', [
                'glossaries' => $glossaries,
                'prefix' => $record->id()->get(),
            ]) ?>
        </div>
    <?php endif; ?>
</form>