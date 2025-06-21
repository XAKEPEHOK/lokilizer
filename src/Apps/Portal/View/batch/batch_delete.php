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

$this->layout('project_layout', ['request' => $request, 'title' => '🗑️ Batch delete']) ?>

<form method="post" enctype="multipart/form-data" class="mt-5 row">
    <div class="col mx-auto">

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?= $this->e($error) ?>
            </div>
        <?php endif; ?>


        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="includeActual" value="1" name="includeActual" <?=$form['includeActual'] === true ? 'checked' : ''?>>
            <label class="form-check-label" for="includeActual">Include actual</label>
        </div>


        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="includeOutdated" value="1" name="includeOutdated" <?=$form['includeOutdated'] === true ? 'checked' : ''?>>
            <label class="form-check-label" for="includeOutdated">Include outdated</label>
        </div>

        <button type="submit" class="btn btn-primary mt-3">Run</button>
    </div>
</form>
