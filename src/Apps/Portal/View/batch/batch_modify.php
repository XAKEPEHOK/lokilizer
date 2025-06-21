<?php

use League\Plates\Template\Template;
use PrinsFrank\Standards\Language\LanguageAlpha2;
use Slim\Http\ServerRequest;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Components\Parsers\FileFormatterInterface;

/** @var Template $this */
/** @var ServerRequest $request */
/** @var RouteUri $route */
/** @var array $form */
/** @var string $error */
/** @var array $languages */

$this->layout('project_layout', ['request' => $request, 'title' => '✂️ Batch modify']) ?>

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
                    <option value="<?=$this->e($lang)?>" <?=$form['language'] === $lang ? 'selected' : ''?> <?= Current::can(null, LanguageAlpha2::from($lang)) ? '' : 'disabled'?>>
                        <?=$this->e(LanguageAlpha2::from($lang)->name) ?>
                        (<?=$this->e(strtoupper($lang)) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <div class="row">
                <div class="col-xs-12 col-md-6">
                    <label class="form-check-label" for="keyContains">Key contains</label>
                    <input type="text" class="form-control" id="keyContains" value="<?=$this->e($form['keyContains'])?>" name="keyContains">
                </div>
                <div class="col-xs-12 col-md-6">
                    <label class="form-check-label" for="valueContains">Value contains</label>
                    <input type="text" class="form-control" id="valueContains" value="<?=$this->e($form['valueContains'])?>" name="valueContains">
                </div>
            </div>
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="removeComment" value="1" name="removeComment" <?=$form['removeComment'] === true ? 'checked' : ''?>>
            <label class="form-check-label" for="removeComment">Remove comment</label>
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="includeOutdated" value="1" name="includeOutdated" <?=$form['includeOutdated'] === true ? 'checked' : ''?>>
            <label class="form-check-label" for="includeOutdated">Include outdated</label>
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="trim" value="1" name="trim" <?=$form['trim'] === true ? 'checked' : ''?>>
            <label class="form-check-label" for="trim">Trim whitespaces</label>
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="revalidate" value="1" name="revalidate" <?=$form['revalidate'] === true ? 'checked' : ''?>>
            <label class="form-check-label" for="revalidate">Revalidate</label>
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="removeVerification" value="1" name="removeVerification" <?=$form['removeVerification'] === true ? 'checked' : ''?>>
            <label class="form-check-label" for="removeVerification">Remove verification</label>
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="removeValue" value="1" name="removeValue" <?=$form['removeValue'] === true ? 'checked' : ''?>>
            <label class="form-check-label" for="removeValue">Remove value</label>
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="removeSuggested" value="1" name="removeSuggested" <?=$form['removeSuggested'] === true ? 'checked' : ''?>>
            <label class="form-check-label" for="removeSuggested">Remove suggested</label>
        </div>

        <button type="submit" class="btn btn-primary mt-3">Run</button>
    </div>
</form>
