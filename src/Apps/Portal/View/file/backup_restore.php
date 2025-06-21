<?php

use League\Plates\Template\Template;
use Slim\Http\ServerRequest;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;

/** @var Template $this */
/** @var ServerRequest $request */
/** @var RouteUri $route */
/** @var array $form */
/** @var string $error */

$this->layout('project_layout', ['request' => $request, 'title' => '♻️ Restore backup']) ?>

<form method="post" enctype="multipart/form-data" class="mt-5 row">
    <div class="col mx-auto">

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?= $this->e($error) ?>
            </div>
        <?php endif; ?>

        <div class="mb-3">
            <label for="file" class="form-label">File</label>
            <input type="file" name="file" id="file" class="form-control" accept=".backup"/>
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="llm" value="1" name="llm" <?=$form['llm'] === true ? 'checked' : ''?>>
            <label class="form-check-label" for="llm">Restore LLM endpoints</label>
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="glossary" value="1" name="glossary" <?=$form['glossary'] === true ? 'checked' : ''?>>
            <label class="form-check-label" for="glossary">Restore glossary</label>
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="records" value="1" name="records" <?=$form['records'] === true ? 'checked' : ''?>>
            <label class="form-check-label" for="records">Restore records</label>
        </div>

        <button type="submit" class="btn btn-primary mt-3">Restore backup</button>
    </div>
</form>
