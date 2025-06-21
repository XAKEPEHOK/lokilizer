<?php

use League\Plates\Template\Template;
use PrinsFrank\Standards\Language\LanguageAlpha2;
use Slim\Http\ServerRequest;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use XAKEPEHOK\Lokilizer\Components\Parsers\FileFormatterInterface;

/** @var Template $this */
/** @var ServerRequest $request */
/** @var RouteUri $route */
/** @var array $form */
/** @var string $error */
/** @var array $languages */
/** @var FileFormatterInterface $formatter */

$this->layout('project_layout', ['request' => $request, 'title' => '📥 Download translation file']) ?>

<form method="post" enctype="multipart/form-data" class="mt-5 row">
    <div class="col mx-auto">

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?= $this->e($error) ?>
            </div>
        <?php endif; ?>

        <div class="mb-3">
            <label for="language" class="form-label">Language</label>
            <select class="form-select" id="language" name="language">
                <?php foreach ($languages as $lang): ?>
                    <option value="<?=$this->e($lang)?>" <?=$form['language'] === $lang ? 'selected' : ''?>>
                        <?=$this->e(LanguageAlpha2::from($lang)->name) ?>
                        (<?=$this->e(strtoupper($lang)) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="withEmpty" value="1" name="withEmpty" <?=$form['withEmpty'] === true ? 'checked' : ''?>>
            <label class="form-check-label" for="withEmpty">With empty data</label>
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="withOutdated" value="1" name="withOutdated" <?=$form['withOutdated'] === true ? 'checked' : ''?>>
            <label class="form-check-label" for="withOutdated">With outdated data</label>
        </div>

        <?php foreach ($formatter::exportOptions() as $name => $options): ?>
            <div class="mb-3">
                <label for="<?=md5($name)?>" class="form-label"><?=$this->e($name)?></label>
                <select class="form-select" id="<?=md5($name)?>" name="option_<?=base64_encode($name)?>">
                    <?php foreach ($options as $value => $label): ?>
                        <option value="<?=$this->e($value)?>" <?=($form['options'][$name] ?? '') === $value ? 'selected' : ''?>>
                            <?=$this->e($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endforeach; ?>

        <button type="submit" class="btn btn-primary mt-3">Download</button>
    </div>
</form>
