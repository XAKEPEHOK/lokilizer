<?php

use League\Plates\Template\Template;
use PrinsFrank\Standards\Language\LanguageAlpha2;
use Slim\Http\ServerRequest;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use XAKEPEHOK\Lokilizer\Components\Current;

/** @var Template $this */
/** @var ServerRequest $request */
/** @var RouteUri $route */
/** @var array $form */
/** @var string $error */
/** @var LanguageAlpha2[] $languages */

$this->layout('project_layout', ['request' => $request, 'title' => '💡 AI Batch suggest']) ?>

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
                    <option value="<?=$this->e($lang->value)?>" <?=$form['language'] === $lang->value ? 'selected' : ''?> <?= Current::can(null, $lang) ? '' : 'disabled'?>>
                        <?=$this->e($lang->name) ?>
                        (<?=$this->e(strtoupper($lang->value)) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <div class="row">
                <div class="col-xs-12 col-md-6">
                    <label class="form-check-label" for="keyContains">Key contains</label>
                    <input type="text" class="form-control" id="keyContains" value="<?=$this->e($form['keyContains'])?>" name="keyContains">
                    <div class="form-text">Optional. Will be applied only for keys, that contain this string</div>

                </div>
                <div class="col-xs-12 col-md-6">
                    <label class="form-check-label" for="valueContains">Value contains</label>
                    <input type="text" class="form-control" id="valueContains" value="<?=$this->e($form['valueContains'])?>" name="valueContains">
                    <div class="form-text">Optional. Will be applied only for values, that contain this string</div>
                </div>
            </div>
        </div>

        <div class="mb-3">
            <div class="row">
                <div class="col-xs-12 col-md-6">
                    <label for="llm" class="form-label">LLM</label>
                    <select class="form-select" id="llm" name="llm">
                        <?php foreach (Current::getLLMEndpoints() as $llm): ?>
                            <option value="<?=$this->e($llm->id())?>" <?=$form['llm'] === $llm->id()->get() ? 'selected' : ''?>>
                                <?=$this->e($llm->getName()) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-xs-12 col-md-6">
                    <label class="form-check-label" for="llmTimeout">LLM Timeout, sec</label>
                    <input type="number" min="30" max="300" class="form-control" id="llmTimeout" value="<?=$this->e($form['llmTimeout'])?>" name="llmTimeout">
                </div>
            </div>
        </div>

        <div class="mb-3">
            <label for="prompt" class="form-label">Prompt</label>
            <textarea class="form-control" id="prompt" rows="10" name="prompt" placeholder="What should AI do?"><?=$this->e($form['prompt'])?></textarea>
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="excludeWithSuggestions" value="1" name="excludeWithSuggestions" <?=$form['excludeWithSuggestions'] === true ? 'checked' : ''?>>
            <label class="form-check-label" for="excludeWithSuggestions">Exclude with suggestions</label>
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="excludeVerified" value="1" name="excludeVerified" <?=$form['excludeVerified'] === true ? 'checked' : ''?>>
            <label class="form-check-label" for="excludeVerified">Exclude verified</label>
        </div>

        <button type="submit" class="btn btn-primary mt-3">Run</button>
    </div>
</form>
