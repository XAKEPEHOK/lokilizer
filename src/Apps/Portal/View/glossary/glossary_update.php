<?php

use League\Plates\Template\Template;
use PrinsFrank\Standards\Language\LanguageAlpha2;
use Slim\Http\ServerRequest;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Models\Glossary\Glossary;
use XAKEPEHOK\Lokilizer\Models\Glossary\PrimaryGlossary;
use XAKEPEHOK\Lokilizer\Models\Glossary\SpecialGlossary;
use XAKEPEHOK\Lokilizer\Models\Project\Components\Role\Permission;

/** @var Template $this */
/** @var ServerRequest $request */
/** @var RouteUri $route */
/** @var Glossary $glossary */
/** @var array $form */
/** @var LanguageAlpha2[] $languages */
/** @var string $error */

$otherLanguages = array_filter(
    LanguageAlpha2::cases(),
    fn(LanguageAlpha2 $language) => !in_array($language, $languages)
);

$title = $glossary instanceof PrimaryGlossary ? '📜 Glossary' : '📙 Special glossary';
$subtitle = '💵 $' . round($glossary->LLMCost()->getResult(), 2);

$this->layout('project_layout', ['request' => $request, 'title' => $title, 'subtitle' => $subtitle]) ?>

<script>
    $(document).ready(function () {
        // Если можно, задайте кнопке id="addRow", тогда можно писать:
        // $('#addRow').on('click', function(e) { ... });
        // Если менять разметку нельзя, выбираем по классу (предполагается, что у кнопки Add row класс btn-success уникален):
        $('#addRow').on('click', function (e) {
            e.preventDefault(); // отменяем стандартное поведение (отправку формы)

            // Выбираем две последние строки в теле таблицы (#glossary tbody)
            let $lastTwoRows = $('#glossary tbody tr').slice(-2);

            // Клонируем выбранные строки
            let $clonedRows = $lastTwoRows.clone();

            // Очищаем значения всех input и textarea внутри клонированных строк
            $clonedRows.find('input, textarea').val('');

            // Добавляем клонированные строки в конец tbody таблицы
            $('#glossary tbody').append($clonedRows);
        });
    });
</script>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger mb-3" role="alert">
        <?= $this->e($error) ?>
    </div>
<?php endif; ?>

<div class="container">
    <form id="glossaryForm" method="post">
        <?php if ($glossary instanceof SpecialGlossary): ?>
            <div class="row mb-3">
                <div class="col-12">
                    <label for="keyPrefix" class="form-label">Key prefix</label>
                    <input type="text" name="keyPrefix" value="<?= $this->e($form['keyPrefix']) ?>" class="form-control"
                           id="keyPrefix" minlength="1" required>
                </div>
            </div>
        <?php endif; ?>
        <div class="row">
            <div class="col-12">
                <label for="summary" class="form-label">Summary</label>
                <textarea class="form-control textarea-autosize" id="summary" rows="3"
                          name="summary"><?= $this->e($form['summary']) ?></textarea>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12">
                <table class="table table-borderless" id="glossary">
                    <thead>
                    <tr>
                        <?php foreach ($languages as $language): ?>
                            <th scope="col"><?= $this->e(str_replace('_', ' ', $language->name)) ?></th>
                        <?php endforeach; ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($glossary->getItems() as $item): ?>
                        <?= $this->insert('glossary/_glossary_row', ['item' => $item, 'languages' => $languages]) ?>
                    <?php endforeach; ?>
                    <?= $this->insert('glossary/_glossary_row', ['languages' => $languages]) ?>
                    </tbody>
                </table>
            </div>
        </div>
    </form>
    <?php if (Current::can(Permission::MANAGE_GLOSSARY)): ?>
        <div class="row mt-3">
            <div class="col-6">
                <button form="glossaryForm" class="btn btn-primary" type="submit">Save changes</button>
                <?php if ($glossary instanceof SpecialGlossary && Current::can(Permission::MANAGE_GLOSSARY)): ?>
                    <form method="post" class="d-inline-block submit-confirmation"
                          data-confirmation="Are you sure you want to DELETE this glossary?">
                        <button class="btn btn-danger" name="delete" value="delete" type="submit">Delete glossary
                        </button>
                    </form>
                <?php endif; ?>
            </div>
            <div class="col-6 text-end">
                <?php if (Current::can(Permission::MANAGE_GLOSSARY)): ?>
                    <div class="dropup d-inline-block">
                        <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" title="LLM translate empty values">
                            🔤
                        </button>
                        <ul class="dropdown-menu">
                            <?php foreach (Current::getLLMEndpoints() as $llm): ?>
                                <li>
                                    <a class="text-decoration-none dropdown-item"
                                       href="<?= $route("glossary/{$glossary->id()}") ?>?translate=<?=$this->e($llm->id())?>">
                                        <?=$this->e($llm->getName())?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <?php if ($glossary instanceof SpecialGlossary && $glossary->id()->isAssigned()): ?>
                    <?= $this->insert('widgets/_glossary_build_button', ['key' => $glossary->getKeyPrefix(), 'class' => 'btn-outline-primary']) ?>
                <?php endif; ?>
                <button id="addRow" class="btn btn-success" type="submit">Add row</button>
                <?php if (Current::can(Permission::MANAGE_LANGUAGES) && $glossary instanceof PrimaryGlossary): ?>
                    <div class="dropup d-inline-block">
                        <button class="btn btn-outline-success dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            Add language
                        </button>
                        <ul class="dropdown-menu my-3"
                            style="max-width: 250px; height: 250px; overflow-y: scroll; overflow-x: hidden;">
                            <?php foreach ($otherLanguages as $language): ?>
                                <li><a
                                            class="dropdown-item"
                                            href="<?= $route("glossary/{$glossary->id()}") ?>?addLang=<?= $language->value ?>">
                                        <?= $this->e(str_replace('_', ' ', $language->name)) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>