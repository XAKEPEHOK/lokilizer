$(document).ready(function () {
    const dropdownElement = document.querySelectorAll('.dropdown-toggle');
    [...dropdownElement].map(dropdownElement => new bootstrap.Dropdown(dropdownElement, {
        popperConfig: function (defaultBsPopperConfig) {
            return {
                ...defaultBsPopperConfig,
                strategy: 'fixed' // Устанавливаем strategy на 'fixed'
            };
        }
    }))

    $('form.submit-confirmation').on('submit', function(e) {
        // Выводим окно подтверждения
        if (!confirm($(this).attr('data-confirmation'))) {
            // Если пользователь нажал "Отмена", отменяем отправку формы
            e.preventDefault();
        }
    });

    $(document).on('click', '.collapse-suggestion', function () {
        const className = $(this).attr('data-bs-target');
        if ($(this).hasClass('collapsed')) {
            $(className + '-input').val('')
        }
    })

    $(document).on('click', '.apply-suggestion', function (e) {
        e.preventDefault();
        let $pair = $(this).closest('.pair-with-suggest');
        let suggest = $pair.find('.pair-with-suggest-suggest').val()
        $pair.find('.pair-with-suggest-value').val(suggest)
    })

    $(document).on('input', 'textarea.record-textarea.autosize', function() {
        this.style.height = 'auto';
        this.style.height = Math.max(this.scrollHeight, 20) + 'px'; // min ~1 строка
    });

    // Инициализация при загрузке
    $('textarea.record-textarea.autosize').each(function() {
        this.style.height = 'auto';
        this.style.height = Math.max(this.scrollHeight, 20) + 'px';
    });

    $('textarea.textarea-autosize').each(function() {
        this.style.height = 'auto';
        this.style.height = this.scrollHeight + 'px';
    })

    jQuery.timeago.settings.allowFuture = true

    const handlers = (function () {
        // Используем WeakSet для хранения обработанных элементов тултипа и timeago
        const processedTooltips = new WeakSet();
        const processedTimeago = new WeakSet();

        return function () {
            // Обработка тултипов
            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            tooltipTriggerList.forEach(tooltipTriggerEl => {
                if (!processedTooltips.has(tooltipTriggerEl)) {
                    new bootstrap.Tooltip(tooltipTriggerEl);
                    processedTooltips.add(tooltipTriggerEl);
                }
            });

            // Обработка элементов timeago
            const timeagoElements = document.querySelectorAll('.timeago');
            timeagoElements.forEach(el => {
                if (!processedTimeago.has(el)) {
                    $(el).timeago();
                    processedTimeago.add(el);
                }
            });

            // Обработка элементов timeago
            $('textarea.textarea-autosize').each(function() {
                this.style.height = 'auto';
                this.style.height = this.scrollHeight + 'px';
            })
        };
    })();

    handlers();
    setInterval(handlers, 1000 * 10)

    let formState = {};

    // 1. Отслеживаем фокус и изменения в input[type="text"] и textarea внутри форм с классом 'record-input-form'
    $(document).on('focusin', 'form.record-input-form input[type="text"], form.record-input-form textarea', function () {
        let $input = $(this);
        let $form = $input.closest('form.record-input-form');

        if ($input.attr('id')) { // Проверяем наличие ID
            let formId = $form.attr('id') || $form.index(); // Используем ID формы или её индекс как ключ
            formState[formId] = formState[formId] || {};
            formState[formId].focusedId = $input.attr('id');
            formState[formId].cursorPos = $input.prop('selectionStart') || 0;
        }
    });

    // 2. Обновляем позицию курсора при вводе текста (keyup и mouseup)
    $(document).on('keyup mouseup', 'form.record-input-form input[type="text"], form.record-input-form textarea', function () {
        let $input = $(this);
        let $form = $input.closest('form.record-input-form');

        if ($input.attr('id')) {
            let formId = $form.attr('id') || $form.index();
            formState[formId] = formState[formId] || {};
            formState[formId].focusedId = $input.attr('id');
            formState[formId].cursorPos = $input.prop('selectionStart') || 0;
        }
    });

    $(document).on('keydown', '.submit-ctrl-s', function(e) {
        // Проверяем, что нажаты Ctrl (или Cmd для Mac) и клавиша "S" (код 83)
        if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S' || e.keyCode === 83)) {
            e.preventDefault(); // Отменяем стандартное действие браузера (сохранение страницы)
            const $form = $(this).closest("form");
            $form.submit();
        }
    });

    $(document).on('click', '.llm-handle', function(e) {
        e.preventDefault();
        const $a = $(this);
        const $group = $a.closest(".record-input-group");
        const $form = $a.closest(".record-input-form");
        const $target = $group.length > 0 ? $group : $form;

        // Проверить, не активирована ли уже обработка для этого блока
        if ($target.data('_llmProcessing')) {
            console.log('Запрос к LLM для этого блока уже обрабатывается.');
            return;
        }

        // Установить флаг обработки
        $target.data('_llmProcessing', true);

        // Создаем элемент индикатора загрузки
        const $loadingIndicator = $('<div></div>');
        $loadingIndicator.addClass('llm-loading-indicator');
        $loadingIndicator.html('<div class="d-flex flex-column align-items-center justify-content-center"><div class="spinner-border spinner-border-sm text-primary me-2" role="status"><span class="visually-hidden">Loading...</span></div><span class="mt-1">Processing...</span></div>');

        // Стили для индикатора
        $loadingIndicator.css({
            'position': 'absolute',
            'display': 'flex',
            'justifyContent': 'center',
            'alignItems': 'center',
            'width': '100%',
            'height': '100%',
            'top': '0',
            'left': '0',
            'backgroundColor': 'rgba(255, 255, 255, 0.8)',
            'color': '#007bff',
            'fontSize': '14px',
            'fontWeight': 'bold',
            'zIndex': '10',
            'pointerEvents': 'none'
        });

        // Добавляем индикатор к $target
        const currentPos = $target.css('position');
        if (currentPos === 'static') {
            $target.css('position', 'relative');
        }
        $target.append($loadingIndicator);

        // Остальная логика старого кода с сохранением поведения
        $target.addClass('opacity-50');
        $('fieldset.record-input-fieldset').prop('disabled', true);

        const $groupInput = $form.find('input[type="hidden"][name="group"]');
        const data = new FormData();
        data.append('group', $groupInput.val());

        $.ajax({
            url: $a.attr('href'),
            type: 'post',
            data,
            processData: false,
            contentType: false,
            dataType: 'html',
            success: function (response) {
                let $newForm = $(response);
                $target.replaceWith($newForm);
                handlers()
            },
            error: function () {
                $target.removeClass('opacity-50');
                alert('There was an error when handling LLM task. Please try again.');
            },
            complete: function () {
                // Удалить индикатор прогресса и снять флаг обработки
                $target.find('.llm-loading-indicator').remove();
                $target.removeData('_llmProcessing');

                $('fieldset.record-input-fieldset').prop('disabled', false);
            }
        });
    });

    $(document).on('click', '.test-handle', function(e) {
        e.preventDefault();
        const $a = $(this);
        const $form = $a.closest(".record-input-form");
        const $groupInput = $form.find('input[type="hidden"][name="group"]');
        const data = new FormData();
        data.append('group', $groupInput.val());

        $.ajax({
            url: $a.attr('href'),
            type: 'post',
            data,
            processData: false,
            contentType: false,
            success: function (response) {
                alert(response)
            },
            error: function () {
                alert('There was an error when handling LLM task. Please try again.');
            },
        });
    });

    $(document).on('submit', 'form.record-input-form', function (event) {
        event.preventDefault(); // Предотвращаем стандартную отправку формы

        let $form = $(this); // Сохраняем ссылку на текущую форму
        let actionUrl = $form.attr('action'); // Получаем URL из атрибута 'action'
        let method = ($form.attr('method') || 'POST').toUpperCase(); // Получаем метод отправки, по умолчанию POST
        let formData = $form.serialize(); // Сериализуем данные формы

        let formId = $form.attr('id') || $form.index(); // Используем ID формы или её индекс как ключ
        let focusedId = formState[formId] ? formState[formId].focusedId : null;
        let cursorPos = formState[formId] ? formState[formId].cursorPos : 0;

        $form.addClass('opacity-50');

        $('fieldset.record-input-fieldset').prop('disabled', true);

        // Выполняем AJAX-запрос
        $.ajax({
            url: actionUrl,
            type: method,
            data: formData,
            dataType: 'html',
            success: function (response) {
                let $newForm = $(response);
                $form.replaceWith($newForm);
                if (focusedId) {
                    // Ищем в новой форме элемент с тем же ID
                    let $focusEl = $newForm.find('#' + focusedId);
                    if ($focusEl.length) {
                        $focusEl.focus(); // Устанавливаем фокус

                        // Восстанавливаем позицию курсора
                        let el = $focusEl[0];
                        if (typeof el.setSelectionRange === 'function') {
                            // Проверяем, что позиция курсора не превышает длину значения
                            let pos = Math.min(cursorPos, el.value.length);
                            el.setSelectionRange(pos, pos);
                        }
                    }
                }
                handlers()
            },
            error: function (jqXHR, textStatus, errorThrown) {
                $form.removeClass('opacity-50');
                alert('There was an error when sending a form. Please try again.');
            },
            complete: function () {
                $('fieldset.record-input-fieldset').prop('disabled', false);
            }
        });
    });

});