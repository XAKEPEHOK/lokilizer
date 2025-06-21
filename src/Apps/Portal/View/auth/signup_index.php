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

$this->layout('guest_layout', ['request' => $request, 'title' => 'Signup'])
?>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
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
    });
</script>


<form method="post">
    <h1 class="h3 mb-3 fw-normal text-center">User signup</h1>

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

    <div class="row">
        <div class="col">
            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="passwordInput" name="password" value="<?= $password ?>" minlength="8">
                <label for="passwordInput">Password</label>
            </div>
        </div>
        <div class="col">
            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="passwordRepeatInput" name="passwordRepeat"
                       value="<?= $passwordRepeat ?>" minlength="8">
                <label for="passwordRepeatInput">Password repeat</label>
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

    <div class="mb-3 w-100 text-center">
        <div class="d-inline-block justify-content-center align-items-center border border-5 border-white" id="qrcode"></div>
    </div>

    <div class="form-floating mb-3">
        <input type="text" class="form-control" id="secondFA" name="secondFA" value="<?= $secondFA ?>">
        <label for="secondFA">2FA</label>
    </div>

    <input type="hidden" name="provisioningUri" value="<?= $provisioningUri; ?>">

    <button class="btn btn-primary w-100 py-2" type="submit">Sign up</button>
</form>