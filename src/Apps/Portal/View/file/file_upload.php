<?php

use League\Plates\Template\Template;
use PrinsFrank\Standards\Language\LanguageAlpha2;
use Slim\Http\ServerRequest;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;

/** @var Template $this */
/** @var ServerRequest $request */
/** @var RouteUri $route */
/** @var array $form */
/** @var string $error */

$this->layout('project_layout', ['request' => $request, 'title' => '📤 Upload translation file']) ?>

<script>
    $(function() {
        $('#file').on('change', function() {
            const file = this.files[0];
            if (!file) return;

            // Получаем имя файла (без пути)
            const fileName = file.name;
            // Удаляем ".json"
            const fileNameNoExt = fileName.replace(/\.json$/i, '');
            // Берём часть имени до первого подчёркивания (если есть)
            const langCandidate = fileNameNoExt.split('_')[0];

            // Проверяем, есть ли такой вариант в select
            if ($('#language option[value="' + langCandidate + '"]').length) {
                $('#language').val(langCandidate);
            }
        });
    });
</script>

<form method="post" enctype="multipart/form-data" class="mt-5 row">
    <div class="col mx-auto">

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?= $this->e($error) ?>
            </div>
        <?php endif; ?>

        <div class="mb-3">
            <label for="file" class="form-label">File</label>
            <input type="file" name="file" id="file" class="form-control" accept=".json"/>
        </div>

        <div class="mb-3">
            <label for="language" class="form-label">Language</label>
            <select class="form-select" id="language" name="language">
                <option value="" <?=empty($form['language']) ? 'selected' : ''?>>Select language</option>
                <?php foreach (LanguageAlpha2::cases() as $lang): ?>
                    <option value="<?=$this->e($lang->value)?>" <?=$form['language'] === $lang->value ? 'selected' : ''?>>
                        <?=$this->e($lang->name) ?>
                        (<?=$this->e(strtoupper($lang->value)) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-primary mt-3">Upload file</button>
    </div>
</form>
