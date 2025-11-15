<?php

use League\Plates\Template\Template;
use PrinsFrank\Standards\Language\LanguageAlpha2;
use Slim\Http\ServerRequest;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use XAKEPEHOK\Lokilizer\Models\Localization\Components\AbstractPluralValue;
use XAKEPEHOK\Lokilizer\Models\Localization\Components\CardinalPluralValue;
use XAKEPEHOK\Lokilizer\Models\Localization\Components\OrdinalPluralValue;

/** @var Template $this */
/** @var ServerRequest $request */
/** @var RouteUri $route */
/** @var array $form */

$this->layout('project_layout', ['request' => $request, 'title' => '🔢 Plurals']) ?>

<script>
$(document).ready(function(){
    // Подготавливаем данные о языках один раз
    const languageOptions = [];
    $('#language-hidden-select option').each(function () {
        const $opt = $(this);
        const value = $opt.val();
        // 🔧 Используем trim() для text, чтобы убрать пробелы
        const text = $opt.text().trim();
        if (value) {
            languageOptions.push({ value, text });
        }
    });

    const $search = $('#language-search');
    const $dropdown = $('#language-dropdown-plurals');
    const $select = $('#language-hidden-select'); // Скрытый select для отправки формы
    const $form = $('#form-plurals');

    // 🔧 Если уже выбран язык (например, после ошибки формы), заполним поле (без лишних пробелов)
    const selectedOption = $select.find('option:selected');
    if (selectedOption.val()) {
        $search.val(selectedOption.text().trim()); // 🔧 .trim() здесь тоже
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
                $(document).off('click.languageDropdownPlurals');
                // Сразу отправляем форму, как и раньше
                $form.submit();
            });
        }

        $dropdown.show();

        // Закрытие при клике вне
        $(document).off('click.languageDropdownPlurals').on('click.languageDropdownPlurals', function (e) {
            if (!$(e.target).closest('#language-search, #language-dropdown-plurals').length) {
                $dropdown.hide();
                $(document).off('click.languageDropdownPlurals');
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

    // Обработчик изменения типа (остаётся как есть)
    const onChange = () => $form.submit()
    $('#type').on('change', onChange)

    // Убираем старый обработчик для select
    // $('#language').on('change', onChange) — убран
});
</script>

<form method="get" class="mt-5 row" id="form-plurals">
    <div class="col mx-auto">

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?= $this->e($error) ?>
            </div>
        <?php endif; ?>

        <div class="mb-3">
            <label for="language-search" class="form-label">Language</label>
            <input type="text" id="language-search" class="form-control" placeholder="Start typing to filter languages..." autocomplete="off" value="<?php
                // Если в $form['language'] есть значение, находим соответствующий текст для отображения
                if (!empty($form['language'])) {
                    $selectedLang = LanguageAlpha2::tryFrom($form['language']);
                    if ($selectedLang) {
                        echo $this->e($selectedLang->name . ' (' . strtoupper($selectedLang->value) . ')');
                    }
                }
            ?>" />
            <div id="language-dropdown-plurals" class="list-group mt-1" style="max-height: 350px; overflow-y: auto; display: none; position: absolute; z-index: 1000; background: white; border: 1px solid #dee2e6; border-top: none;"></div>
            <!-- Скрытый select для отправки формы -->
            <select class="form-select" id="language-hidden-select" name="language" style="display:none;">
                <?php foreach (LanguageAlpha2::cases() as $lang): ?>
                    <option value="<?=$this->e($lang->value)?>" <?=$form['language'] === $lang->value ? 'selected' : ''?>><?=$this->e($lang->name) ?> (<?=$this->e(strtoupper($lang->value)) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="type" class="form-label">Type</label>
            <select class="form-select" id="type" name="type">
                <option value="<?=$this->e(CardinalPluralValue::getType())?>" <?=$form['type'] === CardinalPluralValue::getType() ? 'selected' : ''?>>
                    Cardinal (eg 1, 2, 3, ...)
                </option>
                <option value="<?=$this->e(OrdinalPluralValue::getType())?>" <?=$form['type'] === OrdinalPluralValue::getType() ? 'selected' : ''?>>
                    Ordinal (eg 1st, 2nd, 3rd, ...)
                </option>
            </select>
        </div>

        <ul class="mt-5">
            <?php $language = LanguageAlpha2::from($form['language']); ?>
            <?php foreach (AbstractPluralValue::getCategoriesForLanguage($language, $form['type']) as $category): ?>
                <li>
                    <span class="badge text-bg-primary"><?=$this->e($category)?></span>
                    <?=$this->e(implode(', ', AbstractPluralValue::getCategoryExamples($language, $category, $form['type'], 15)))?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</form>