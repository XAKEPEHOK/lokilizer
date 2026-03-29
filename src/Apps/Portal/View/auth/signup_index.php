<?php

use League\Plates\Template\Template;
use Slim\Http\ServerRequest;

/** @var Template $this */
/** @var ServerRequest $request */
/** @var string $email */
/** @var string $firstName */
/** @var string $lastName */
/** @var string $password */
/** @var string $passwordRepeat */
/** @var string $timezone */
/** @var string $secondFA */
/** @var string $provisioningUri */
/** @var string $error */
/** @var bool $allowSignup */
/** @var bool $inviteFlag */ // <-- Добавляем объявление переменной

$this->layout('guest_layout', ['request' => $request, 'title' => 'Signup'])
?>

<style>
#timezone-dropdown mark {
    background-color: #4a5568; /* темно-серый фон — под тёмную тему */
    color: #fbbf24;            /* янтарный/золотистый текст — хорошо читается */
    padding: 0 3px;
    border-radius: 3px;
    font-weight: bold;
}
</style>

<?php if ($_ENV['APP_ENV'] === 'dev'): ?>
    <script>
        console.log("DEBUG signup_index.php: allowSignup variable is:", <?php echo json_encode($allowSignup); ?>);
        console.log("DEBUG signup_index.php: inviteFlag variable is:", <?php echo json_encode($inviteFlag); ?>);
    </script>
<?php endif; ?>

