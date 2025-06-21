<?php

use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use League\Plates\Template\Template;
use Slim\Http\ServerRequest;

/** @var Template $this */
/** @var ServerRequest $request */
/** @var RouteUri $route */
/** @var string $error */

$this->layout('guest_layout', ['request' => $request, 'title' => $error]);
?>

<div class="error-container" style="text-align: center">
    <div>
        <h1 class="display-1">404</h1>
        <p class="lead"><?= $this->e($error) ?></p>
        <a href="<?= $route('/') ?>" class="btn btn-primary">Back to main page</a>
    </div>
</div>
