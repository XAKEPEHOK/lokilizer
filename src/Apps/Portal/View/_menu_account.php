<?php
/** @var Template $this */
/** @var ServerRequest $request */
/** @var RouteUri $route */
/** @var array $menu */

use League\Plates\Template\Template;
use Slim\Http\ServerRequest;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use XAKEPEHOK\Lokilizer\Components\Current;

?>

<ul class="navbar-nav justify-content-end">
    <?=$this->insert('_menu', ['menu' => $menu ?? []])?>
    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
            👤 <?= $this->e(Current::getUser()->getName()) ?>
        </a>
        <ul class="dropdown-menu">
            <li>
                <a class="dropdown-item" href="<?=$route('', false)?>">My projects</a>
            </li>
            <li>
                <a class="dropdown-item" href="<?=$route('profile', false)?>">Change profile</a>
            </li>
            <li>
                <a class="dropdown-item" href="<?=$route('profile/password', false)?>">Change password</a>
            </li>
            <li>
                <a target="_blank" class="dropdown-item" href="<?=$route('logout', false)?>">Logout</a>
            </li>

            <?php if ($_ENV['APP_ENV'] === 'dev'): ?>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a target="_blank" class="dropdown-item" href="https://admin:pass@<?=$_ENV['PROJECT_DOMAIN']?>:<?=$_ENV['APP_PORT']?>/mongo/db/lokilizer/">Mongo</a>
                </li>
            <?php endif; ?>
        </ul>
    </li>
</ul>