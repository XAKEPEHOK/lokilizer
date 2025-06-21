<?php

use League\Plates\Template\Template;
use PrinsFrank\Standards\Language\LanguageAlpha2;
use Slim\Http\ServerRequest;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Components\Parsers\FileFormatter;
use XAKEPEHOK\Lokilizer\Models\Project\Components\EOLFormat;
use XAKEPEHOK\Lokilizer\Models\Project\Components\PlaceholderFormat;

/** @var Template $this */
/** @var ServerRequest $request */
/** @var RouteUri $route */
/** @var array $form */
/** @var string $error */
/** @var string $button */
/** @var bool $update */

$update = isset($update) && boolval($update);
?>

<form method="post" enctype="multipart/form-data" class="mt-5 row">
    <div class="col mx-auto">

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?= $this->e($error) ?>
            </div>
        <?php endif; ?>

        <div class="mb-3">
            <label for="name" class="form-label">Name</label>
            <input type="text" class="form-control" id="name" name="name" value="<?=$this->e($form['name'])?>" required minlength="3">
        </div>

        <?php if ($update === true): ?>
        <div class="mb-3">
            <label for="llm" class="form-label">LLM</label>
            <select class="form-select" id="llm" name="llm">
                <?php foreach (Current::getLLMEndpoints() as $llm): ?>
                    <option value="<?=$this->e($llm->id())?>" <?=$llm->id()->isEqual($form['llm']) ? 'selected' : ''?>>
                        <?=$this->e($llm->getName()) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <div class="row mb-3">
            <div class="col-6">
                <label for="primary" class="form-label">Primary language</label>
                <select class="form-select" id="primary" name="primary" <?=$update ? 'disabled' : ''?>>
                    <option value="" <?=empty($form['primary']) ? 'selected' : ''?>>Select language</option>
                    <?php foreach (LanguageAlpha2::cases() as $lang): ?>
                        <option value="<?=$this->e($lang->value)?>" <?=$form['primary'] === $lang->value ? 'selected' : ''?>>
                            <?=$this->e($lang->name) ?>
                            (<?=$this->e(strtoupper($lang->value)) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6">
                <label for="secondary" class="form-label">Secondary language</label>
                <select class="form-select" id="secondary" name="secondary">
                    <option value="" <?=empty($form['secondary']) ? 'selected' : ''?>>No secondary language</option>
                    <?php foreach (LanguageAlpha2::cases() as $lang): ?>
                        <?php if ($update && $form['primary'] === $lang->value) continue ?>
                        <option value="<?=$this->e($lang->value)?>" <?=$form['secondary'] === $lang->value ? 'selected' : ''?>>
                            <?=$this->e($lang->name) ?>
                            (<?=$this->e(strtoupper($lang->value)) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-6">
                <label for="placeholders" class="form-label">Placeholders format</label>
                <select class="form-select" id="placeholders" name="placeholders">
                    <?php foreach (PlaceholderFormat::cases() as $placeholder): ?>
                        <option value="<?=$this->e($placeholder->value)?>" <?=$form['placeholders'] === $placeholder->value ? 'selected' : ''?>>
                            <?=$this->e(str_replace('_', ' ', ucfirst(strtolower($placeholder->name)))) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6">
                <label for="eol" class="form-label">EOL format</label>
                <select class="form-select" id="eol" name="eol">
                    <?php foreach (EOLFormat::cases() as $eol): ?>
                        <option value="<?=base64_encode($this->e($eol->value))?>" <?=base64_decode($form['eol']) === $eol->value ? 'selected' : ''?>>
                            <?=$this->e(trim(json_encode($eol->value), '"')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="mb-3">
            <label for="placeholders" class="form-label">File formatter</label>
            <select class="form-select" id="fileFormatter" name="fileFormatter">
                <?php foreach (FileFormatter::cases() as $fileFormatter): ?>
                    <option value="<?=$this->e($fileFormatter->value)?>" <?=$form['fileFormatter'] === $fileFormatter->value ? 'selected' : ''?>>
                        <?=$this->e(str_replace('_', ' ', ucfirst(strtolower($fileFormatter->name)))) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-primary mt-3"><?=$this->e($button)?></button>
    </div>
</form>
