<?php

use League\Plates\Template\Template;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Models\Localization\Components\AbstractPluralValue;
use XAKEPEHOK\Lokilizer\Models\Localization\Components\AbstractValue;
use XAKEPEHOK\Lokilizer\Models\Localization\PluralRecord;
use XAKEPEHOK\Lokilizer\Models\Localization\SimpleRecord;
use XAKEPEHOK\Lokilizer\Models\Localization\Record;
use XAKEPEHOK\Lokilizer\Models\Project\Components\Role\Permission;

/** @var Template $this */
/** @var RouteUri $route */
/** @var Record $record */
/** @var AbstractValue $value */
/** @var bool $disabled */
/** @var array $group */

$disabled = isset($disabled) && boolval($disabled);
$group = $group ?? [];

if (!$disabled) {
    $disabled = !Current::can(Permission::TRANSLATE, $value->getLanguage());
}

$primary = $record->getPrimaryValue();
$multiline = $primary->countEOL() > 0 || $value->countEOL() > 0;
$invalidClass = $value->getWarnings() > 0 && !$value->verified ? 'text-danger' : '';

$identity = "{$record->id()}-{$value->getLanguage()->value}"
?>

<div>
    <fieldset class="input-multilineset input-group input-group-sm"
              style="min-width: 25vw;" <?= $disabled ? 'disabled' : '' ?>>
            <span
                    title="<?= $this->e($value->getLanguage()->name) ?>"
                    class="input-group-text <?= $invalidClass ?> <?= $record === $primary ? 'fw-bolder' : '' ?>"
                    id="inputGroup-sizing-sm"
            >
                    <?= $this->e(strtoupper($value->getLanguage()->value)) ?>
                </span>

        <div class="form-control p-0 vstack border-0 input-group-sm pair-with-suggest">
            <?php if ($record instanceof SimpleRecord): ?>
                <?= $this->insert('widgets/_record_input', [
                    'multiline' => $multiline,
                    'id' => $identity,
                    'name' => "value[{$value->getLanguage()->value}]",
                    'value' => $value,
                    'class' => 'pair-with-suggest-value'
                ]) ?>

            <div class="input-group input-group-sm collapse collapse-<?=$identity?> <?=(!empty($value->getSuggested()) ? 'show' : '')?>">
                <?= $this->insert('widgets/_record_input', [
                    'multiline' => $multiline,
                    'id' => $identity,
                    'name' => "suggested[{$value->getLanguage()->value}]",
                    'value' => $value->getSuggested(),
                    'class' => "bg-info-subtle text-info-emphasis collapse-{$identity}-input pair-with-suggest-suggest",
                ]) ?>
                <?php if ($value->getSuggested()): ?>
                    <a
                            title="Apply suggestion"
                            class="input-group-text text-decoration-none apply-suggestion"
                            href="#">
                        ⏫
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php if ($record instanceof PluralRecord): ?>
                <?php foreach (AbstractPluralValue::getCategoriesForLanguage($value->getLanguage()) as $category): ?>
                    <div class="input-group input-group-sm">
                            <span
                                    data-bs-toggle="tooltip"
                                    data-bs-title="<?= $this->e(implode(', ', AbstractPluralValue::getCategoryExamples($value->getLanguage(), $category, $record->getType()))) ?>"
                                    class="input-group-text rounded-0"
                                    style="min-width: 4.3em"
                            ><?= $this->e(ucfirst($category)) ?></span>
                        <div class="form-control p-0 vstack border-0 input-group-sm pair-with-suggest">
                            <?= $this->insert('widgets/_record_input', [
                                'multiline' => $multiline,
                                'id' => $category . '-' . $identity,
                                'name' => "value[{$value->getLanguage()->value}][{$this->e($category)}]",
                                'value' => $value->getCategoryValue($category),
                                'class' => 'pair-with-suggest-value'
//                                'required' => true,
                            ]) ?>
                            <div class="input-group input-group-sm collapse collapse-<?=$identity?> <?=(!empty($value->getSuggested()) ? 'show' : '')?>">
                                <?= $this->insert('widgets/_record_input', [
                                    'multiline' => $multiline,
                                    'id' => $category . '-' . $identity,
                                    'name' => "suggested[{$value->getLanguage()->value}][{$this->e($category)}]",
                                    'value' => $value->getSuggested()?->getCategoryValue($category) ?? '',
                                    'class' => "bg-info-subtle text-info-emphasis collapse-{$identity}-input pair-with-suggest-suggest",
                                ]) ?>
                                <?php if ($value->getSuggested()): ?>
                                    <a
                                            title="Apply suggestion"
                                            class="input-group-text text-decoration-none apply-suggestion"
                                            href="#">
                                        ⏫
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="input-group-text dropdown">
            <a class="dropdown-toggle text-decoration-none" href="#" role="button" data-bs-toggle="dropdown">
                🤖
            </a>
            <ul class="dropdown-menu">
                <?php foreach (Current::getLLMEndpoints() as $llm): ?>
                    <li>
                        <a class="<?=$disabled ? '' : 'llm-handle'?> text-decoration-none dropdown-item" title="LLM translate & fix"
                           href="<?= $this->e($route("_llm/{$record->id()}/{$value->getLanguage()->value}/{$llm->id()}")) ?>">
                            <?=$this->e($llm->getName())?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <?php if ($_ENV['APP_ENV'] === 'dev1'): ?>
            <div
                    class="input-group-text"
            >
                <a class="test-handle text-decoration-none" title="Test"
                   href="<?= $this->e($route("_glossary/{$record->id()}")) ?>">
                    🧪
                </a>
            </div>
        <?php endif; ?>

        <div
                class="input-group-text collapse-suggestion"
                <?php if (!$disabled): ?>
                data-bs-toggle="collapse"
                data-bs-target=".collapse-<?= $this->e($identity) ?>"
                <?php endif; ?>
                role="button"
        >
            <a class="text-decoration-none" title="Suggestion">
                💡
            </a>
        </div>

        <label class="input-group-text">
            <input
                    title="Verification"
                    class="form-check-input mt-0"
                    type="checkbox"
                    value="1"
                    name="verification[<?=$value->getLanguage()->value?>]"
                <?= $value->verified ? 'checked' : '' ?>
            >
        </label>

        <?php if ($value->getWarnings() > 0): ?>
            <?php $errors = "<div class='text-start pt-3'><ul class='ps-4'>" . implode('', array_map(fn(string $err) => "<li>{$this->e($err)}</li>", $value->validate($record))) . "</ul></div>"; ?>
            <div class="input-group-text" data-bs-toggle="tooltip" data-bs-html="true"
                 data-bs-title="<?= htmlspecialchars($errors) ?>">
                <div style="<?= $value->verified ? 'filter: grayscale(1); opacity: 0.5;' : '' ?>">
                    ⚠️
                </div>
            </div>
        <?php endif; ?>
    </fieldset>
</div>


