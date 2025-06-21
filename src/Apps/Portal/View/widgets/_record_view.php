<?php

use League\Plates\Template\Template;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use XAKEPEHOK\Lokilizer\Models\Localization\PluralRecord;
use XAKEPEHOK\Lokilizer\Models\Localization\SimpleRecord;
use XAKEPEHOK\Lokilizer\Models\Localization\Record;

/** @var Template $this */
/** @var RouteUri $route */
/** @var Record $record */
?>

<div class="">
    <?php if ($record instanceof SimpleRecord): ?>
        <div><?= nl2br($this->e($record->getPrimaryValue())) ?></div>
    <?php endif; ?>

    <?php if ($record instanceof PluralRecord): ?>
        <ul>
            <?php foreach ($record->getPrimaryValue()->toArray() as $category => $value): ?>
            <li>
                <span class="badge badge-secondary"><?=$this->e($category)?></span> <?=$this->e($value)?>
            </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>