<?php

use League\Plates\Template\Template;
use PrinsFrank\Standards\Language\LanguageAlpha2;
use Slim\Http\ServerRequest;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;

/** @var Template $this */
/** @var ServerRequest $request */
/** @var RouteUri $route */
/** @var array $form */
/** @var string $error */

$this->layout('project_layout', ['request' => $request, 'title' => '📤 Upload translation file']) ?>

<script>
$(function () {
    // Подготавливаем данные о языках один раз
    const languageOptions = [];
    $('#language option').each(function () {
        const $opt = $(this);
        const value = $opt.val();
        // 🔧 Используем trim() для text, чтобы убрать пробелы
        const text = $opt.text().trim();
        if (value) {
            languageOptions.push({ value, text });
        }
    });

    const $search = $('#language-search');
    const $dropdown = $('#language-dropdown');
    const $select = $('#language');
    const $fileInput = $('#file');

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
        // 👇 Добавляем: устанавливаем ширину списка равной ширине поля ввода
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
                const text = $(this).text();
                $search.val(text);
                $select.val(value);
                $dropdown.hide();
                $(document).off('click.languageDropdown');
            });
        }

        $dropdown.show();

        // Закрытие при клике вне
        $(document).off('click.languageDropdown').on('click.languageDropdown', function (e) {
            if (!$(e.target).closest('#language-search, #language-dropdown').length) {
                $dropdown.hide();
                $(document).off('click.languageDropdown');
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

    // Обработка выбора файла
    $fileInput.on('change', function () {
        const file = this.files[0];
        if (!file) return;

        const fileName = file.name.replace(/\.json$/i, '');
        const langCandidate = fileName.split('_')[0];

        const option = $('#language option[value="' + langCandidate + '"]');
        if (option.length) {
            const text = option.text();
            $search.val(text);
            $select.val(langCandidate);
        }
    });
});
</script>

<form method="post" enctype="multipart/form-data" class="mt-5 row">
    <div class="col mx-auto">

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?= $this->e($error) ?>
            </div>
        <?php endif; ?>

        <div class="mb-3">
            <label for="file" class="form-label">File</label>
            <input type="file" name="file" id="file" class="form-control" accept=".json"/>
        </div>

        <div class="mb-3">
            <label for="language-search" class="form-label">Language</label>
            <input type="text" id="language-search" class="form-control" placeholder="Start typing to filter languages..." autocomplete="off" />
            <div id="language-dropdown" class="list-group mt-1" style="max-height: 350px; overflow-y: auto; display: none; position: absolute; z-index: 1000; background: white; border: 1px solid #dee2e6; border-top: none;"></div>
            <!-- Скрытый select для отправки формы -->
            <select class="form-select" id="language" name="language" style="display:none;">
                <option value="" <?=empty($form['language']) ? 'selected' : ''?>>Select language</option>
                <?php foreach (LanguageAlpha2::cases() as $lang): ?>
                    <option value="<?=$this->e($lang->value)?>" <?=$form['language'] === $lang->value ? 'selected' : ''?>><?=$this->e($lang->name) ?> (<?=$this->e(strtoupper($lang->value)) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-primary mt-3">Upload file</button>
    </div>
</form>