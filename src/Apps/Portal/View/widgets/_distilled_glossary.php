<?php
/**
 * Created for lokilizer
 * Date: 2025-02-28 17:43
 * @author: Timur Kasumov (XAKEPEHOK)
 */
/** @var string $prefix */
/** @var Glossary[] $glossaries */

$prefix = $prefix ?? '';

use XAKEPEHOK\Lokilizer\Models\Glossary\Glossary;
use XAKEPEHOK\Lokilizer\Models\Glossary\PrimaryGlossary;
use XAKEPEHOK\Lokilizer\Models\Glossary\SpecialGlossary;
use XAKEPEHOK\Lokilizer\Services\GlossaryService;

?>

<ul class="nav nav-pills" role="tablist" style="font-size: 0.8em">
    <?php foreach ($glossaries as $glossary): ?>
        <?php foreach ($glossary->getItems() as $item): ?>
            <li class="nav-item" role="presentation">
                <a
                        href="#"
                        title="<?= $glossary instanceof SpecialGlossary ? ('Glossary for ' . $this->e($glossary->getKeyPrefix())) : 'Primary glossary' ?>"
                        class="nav-link px-2 py-1 me-1 border border-1 rounded"
                        data-bs-toggle="pill"
                        data-bs-target="#glossary-items-<?= md5("{$item}{$prefix}") ?>"
                        type="button"
                        role="tab">
                    <?= $glossary instanceof PrimaryGlossary ? '📗' : '📙' ?>
                    <?= $this->e($item->primary->phrase) ?>
                    <?php
                    $opacity = convertRangeToRange(
                        value: $item->similarity,
                        oldStart: GlossaryService::SIMILARITY_THRESHOLD,
                        oldEnd: 1,
                        newStart: 0.3,
                        newEnd: 1
                    );
                    ?>
                    <span class="badge text-bg-info" style="opacity: <?= $opacity ?>">
                        <?= round($item->similarity * 100) ?>%
                    </span>
                </a>
            </li>
        <?php endforeach; ?>
    <?php endforeach; ?>
</ul>
<div class="tab-content">
    <?php foreach ($glossaries as $glossary): ?>
        <?php foreach ($glossary->getItems() as $item): ?>
            <div class="tab-pane fade mt-2" id="glossary-items-<?= md5("{$item}{$prefix}") ?>" role="tabpanel">
                <?php foreach ($item->getTranslations() as $translation): ?>
                    <span class="badge text-bg-secondary"
                          title="<?= $this->e(str_replace('_', ' ', $translation->language->name)) ?>">
                                    <span class="badge text-bg-primary">
                                        <?= $this->e(strtoupper($translation->language->value)) ?>
                                    </span>
                                    <?= $this->e($translation->phrase) ?>
                                </span>
                <?php endforeach; ?>
                <div class="pt-2" style="font-size: 0.8em">
                    <?= nl2br($this->e($item->description)) ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>
</div>
