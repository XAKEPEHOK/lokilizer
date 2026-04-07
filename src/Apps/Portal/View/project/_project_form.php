<?php

use League\Plates\Template\Template;
use PrinsFrank\Standards\Language\LanguageAlpha2;
use Slim\Http\ServerRequest;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Components\Parsers\FileFormatter;
use XAKEPEHOK\Lokilizer\Models\Project\Components\EOLFormat;
use XAKEPEHOK\Lokilizer\Models\Project\Components\PlaceholderFormat;

/** @var Template $this */
/** @var ServerRequest $request */
/** @var RouteUri $route */
/** @var array $form */
/** @var string $error */
/** @var string $button */
/** @var bool $update */

$update = isset($update) && boolval($update);
?>

<form method="post" enctype="multipart/form-data" class="mt-5 row">
    <div class="col mx-auto">

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?= $this->e($error) ?>
            </div>
        <?php endif; ?>

        <div class="mb-3">
            <label for="name" class="form-label">Name</label>
            <input type="text" class="form-control" id="name" name="name" value="<?=$this->e($form['name'])?>" required minlength="3">
        </div>

        <?php if ($update === true): ?>
        <div class="mb-3">
            <label for="llm" class="form-label">LLM</label>
            <select class="form-select" id="llm" name="llm">
                <?php foreach (Current::getLLMEndpoints() as $llm): ?>
                    <option value="<?=$this->e($llm->id())?>" <?=$llm->id()->isEqual($form['llm']) ? 'selected' : ''?>>
                        <?=$this->e($llm->getName()) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <div class="row mb-3">
            <div class="col-6">
                <label for="primary" class="form-label">Primary language</label>
                <select class="form-select" id="primary" name="primary" <?=$update ? 'disabled' : ''?>>
                    <option value="" <?=empty($form['primary']) ? 'selected' : ''?>>Select language</option>
                    <?php foreach (LanguageAlpha2::cases() as $lang): ?>
                        <option value="<?=$this->e($lang->value)?>" <?=$form['primary'] === $lang->value ? 'selected' : ''?>>
                            <?=$this->e($lang->name) ?>
                            (<?=$this->e(strtoupper($lang->value)) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6">
                <label for="secondary-search" class="form-label">Secondary language</label>
                <input type="text" id="secondary-search" class="form-control" placeholder="Start typing to filter languages..." autocomplete="off" value="<?php
                    // Если в $form['secondary'] есть значение, находим соответствующий текст для отображения
                    if (!empty($form['secondary'])) {
                        $selectedLang = LanguageAlpha2::tryFrom($form['secondary']);
                        if ($selectedLang) {
                            echo $this->e($selectedLang->name . ' (' . strtoupper($selectedLang->value) . ')');
                        }
                    }
                ?>" />
                <div id="secondary-language-dropdown" class="list-group mt-1" style="max-height: 350px; overflow-y: auto; display: none; position: absolute; z-index: 1000; background: white; border: 1px solid #dee2e6; border-top: none;"></div>
                <!-- Скрытый select для отправки формы -->
                <select class="form-select" id="secondary-hidden-select" name="secondary" style="display:none;">
                    <option value="" <?=empty($form['secondary']) ? 'selected' : ''?>>No secondary language</option>
                    <?php foreach (LanguageAlpha2::cases() as $lang): ?>
                        <?php if ($update && $form['primary'] === $lang->value) continue ?>
                        <option value="<?=$this->e($lang->value)?>" <?=$form['secondary'] === $lang->value ? 'selected' : ''?>><?=$this->e($lang->name) ?> (<?=$this->e(strtoupper($lang->value)) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-6">
                <label for="placeholders" class="form-label">Placeholders format</label>
                <select class="form-select" id="placeholders" name="placeholders">
                    <?php foreach (PlaceholderFormat::cases() as $placeholder): ?>
                        <option value="<?=$this->e($placeholder->value)?>" <?=$form['placeholders'] === $placeholder->value ? 'selected' : ''?>>
                            <?=$this->e(str_replace('_', ' ', ucfirst(strtolower($placeholder->name)))) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6">
                <label for="eol" class="form-label">EOL format</label>
                <select class="form-select" id="eol" name="eol">
                    <?php foreach (EOLFormat::cases() as $eol): ?>
                        <option value="<?=base64_encode($this->e($eol->value))?>" <?=base64_decode($form['eol']) === $eol->value ? 'selected' : ''?>>
                            <?=$this->e(trim(json_encode($eol->value), '"')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="mb-3">
            <label for="placeholders" class="form-label">File formatter</label>
            <select class="form-select" id="fileFormatter" name="fileFormatter">
                <?php foreach (FileFormatter::cases() as $fileFormatter): ?>
                    <option value="<?=$this->e($fileFormatter->value)?>" <?=$form['fileFormatter'] === $fileFormatter->value ? 'selected' : ''?>>
                        <?=$this->e(str_replace('_', ' ', ucfirst(strtolower($fileFormatter->name)))) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-primary mt-3"><?=$this->e($button)?></button>
    </div>
</form>

<script>
$(function () {
    // Подготавливаем данные о языках один раз
    const languageOptions = [];
    $('#secondary-hidden-select option').each(function () {
        const $opt = $(this);
        const value = $opt.val();
        const text = $opt.text().trim(); // Убираем лишние пробелы
        if (value) { // Пропускаем опцию "No secondary language"
            languageOptions.push({ value, text });
        }
    });

    const $search = $('#secondary-search');
    const $dropdown = $('#secondary-language-dropdown');
    const $select = $('#secondary-hidden-select');

    // Если уже выбран язык (например, после ошибки формы), заполним поле
    const selectedOption = $select.find('option:selected');
    if (selectedOption.val()) {
        $search.val(selectedOption.text());
    }

    // Вспомогательная функция для подсветки
    function highlightMatch(text, query) {
        if (!query.trim()) return text;
        const regex = new RegExp('(' + query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
        return text.replace(regex, '<mark>$1</mark>');
    }

    // Показать выпадающий список
    function showDropdown(items, query) {
        // Устанавливаем ширину списка равной ширине поля ввода
        $dropdown.css('width', $search.outerWidth() + 'px');

        if (items.length === 0) {
            $dropdown.html('<div class="list-group-item text-muted">No matches found</div>');
        } else {
            const html = items.map(opt => {
                const highlighted = highlightMatch(opt.text, query);
                return `<button type="button" class="list-group-item list-group-item-action" data-value="${opt.value}">${highlighted}</button>`;
            }).join('');

            $dropdown.html(html).find('button').on('click', function () {
                const value = $(this).data('value');
                const text = $(this).text(); // .text() уберёт <mark>
                $search.val(text);
                $select.val(value);
                $dropdown.hide();
                $(document).off('click.secondaryLanguageDropdown');
            });
        }

        $dropdown.show();

        // Закрытие при клике вне
        $(document).off('click.secondaryLanguageDropdown').on('click.secondaryLanguageDropdown', function (e) {
            if (!$(e.target).closest('#secondary-search, #secondary-language-dropdown').length) {
                $dropdown.hide();
                $(document).off('click.secondaryLanguageDropdown');
            }
        });
    }

    // Обновление списка
    function updateDropdown(query) {
        let itemsToShow;
        if (!query.trim()) {
            itemsToShow = languageOptions;
        } else {
            itemsToShow = languageOptions.filter(opt =>
                opt.text.toLowerCase().includes(query.toLowerCase())
            );
        }
        showDropdown(itemsToShow, query);
    }

    // События
    $search.on('input', function () {
        updateDropdown($(this).val());
    });

    // 🔧 Изменяем поведение при фокусе
    $search.on('focus', function () {
        // Ставим таймер, чтобы выделение сработало после того, как браузер установит фокус
        const $this = $(this);
        setTimeout(function() {
            // 🔥 Выделяем весь текст (без пробелов по краям, если они были)
            $this.select();
            // Показываем полный список при фокусе
            updateDropdown('');
        }, 0);
    });
});
</script>