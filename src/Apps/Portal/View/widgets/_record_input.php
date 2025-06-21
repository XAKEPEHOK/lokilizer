<?php
/** @var Template $this */
/** @var RouteUri $route */
/** @var bool $multiline */
/** @var string $id */
/** @var string $name */
/** @var string $value */
/** @var string $class */
/** @var string $class */
/** @var string $required */

$class = $class ?? "";
$required = isset($required) && boolval($required);

use League\Plates\Template\Template;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;

?>

<?php if ($multiline): ?>
    <textarea
        id="input-<?= $this->e($id) ?>-<?=$this->e($name)?>"
        data-form="<?=$this->e($id)?>"
        rows="3"
        class="form-control font-monospace submit-ctrl-s overflow-x-scroll overflow-y-auto text-nowrap <?=$this->e($class)?>"
        name="<?=$this->e($name)?>"
        <?=$required ? 'required' : ''?>
    ><?= $this->e($value) ?></textarea>
<?php endif; ?>

<?php if (!$multiline): ?>
    <input
        id="input-<?= $this->e($id) ?>-<?=$this->e($name)?>"
        data-form="<?=$this->e($id)?>"
        class="form-control font-monospace submit-ctrl-s input-multiline rounded-0 <?=$this->e($class)?>"
        type="text"
        name="<?=$this->e($name)?>"
        value="<?= $this->e($value) ?>"
        <?=$required ? 'required' : ''?>
    >
<?php endif; ?>