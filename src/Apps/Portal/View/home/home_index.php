<?php

use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use League\Plates\Template\Template;
use Slim\Http\ServerRequest;

/** @var Template $this */
/** @var ServerRequest $request */
/** @var RouteUri $route */
/** @var array $form */
/** @var bool $allowSignup */
/** @var bool $inviteFlag */ // <-- Добавляем аннотацию для $inviteFlag
/** @var array $debugInfo */

$this->layout('guest_layout', ['request' => $request, 'title' => 'Home']) ?>

<?php if ($_ENV['APP_ENV'] === 'dev'): ?>
    <script>
        // DEBUG: Вывод значения allowSignup и inviteFlag в консоль браузера
        console.log("DEBUG home_index.php: allowSignup variable is:", <?php echo json_encode($allowSignup); ?>);
        console.log("DEBUG home_index.php: inviteFlag variable is:", <?php echo json_encode($inviteFlag); ?>);
        console.log("DEBUG home_index.php: Typeof inviteFlag is:", typeof <?php echo json_encode($inviteFlag); ?>);
        console.log("DEBUG home_index.php: Current URL params: ", window.location.search);
        console.log("DEBUG home_index.php: Referrer URL (where user came from): ", document.referrer);

        // Вывод информации, полученной с сервера
        console.log("DEBUG home_index.php: Original URI (from PHP): ", <?php echo json_encode($debugInfo['originalUri'] ?? ''); ?>);
        console.log("DEBUG home_index.php: Referer header (from PHP): ", <?php echo json_encode($debugInfo['referrerHeader'] ?? ''); ?>);
        // /DEBUG
    </script>
<?php endif; ?>

<h1 class="mb-5 text-center">You are not logged in</h1>

<div class="vstack gap-3">
    <?php
    // --- Подготовка URL для формы входа с параметрами приглашения (если они есть) ---
    $loginActionRoute = $route('login'); // Получаем базовый маршрут /login
    // Проверяем, были ли переданы параметры приглашения из ProjectInviteAction
    $projectInviteIdForLogin = $projectInviteIdForLogin ?? null; // <-- Получаем projectId из данных рендера
    $projectInviteIdValueForLogin = $projectInviteIdValueForLogin ?? null; // <-- Получаем inviteId из данных рендера

    if ($projectInviteIdForLogin && $projectInviteIdValueForLogin) {
        // Добавляем параметры к URL формы входа
        $query_params = http_build_query([
            'pending_invite_project_id' => $projectInviteIdForLogin,
            'pending_invite_id' => $projectInviteIdValueForLogin
        ]);
        $loginActionRoute = $loginActionRoute->withQuery($query_params);
    }
    // --- /НОВОЕ ---
    ?>
    <form id="form" method="post" action="<?=$loginActionRoute?>"> <!-- <-- Используем модифицированный URL -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?=$error?>
            </div>
        <?php endif; ?>

        <div class="form-floating mb-3">
            <input type="email" class="form-control" autofocus id="email" name="email" value="<?=$form['email'] ?? ''?>" required>
            <label for="email">Email</label>
        </div>

        <div class="form-floating mb-3">
            <input type="password" class="form-control" autofocus id="password" name="password" value="<?=$form['password'] ?? ''?>" minlength="8" required>
            <label for="password">Password</label>
        </div>

        <div class="form-floating mb-3">
            <input type="text" class="form-control" autofocus id="otp" name="otp" value="<?=$form['otp'] ?? ''?>" maxlength="6" required>
            <label for="otp">OTP</label>
        </div>

        <button class="btn btn-lg btn-primary w-100 py-2" type="submit">Login</button>
    </form>

    <?php if ($allowSignup || $inviteFlag): ?>
        <?php
        // --- Подготовка URL для регистрации с параметрами приглашения ---
        $signupRoute = $route('signup'); // Получаем базовый маршрут /signup

        if ($inviteFlag) {
            // Получаем projectId и inviteId из оригинального URI (текущего запроса)
            $currentPath = $request->getUri()->getPath(); // Например, "/project/mhxh1zaz3kb8e4j3/invite/61b4f494-d5fc-43e2-8423-ce1b4fd0eeb5"
            $pathSegments = explode('/', trim($currentPath, '/')); // ["project", "mhxh1zaz3kb8e4j3", "invite", "61b4f494-d5fc-43e2-8423-ce1b4fd0eeb5"]

            // Проверяем, что сегментов достаточно и они соответствуют ожидаемому формату
            if (isset($pathSegments[0], $pathSegments[1], $pathSegments[2], $pathSegments[3]) &&
                $pathSegments[0] === 'project' &&
                $pathSegments[2] === 'invite') {

                $projectIdFromUrl = $pathSegments[1];
                $inviteIdFromUrl = $pathSegments[3];

                // Добавляем параметры к URL
                $query_params = http_build_query([
                    'invite_project_id' => $projectIdFromUrl,
                    'invite_id' => $inviteIdFromUrl
                ]);
                $signupRoute = $signupRoute->withQuery($query_params);
            }
            // Если формат URL не распознан, $signupRoute останется просто /signup
            // В SignupAction inviteFlag будет false, и сработают обычные проверки allowSignup/allowedEmails
        }

        ?>
        <a href="<?=$signupRoute?>" class="btn btn-lg btn-success">Sign Up</a>
    <?php endif; ?>
</div>