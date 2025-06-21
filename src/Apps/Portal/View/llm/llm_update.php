<?php

use League\Plates\Template\Template;
use Slim\Http\ServerRequest;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use XAKEPEHOK\Lokilizer\Models\LLM\LLMEndpoint;

/** @var Template $this */
/** @var ServerRequest $request */
/** @var RouteUri $route */
/** @var array $form */
/** @var string $error */
/** @var string $test */
/** @var LLMEndpoint $llm */

$this->layout('project_layout', ['request' => $request, 'title' => '🧠 Update ' . $llm->getName()]);
$this->insert('llm/_llm_form', [
    'form' => $form,
    'error' => $error,
    'test' => $test,
    'button' => 'Update endpoint',
    'llm' => $llm,
]);