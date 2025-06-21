<?php

use League\Plates\Template\Template;
use Slim\Http\ServerRequest;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use XAKEPEHOK\Lokilizer\Components\ColorType;

/** @var Template $this */
/** @var ServerRequest $request */
/** @var RouteUri $route */
/** @var string $error */
/** @var array $form */


$this->layout('project_layout', ['request' => $request, 'title' => '📢 Alert message']) ?>

<form method="post" class="mt-5 row">
    <div class="col mx-auto">

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?= $this->e($error) ?>
            </div>
        <?php endif; ?>

        <div class="mb-3">
            <label for="llm" class="form-label">Type</label>
            <select class="form-select" id="type" name="type">
                <?php foreach (ColorType::cases() as $type): ?>
                    <option value="<?=$this->e($type->value)?>" <?=$form['type'] === $type->value ? 'selected' : ''?>>
                        <?=$this->e($type->name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="text" class="form-label">Alert message text</label>
            <textarea class="form-control" id="text" rows="10" name="text"><?=$this->e($form['text'])?></textarea>
        </div>

        <button type="submit" class="btn btn-primary mt-3">Apply</button>
    </div>
</form>
