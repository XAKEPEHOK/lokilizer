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
        // Обработчик кнопки Add Row (оставлен без изменений)
        $('#addRow').on('click', function (e) {
            e.preventDefault();

            let $lastTwoRows = $('#glossary tbody tr').slice(-2);
            let $clonedRows = $lastTwoRows.clone();
            $clonedRows.find('input, textarea').val('');
            $('#glossary tbody').append($clonedRows);
        });

        // --- Новый JavaScript для фильтра языков ---
        // Подготавливаем данные о доступных языках из скрытого списка
        const languageOptions = [];
        // Ищем скрытый список внутри нужного dropdown
        const $hiddenLanguageList = $('.dropup.d-inline-block .btn-outline-success.dropdown-toggle').closest('.dropup').find('ul.d-none.list-unstyled');
        $hiddenLanguageList.find('li a[data-language-value]').each(function () {
            const $opt = $(this);
            const value = $opt.data('language-value');
            const text = $opt.text().trim(); // 🔧 Используем trim() для text
            const href = $opt.attr('href');
            if (value && text) { // Проверяем, что есть и значение, и текст
                languageOptions.push({ value, text, href });
            }
        });

        const $languageDropdownButton = $('.dropup.d-inline-block .btn-outline-success.dropdown-toggle');
        const $languageSearch = $languageDropdownButton.siblings('.dropdown-menu').find('#languageFilterInput');
        const $originalLanguageList = $languageDropdownButton.siblings('.dropdown-menu').find('ul.language-list-original'); // Цель - оригинальный список
        const $originalDropdownMenu = $languageDropdownButton.siblings('.dropdown-menu');

        // Вспомогательная функция для подсветки
        function highlightMatch(text, query) {
            if (!query.trim()) return text;
            const regex = new RegExp('(' + query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
            return text.replace(regex, '<mark>$1</mark>');
        }

        // Показать фильтрованные результаты ВНУТРИ оригинального списка
        function showLanguageDropdown(items, query) {
            if (items.length === 0) {
                $originalLanguageList.html('<li class="px-3 py-2 text-muted">No matches found</li>');
            } else {
                const html = items.map(opt => {
                    const highlighted = highlightMatch(opt.text, query);
                    // Используем <a> внутри <li> как в оригинальном списке
                    return `<li><a class="dropdown-item language-result-item" href="${opt.href}">${highlighted}</a></li>`;
                }).join('');

                $originalLanguageList.html(html);

                // Привязываем обработчик клика к новым элементам ВНУТРИ оригинального списка
                $originalLanguageList.find('.language-result-item').on('click', function (e) {
                    // Обработчик клика останется стандартным: переход по href
                    // Код закрытия и очистки будет в глобальном обработчике клика вне области
                });
            }
            // $originalLanguageList.show(); // Уже отображается, так как внутри dropdown-menu
        }

        // Обновление оригинального списка (заменяет его содержимое)
        function updateLanguageDropdown(query) {
            let itemsToShow;
            if (!query.trim()) {
                // Показываем все языки, как в оригинальном списке
                itemsToShow = languageOptions;
            } else {
                itemsToShow = languageOptions.filter(opt =>
                    opt.text.toLowerCase().includes(query.toLowerCase()) ||
                    opt.value.toLowerCase().includes(query.toLowerCase())
                );
            }
            showLanguageDropdown(itemsToShow, query);
        }

        // Восстановление оригинального списка (опционально, например, при закрытии)
        function restoreOriginalList() {
             // Просто обновляем список с пустым запросом, чтобы показать все языки
             // или можно сохранить оригинальный HTML и восстановить его.
             updateLanguageDropdown('');
        }

        // События для фильтрации
        $languageSearch.on('input', function () {
            const query = $(this).val();
            updateLanguageDropdown(query);
        });

        // Обработчик открытия dropdown - фокус на поле ввода
        $languageDropdownButton.on('shown.bs.dropdown', function () {
            setTimeout(function() {
                $languageSearch.focus();
                // При открытии показываем все языки (восстанавливаем оригинал)
                restoreOriginalList();
            }, 100);
        });

        // Обработчик закрытия dropdown - очистка фильтра
        $languageDropdownButton.on('hidden.bs.dropdown', function () {
            $languageSearch.val(''); // Очищаем поле ввода
            // Можно восстановить оригинальный список при закрытии, но это делается при открытии
            // restoreOriginalList(); // Вызовем при открытии, чтобы список был актуален
            $(document).off('click.languageGlossaryDropdown'); // Отписываемся от глобального обработчика
        });

        // Глобальный обработчик клика для очистки при переходе по ссылке или закрытия
        // (Bootstrap сам закроет dropdown при клике по ссылке из .dropdown-item)
        // Мы можем использовать его для очистки поля при необходимости, но обработчик клика по item уже внутри списка
        // Подпишемся снова при каждом открытии/обновлении списка, но отпишемся при закрытии dropdown
        // Лучше отписаться при любом закрытии (hidden.bs.dropdown) и не подписываться тут постоянно
        // Вместо этого, просто убедимся, что при открытии список восстанавливается.

    });
</script>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger mb-3" role="alert">
        <?= $this->e($error) ?>
    </div>
<?php endif; ?>

<div class="container">
    <a href="<?= $route("glossary/view/{$glossary->id()}") ?>" class="btn btn-outline-secondary mb-4">View-mode</a>
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
                        <button class="btn btn-outline-success dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Add language
                        </button>
                        <!-- Добавлен id для целевого CSS -->
                        <div id="language-dropdown-menu" class="dropdown-menu my-3"
                             style="max-width: 250px; max-height: none; height: auto; min-width: 250px; overflow-y: visible; overflow-x: visible;">
                            <!-- Поле ввода остается видимым -->
                            <div class="px-3 py-2 border-bottom">
                                <input type="text" id="languageFilterInput" class="form-control form-control-sm" placeholder="Filter languages..." style="font-size: 0.875rem;" autocomplete="off">
                            </div>
                            <!-- Оригинальный список языков, который будет динамически изменяться JS -->
                            <ul class="list-unstyled mb-0 language-list-original" style="max-height: 210px; overflow-y: auto; overflow-x: hidden;">
                                <?php foreach ($otherLanguages as $language): ?>
                                    <li>
                                        <a class="dropdown-item" href="<?= $route("glossary/{$glossary->id()}") ?>?addLang=<?= $language->value ?>" data-language-value="<?= $language->value ?>">
                                            <?= $this->e(str_replace('_', ' ', $language->name)) ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <!-- Скрытый список с исходными элементами для JS-поиска (на всякий случай, можно удалить, если не нужен) -->
                            <ul class="list-unstyled d-none">
                                <?php foreach ($otherLanguages as $language): ?>
                                    <li>
                                        <a class="dropdown-item" href="<?= $route("glossary/{$glossary->id()}") ?>?addLang=<?= $language->value ?>" data-language-value="<?= $language->value ?>">
                                            <?= $this->e(str_replace('_', ' ', $language->name)) ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>