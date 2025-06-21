<?php

use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use League\Plates\Template\Template;
use Slim\Http\ServerRequest;

/** @var Template $this */
/** @var ServerRequest $request */
/** @var RouteUri $route */
/** @var array $form */


$this->layout('guest_layout', ['request' => $request, 'title' => 'Home']) ?>

<h1 class="mb-5 text-center">You are not logged in</h1>

<div class="vstack gap-3">
    <form id="form" method="post" action="<?=$route('login')?>">
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
    <a href="<?=$route('signup')?>" class="btn btn-lg btn-success">Sign Up</a>
</div>