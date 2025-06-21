<?php

use League\Plates\Template\Template;
use Slim\Http\ServerRequest;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;

/** @var Template $this */
/** @var ServerRequest $request */
/** @var RouteUri $route */
/** @var array $form */
/** @var string $error */
/** @var string $test */

$this->layout('project_layout', ['request' => $request, 'title' => '🧠 Add LLM endpoint']);
$this->insert('llm/_llm_form', [
    'form' => $form,
    'error' => $error,
    'test' => $test,
    'button' => 'Add endpoint',
]);