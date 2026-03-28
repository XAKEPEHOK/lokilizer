<?php

use League\Plates\Template\Template;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;

/** @var Template $this */
/** @var RouteUri $route */
/** @var bool $multiline */      // теперь используется как сигнал: есть ли \n
/** @var string $id */
/** @var string $name */
/** @var string $value */
/** @var string $class */
/** @var bool $required */

$class = $class ?? "";
$required = isset($required) && boolval($required);

// Определяем, многострочный ли текст: содержит \n или очень длинный (>100 символов)
$isMultilineContent = str_contains($value, "\n") || strlen($value) > 100;

if ($isMultilineContent) {
    // Многострочный — фиксированная высота (6 строк)
    ?>
    <textarea
        id="input-<?= $this->e($id) ?>-<?= $this->e($name) ?>"
        data-form="<?= $this->e($id) ?>"
        class="form-control font-monospace submit-ctrl-s record-textarea multiline <?= $this->e($class) ?>"
        name="<?= $this->e($name) ?>"
        rows="6"
        <?= $required ? 'required' : '' ?>
    ><?= $this->e($value) ?></textarea>
    <?php
} else {
    // Однострочный — авто-высота (1 строка, растягивается по содержимому)
    ?>
    <textarea
        id="input-<?= $this->e($id) ?>-<?= $this->e($name) ?>"
        data-form="<?= $this->e($id) ?>"
        class="form-control font-monospace submit-ctrl-s record-textarea autosize <?= $this->e($class) ?>"
        name="<?= $this->e($name) ?>"
        rows="1"
        <?= $required ? 'required' : '' ?>
    ><?= $this->e($value) ?></textarea>
    <?php
}
?>