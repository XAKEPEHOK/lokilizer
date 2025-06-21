<?php

use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use League\Plates\Template\Template;
use Slim\Http\ServerRequest;

/** @var Template $this */
/** @var ServerRequest $request */
/** @var bool $displayErrorDetails */
/** @var Exception $exception */

$this->layout('guest_layout', ['request' => $request, 'title' => 'Error']);
?>

<div class="error-container" style="text-align: center">
    <div>
        <h1 class="display-1">500 🫠</h1>
        <p class="lead"><?= $this->e($exception->getMessage()) ?></p>
        <?php if ($displayErrorDetails): ?>
        <pre style="text-align: left"><code><?=$this->e($exception->getFile())?>:<?=$this->e($exception->getLine())?></code></pre>
        <pre style="text-align: left"><code><?=$this->e($exception->getTraceAsString())?></code></pre>
        <?php endif;?>
    </div>
</div>
