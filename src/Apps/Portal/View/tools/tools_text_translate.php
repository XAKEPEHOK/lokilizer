<?php

use League\Plates\Template\Template;
use PrinsFrank\Standards\Language\LanguageAlpha2;
use Slim\Http\ServerRequest;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Models\Glossary\Glossary;

/** @var Template $this */
/** @var ServerRequest $request */
/** @var RouteUri $route */
/** @var string $error */
/** @var array $form */
/** @var float $cost */
/** @var string $translated */
/** @var LanguageAlpha2 $languages */
/** @var Glossary[] $glossaries */

$subtitle = '💵 $' . round($cost, 4);

$this->layout('project_layout', ['request' => $request, 'title' => '🔠 Text translate', 'subtitle' => $subtitle]) ?>

<form method="post" class="mt-5 row">
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
                    <option value="<?=$this->e($lang->value)?>" <?=$form['language'] === $lang->value ? 'selected' : ''?>>
                        <?=$this->e(str_replace('_', ' ', $lang->name)) ?>
                        (<?=$this->e(strtoupper($lang->value)) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="prompt" class="form-label">Prompt</label>
            <textarea class="form-control" id="prompt" rows="2" name="prompt"><?=$this->e($form['prompt'])?></textarea>
        </div>

        <div class="mb-3">
            <label for="llm" class="form-label">LLM</label>
            <select class="form-select" id="llm" name="llm">
                <?php foreach (Current::getLLMEndpoints() as $llm): ?>
                    <option value="<?=$this->e($llm->id())?>" <?=$form['llm'] === $llm->id()->get() ? 'selected' : ''?>>
                        <?=$this->e($llm->getName()) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="text" class="form-label">Text</label>
            <textarea class="form-control" id="text" rows="10" name="text"><?=$this->e($form['text'])?></textarea>
        </div>

        <button type="submit" class="btn btn-primary mt-3">Translate</button>

        <?php if (!empty($translated)): ?>
            <div class="card my-4">
                <div class="card-header">
                    <nav>
                        <div class="nav nav-pills" id="nav-tab" role="tablist">
                            <button class="nav-link active" id="nav-formatted-tab" data-bs-toggle="tab" data-bs-target="#nav-formatted" type="button" role="tab">
                                Formatted
                            </button>
                            <button class="nav-link" id="nav-raw-tab" data-bs-toggle="tab" data-bs-target="#nav-raw" type="button" role="tab">
                                Raw
                            </button>
                        </div>
                    </nav>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="nav-tabContent">
                        <div class="tab-pane fade show active" id="nav-formatted" role="tabpanel" aria-labelledby="nav-formatted-tab" tabindex="0">
                            <?=nl2br($this->e($translated))?>
                        </div>
                        <pre class="tab-pane fade" id="nav-raw" role="tabpanel" aria-labelledby="nav-raw-tab" tabindex="0"><?=$this->e($translated)?></pre>
                    </div>
                </div>
                <?php if (!empty($glossaries)): ?>
                <div class="card-footer text-body-secondary">
                    <?= $this->insert('widgets/_distilled_glossary', ['glossaries' => $glossaries]) ?>
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</form>
