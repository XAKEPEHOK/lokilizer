<?php

use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use League\Plates\Template\Template;
use Slim\Http\ServerRequest;

/** @var Template $this */
/** @var ServerRequest $request */
/** @var RouteUri $route */
/** @var Exception $exception */

$this->layout('guest_layout', ['request' => $request, 'title' => 'Fatal error']);
?>

<div class="error-container" style="text-align: center">
    <div>
        <h1 class="display-1">🚫 You have no access to this action </h1>
    </div>
</div>
