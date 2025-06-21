<?php
use League\Plates\Template\Template;

/** @var Template $this */
/** @var null|DateTimeInterface $datetime */
?>

<?php if ($datetime): ?>
    <div style="cursor: default" class="d-inline-block" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="<?=$datetime?->format('Y-m-d H:i')?>">
        <time class="timeago cursor-pointer" datetime="<?=$datetime?->format('c')?>"></time>
    </div>
<?php endif ?>
