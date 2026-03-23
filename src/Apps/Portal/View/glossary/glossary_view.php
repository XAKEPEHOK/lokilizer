<?php

use League\Plates\Template\Template;
use PrinsFrank\Standards\Language\LanguageAlpha2;
use Slim\Http\ServerRequest;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use XAKEPEHOK\Lokilizer\Models\Glossary\Glossary;
use XAKEPEHOK\Lokilizer\Models\Glossary\PrimaryGlossary;
use XAKEPEHOK\Lokilizer\Models\Glossary\SpecialGlossary;
use XAKEPEHOK\Lokilizer\Models\Project\Components\Role\Permission;

/** @var Template $this */
/** @var ServerRequest $request */
/** @var RouteUri $route */
/** @var Glossary $glossary */
/** @var LanguageAlpha2[] $languages */

$primary = Current::getProject()->getPrimaryLanguage();

$title = $glossary instanceof PrimaryGlossary ? '📜 Glossary' : '📙 Special glossary';
$subtitle = '💵 $' . round($glossary->LLMCost()->getResult(), 2);

$this->layout('project_layout', ['request' => $request, 'title' => $title, 'subtitle' => $subtitle]) ?>

<div class="container">
    <?php if (Current::can(Permission::MANAGE_LANGUAGES)): ?>
        <div class="my-4">
            <a href="<?= $route("glossary/{$glossary->id()}") ?>" class="btn btn-primary">Edit glossary</a>
        </div>
    <?php endif; ?>

    <?php if ($glossary instanceof SpecialGlossary): ?>
        <p><strong>Key prefix:</strong> <code><?= $this->e($glossary->getKeyPrefix()) ?></code></p>
    <?php endif; ?>

    <?php if (!empty($glossary->getSummary())): ?>
        <p class="text-secondary" style="white-space: pre-wrap;"><?= $this->e($glossary->getSummary()) ?></p>
    <?php endif; ?>

    <?php if (!empty($glossary->getItems())): ?>
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <?php foreach ($languages as $lang): ?>
                <input type="checkbox" class="btn-check lang-toggle" id="lang-<?= $lang->value ?>"
                       value="<?= $lang->value ?>" autocomplete="off" <?= $lang === $primary ? 'checked' : '' ?>
                       data-primary="<?= $lang === $primary ? '1' : '0' ?>">
                <label class="btn btn-outline-primary btn-sm" for="lang-<?= $lang->value ?>">
                    <?= $this->e(str_replace('_', ' ', $lang->name)) ?>
                </label>
            <?php endforeach; ?>
        </div>

        <div class="list-group" id="glossaryItems">
            <?php foreach ($glossary->getItems() as $item): ?>
                <div class="list-group-item">
                    <?php foreach ($languages as $lang): ?>
                        <?php $value = $item->getByLanguage($lang) ?? ''; ?>
                        <div class="glossary-lang d-none" data-lang="<?= $lang->value ?>">
                            <span class="badge text-bg-secondary me-1"><?= strtoupper($lang->value) ?></span>
                            <?php if (strlen($value) > 0): ?>
                                <?= $this->e($value) ?>
                            <?php else: ?>
                                <span class="text-warning">—</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!empty($item->description)): ?>
                        <div class="text-secondary small mt-1"><?= $this->e($item->description) ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="text-secondary mt-3">No glossary items</p>
    <?php endif; ?>
</div>

<script>
    $(document).ready(function () {
        var $items = $('.glossary-lang');
        var $toggles = $('.lang-toggle');
        var $primary = $('.lang-toggle[data-primary="1"]');

        function updateLangs() {
            var selected = $toggles.filter(':checked').map(function () {
                return $(this).val();
            }).get();

            if (selected.length === 0) {
                $primary.prop('checked', true);
                selected = [$primary.val()];
            }

            $items.each(function () {
                var $el = $(this);
                $el.toggleClass('d-none', selected.indexOf($el.data('lang')) === -1);
            });
        }

        $toggles.on('change', updateLangs);
        updateLangs();
    });
</script>
