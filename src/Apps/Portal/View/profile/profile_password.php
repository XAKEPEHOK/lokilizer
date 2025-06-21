<?php

use League\Plates\Template\Template;
use Slim\Http\ServerRequest;

/** @var Template $this */
/** @var ServerRequest $request */
/** @var string $currentPassword */
/** @var string $newPassword */
/** @var string $passwordRepeat */
/** @var string $error */

$this->layout('account_layout', ['request' => $request, 'title' => 'Password change'])
?>

<div class="row">
    <form class="mx-auto" method="post">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <div class="form-floating mb-3">
            <input type="password" class="form-control" id="currentPasswordInput" name="currentPassword"
                   value="<?= $currentPassword ?>" minlength="8" required>
            <label for="currentPasswordInput">Current password</label>
        </div>

        <div class="form-floating mb-3">
            <input type="password" class="form-control" id="newPasswordInput" name="newPassword"
                   value="<?= $newPassword ?>" minlength="8" required>
            <label for="newPasswordInput">New password</label>
        </div>
        <div class="form-floating mb-3">
            <input type="password" class="form-control" id="passwordRepeatInput" name="passwordRepeat"
                   value="<?= $passwordRepeat ?>" minlength="8" required>
            <label for="passwordRepeatInput">New password repeat</label>
        </div>

        <button class="btn btn-primary py-2" type="submit">Change password</button>
    </form>
</div>