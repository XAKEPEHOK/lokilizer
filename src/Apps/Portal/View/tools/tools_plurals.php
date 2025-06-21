<?php

use League\Plates\Template\Template;
use PrinsFrank\Standards\Language\LanguageAlpha2;
use Slim\Http\ServerRequest;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use XAKEPEHOK\Lokilizer\Models\Localization\Components\AbstractPluralValue;
use XAKEPEHOK\Lokilizer\Models\Localization\Components\CardinalPluralValue;
use XAKEPEHOK\Lokilizer\Models\Localization\Components\OrdinalPluralValue;

/** @var Template $this */
/** @var ServerRequest $request */
/** @var RouteUri $route */
/** @var array $form */

$this->layout('project_layout', ['request' => $request, 'title' => '🔢 Plurals']) ?>

<script>
    $(document).ready(function(){
        const $form = $('#form-plurals')
        const onChange = () => $form.submit()
        $('#language').on('change', onChange)
        $('#type').on('change', onChange)
    });
</script>

<form method="get" class="mt-5 row" id="form-plurals">
    <div class="col mx-auto">

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?= $this->e($error) ?>
            </div>
        <?php endif; ?>

        <div class="mb-3">
            <label for="language" class="form-label">Language</label>
            <select class="form-select" id="language" name="language">
                <?php foreach (LanguageAlpha2::cases() as $lang): ?>
                    <option value="<?=$this->e($lang->value)?>" <?=$form['language'] === $lang->value ? 'selected' : ''?>>
                        <?=$this->e($lang->name) ?>
                        (<?=$this->e(strtoupper($lang->value)) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="type" class="form-label">Type</label>
            <select class="form-select" id="type" name="type">
                <option value="<?=$this->e(CardinalPluralValue::getType())?>" <?=$form['type'] === CardinalPluralValue::getType() ? 'selected' : ''?>>
                    Cardinal (eg 1, 2, 3, ...)
                </option>
                <option value="<?=$this->e(OrdinalPluralValue::getType())?>" <?=$form['type'] === OrdinalPluralValue::getType() ? 'selected' : ''?>>
                    Ordinal (eg 1st, 2nd, 3rd, ...)
                </option>
            </select>
        </div>

        <ul class="mt-5">
            <?php $language = LanguageAlpha2::from($form['language']); ?>
            <?php foreach (AbstractPluralValue::getCategoriesForLanguage($language, $form['type']) as $category): ?>
                <li>
                    <span class="badge text-bg-primary"><?=$this->e($category)?></span>
                    <?=$this->e(implode(', ', AbstractPluralValue::getCategoryExamples($language, $category, $form['type'], 15)))?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</form>
