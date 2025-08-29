<?php
/** @var Template $this */
/** @var ServerRequest $request */
/** @var GlossaryItem|null $item */
/** @var LanguageAlpha2[] $languages */

$item = $item ?? null;

use League\Plates\Template\Template;
use PrinsFrank\Standards\Language\LanguageAlpha2;
use Slim\Http\ServerRequest;
use XAKEPEHOK\Lokilizer\Models\Glossary\GlossaryItem;

?>

<tr>
    <?php foreach ($languages as $language): ?>
        <?php $value = $item?->getByLanguage($language) ?? ''; ?>
        <td class="pt-3">
            <input
                class="form-control form-control-sm <?=(strlen($value) === 0 && $item) ? 'is-invalid' : ''?>"
                type="text"
                name="<?=$language->value?>[]"
                placeholder="Text in <?=$this->e(str_replace('_', ' ', $language->name))?>"
                value="<?=$value?>"
            >
        </td>
    <?php endforeach; ?>
</tr>
<tr>
    <td class="pt-0 pb-3" colspan="<?= count($languages) ?>">
        <textarea
            placeholder="Description"
            class="form-control form-control-sm textarea-autosize"
            rows="1"
            name="description[]"><?=$this->e($item?->description ?? '')?></textarea>
    </td>
</tr>
