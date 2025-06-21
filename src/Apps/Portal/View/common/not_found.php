<?php

use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use League\Plates\Template\Template;
use Slim\Http\ServerRequest;

/** @var Template $this */
/** @var ServerRequest $request */
/** @var RouteUri $route */
$this->layout('project_layout', ['request' => $request, 'title' => 'Not found']);
?>

<div class="text-center">
    <h1>404 - Not found</h1>
</div>
