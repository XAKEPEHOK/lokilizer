<?php

use League\Plates\Template\Template;
use Slim\Http\ServerRequest;
use XAKEPEHOK\Lokilizer\Models\User\Components\Theme;

/** @var Template $this */
/** @var ServerRequest $request */
/** @var string $email */
/** @var string $firstName */
/** @var string $lastName */
/** @var string $timezone */
/** @var Theme $theme */
/** @var string $error */

$this->layout('account_layout', ['request' => $request, 'title' => 'Profile'])
?>

<div class="row">
    <form class="mx-auto" method="post">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <div class="form-floating mb-3">
            <input type="email" class="form-control" id="email" name="email" value="<?= $email ?>">
            <label for="email">Email</label>
        </div>

        <div class="row">
            <div class="col-6">
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="firstNameInput" name="firstName" value="<?= $firstName ?>">
                    <label for="firstNameInput">First name</label>
                </div>
            </div>
            <div class="col-6">
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="lastNameInput" name="lastName" value="<?= $lastName ?>">
                    <label for="lastNameInput">Last name</label>
                </div>
            </div>
        </div>

        <div class="form-floating mb-3">
            <select class="form-select" id="floatingSelect" name="timezone">
                <?php foreach (timezone_identifiers_list() as $timezoneName): ?>
                    <option value="<?= $timezoneName; ?>" <?= $timezoneName === $timezone ? 'selected' : '' ?>><?= $timezoneName; ?></option>
                <?php endforeach; ?>
            </select>
            <label for="floatingSelect">Timezone</label>
        </div>

        <div class="form-floating mb-3">
            <select class="form-select" id="floatingSelect" name="theme">
                <?php foreach (Theme::cases() as $themeItem): ?>
                    <option value="<?= $themeItem->value; ?>" <?= $theme === $themeItem ? 'selected' : '' ?>>
                        <?= $themeItem->name; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <label for="floatingSelect">Theme</label>
        </div>

        <button class="btn btn-primary py-2" type="submit">Save</button>
    </form>
</div>