<!-- Подключаем QRCode.js только если регистрация разрешена или есть флаг приглашения -->
<?php if ($allowSignup === true || $inviteFlag === true): ?>
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script> <!-- Исправлен URL -->
    <script>
        $(document).ready(function () {
            //https://github.com/llyys/qrcodejs/
            new QRCode(
                document.getElementById("qrcode"),
                {
                    text: "<?=$provisioningUri?>",
                    correctLevel: QRCode.CorrectLevel.L
                }
            );

            // Подготавливаем данные о временных зонах один раз
            const timezoneOptions = [];
            $('#timezone-hidden-select option').each(function () {
                const $opt = $(this);
                const value = $opt.val();
                const text = $opt.text().trim();
                if (value) { // Пропускаем пустую опцию
                    timezoneOptions.push({ value, text });
                }
            });

            const $search = $('#timezone-search');
            const $dropdown = $('#timezone-dropdown');
            const $select = $('#timezone-hidden-select'); // Скрытый select для отправки формы
            let lastInputValue = $search.val(); // Для отслеживания изменений

            // Вспомогательная функция для подсветки
            function highlightMatch(text, query) {
                if (!query.trim()) return text;
                const regex = new RegExp('(' + query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
                return text.replace(regex, '<mark>$1</mark>');
            }

            // Изменённая функция showDropdown
            // Принимает items (всегда весь список), query (для подсветки) и optional targetId для прокрутки
            function showDropdown(items, query, targetId = null) {
                if (items.length === 0) { // Этот случай маловероятен, если всегда передаём timezoneOptions
                    $dropdown.html('<div class="list-group-item text-muted">No timezones found</div>');
                } else {
                    const html = items.map(opt => {
                        const highlighted = highlightMatch(opt.text, query);
                        // Добавляем уникальный ID на основе значения опции для прокрутки
                        const elementId = 'tz-option-' + opt.value.replace(/[^\w-]/g, '_'); // Заменяем недопустимые символы в ID
                        return `<button type="button" class="list-group-item list-group-item-action" data-value="${opt.value}" id="${elementId}">${highlighted}</button>`;
                    }).join('');

                    $dropdown.html(html);

                    // Находим и прокручиваем к элементу с targetId, если он передан и существует
                    if (targetId) {
                        const $targetElement = $('#' + targetId);
                        if ($targetElement.length) {
                            // Простая прокрутка к элементу
                            $dropdown.scrollTop($targetElement.position().top + $dropdown.scrollTop());

                            // Опционально: добавить кратковременную подсветку найденного элемента
                            // $targetElement.addClass('bg-info'); // Bootstrap class for highlight
                            // setTimeout(() => {
                            //     $targetElement.removeClass('bg-info');
                            // }, 1500);
                        }
                    }

                    // Привязываем обработчик клика к новым кнопкам
                    $dropdown.find('button').on('click', function () {
                        const value = $(this).data('value');
                        const text = $(this).text(); // .text() уберёт <mark>
                        $search.val(text);
                        $select.val(value); // Устанавливаем значение в скрытом select
                        $dropdown.hide();
                        lastInputValue = text; // Обновляем последнее значение
                        $('#floatingSelect').addClass('filled');
                    });
                }

                $dropdown.show();

                // Логика закрытия при клике вне (остаётся без изменений)
                $(document).off('click.timezoneDropdown').on('click.timezoneDropdown', function (e) {
                    if (!$(e.target).closest('#timezone-search-container, #timezone-dropdown').length) {
                        $dropdown.hide();
                        if (!$search.val()) {
                            $('#floatingSelect').removeClass('filled');
                        }
                        $(document).off('click.timezoneDropdown');
                    }
                });
            }

            // Изменённая функция updateDropdown
            function updateDropdown(query) {
                let itemsToShow = timezoneOptions; // Всегда показываем весь список

                // Находим первый совпадающий элемент (если query не пустой)
                let firstMatchId = null;
                if (query.trim()) {
                    const firstMatch = timezoneOptions.find(opt =>
                        opt.text.toLowerCase().includes(query.toLowerCase())
                    );
                    if (firstMatch) {
                        firstMatchId = 'tz-option-' + firstMatch.value.replace(/[^\w-]/g, '_'); // Генерируем ID как в showDropdown
                    }
                }
                // Вызываем showDropdown с полным списком и ID первого совпадения (если есть)
                showDropdown(itemsToShow, query, firstMatchId);
            }

            // Событие фокуса: выделяем весь текст
            $search.on('focus', function () {
                // Ставим таймер, чтобы выделение сработало после того, как браузер установит фокус
                const $this = $(this);
                setTimeout(function() {
                    $this.select(); // Выделяем весь текст
                    // Показываем список с текущим значением (или пустым)
                    updateDropdown($this.val());
                }, 0);
            });

            // Событие ввода: обновляем список
            $search.on('input', function () {
                const currentVal = $(this).val();
                updateDropdown(currentVal);
                lastInputValue = currentVal; // Обновляем последнее значение при вводе
                // Убираем плавающую метку при вводе
                $('#floatingSelect').addClass('filled');
            });

            // Событие потери фокуса: скрываем список, восстанавливаем метку при необходимости
            $search.on('blur', function() {
                // Небольшая задержка, чтобы обработать клик по элементу списка
                setTimeout(() => {
                    if (!$(document.activeElement).closest('#timezone-dropdown').length) {
                        $dropdown.hide();
                        // Восстанавливаем плавающую метку, если поле пустое
                        if (!$(this).val()) {
                            $('#floatingSelect').removeClass('filled');
                        }
                    }
                }, 150); // Небольшая задержка для обработки клика по списку
            });

            // Событие нажатия клавиш (стрелки, Enter)
            $search.on('keydown', function(e) {
                // Обработка стрелок и Enter можно добавить позже, если нужно
                // Пока просто обновляем список при изменении значения
            });

        });
    </script>
<?php endif; ?>

<form method="post">
    <h1 class="h3 mb-3 fw-normal text-center">User signup</h1>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" role="alert">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <?php
    // Проверяем, разрешена ли регистрация или есть флаг приглашения. Если нет, не отображаем остальные поля формы.
    if ($allowSignup === true || $inviteFlag === true):
    ?>
        <div class="form-floating mb-3">
            <input type="email" class="form-control" id="email" name="email" value="<?= $this->e($email) ?>">
            <label for="email">Email</label>
        </div>

        <div class="row">
            <div class="col-6">
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="firstNameInput" name="firstName" value="<?= $this->e($firstName) ?>">
                    <label for="firstNameInput">First name</label>
                </div>
            </div>
            <div class="col-6">
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="lastNameInput" name="lastName" value="<?= $this->e($lastName) ?>">
                    <label for="lastNameInput">Last name</label>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="passwordInput" name="password" value="<?= $this->e($password) ?>" minlength="8">
                    <label for="passwordInput">Password</label>
                </div>
            </div>
            <div class="col">
                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="passwordRepeatInput" name="passwordRepeat"
                           value="<?= $this->e($passwordRepeat) ?>" minlength="8">
                    <label for="passwordRepeatInput">Password repeat</label>
                </div>
            </div>
        </div>

        <!-- Новый контейнер для фильтруемого выбора временной зоны -->
        <div class="form-floating mb-3 position-relative" id="timezone-search-container">
            <input type="text" class="form-control" id="timezone-search" placeholder="Select timezone..." autocomplete="off" value="<?php
                // Если в $timezone есть значение, находим соответствующий текст для отображения
                if ($timezone !== '') {
                    $tzList = timezone_identifiers_list();
                    $selectedTzName = '';
                    foreach ($tzList as $tzName) {
                        if ($tzName === $timezone) {
                            $selectedTzName = $tzName;
                            break;
                        }
                    }
                    echo $this->e($selectedTzName);
                } else {
                    // Если $timezone пустой (например, при первом открытии), можно показать пустое поле или UTC
                    // В данном случае, если $timezone === '', то и $this->e($selectedTzName) будет '', что правильно
                    // Если вы хотите, чтобы по умолчанию было "UTC", раскомментируйте строку ниже:
                    // echo 'UTC';
                }
            ?>" />
            <label for="timezone-search" id="floatingSelectLabel">Timezone</label>

            <div id="timezone-dropdown" class="list-group mt-1" style="max-height: 350px; overflow-y: auto; display: none; position: absolute; z-index: 1000; background: white; border: 1px solid #dee2e6; border-top: none; width: 100%;"></div>

            <!-- Скрытый select для отправки формы -->
            <select class="form-select" id="timezone-hidden-select" name="timezone" style="display:none;">
                <option value="" <?=empty($timezone) ? 'selected' : ''?>>Select timezone</option>
                <?php foreach (timezone_identifiers_list() as $timezoneName): ?>
                    <option value="<?= $this->e($timezoneName); ?>" <?= $timezoneName === $timezone ? 'selected' : '' ?>><?= $this->e($timezoneName); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <!-- /Контейнер для фильтруемого выбора временной зоны -->

        <div class="mb-3 w-100 text-center">
            <p class="text-muted small">
                Scan the QR code below with an authenticator app like Google Authenticator, Microsoft Authenticator, or any other OTP-compatible app.
            </p>
            <div class="d-inline-block justify-content-center align-items-center border border-5 border-white" id="qrcode"></div>
        </div>

        <div class="form-floating mb-3">
            <input type="text" class="form-control" id="secondFA" name="secondFA" value="<?= $this->e($secondFA) ?>">
            <label for="secondFA">2FA code from your OTP app</label>
        </div>

        <input type="hidden" name="provisioningUri" value="<?= $this->e($provisioningUri); ?>">

        <button class="btn btn-primary w-100 py-2" type="submit">Sign up</button>
    <?php endif; ?>
</form>