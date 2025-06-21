<?php

use League\Plates\Template\Template;
use Slim\Http\ServerRequest;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;

/** @var Template $this */
/** @var ServerRequest $request */
/** @var RouteUri $route */
/** @var array $form */
/** @var string $error */

$this->layout('account_layout', ['request' => $request, 'title' => 'New project']);
$this->insert('project/_project_form', [
    'form' => $form,
    'error' => $error,
    'button' => 'Create project'
]);
