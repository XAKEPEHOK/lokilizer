<?php

use League\Plates\Template\Template;
use Slim\Http\ServerRequest;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use XAKEPEHOK\Lokilizer\Models\Project\Project;

/** @var Template $this */
/** @var ServerRequest $request */
/** @var RouteUri $route */
/** @var Project $project */
/** @var array $form */
/** @var string $error */

$this->layout('project_layout', ['request' => $request, 'title' => '🔤 Update project: ' . $project->getName()]);
$this->insert('project/_project_form', [
    'form' => $form,
    'error' => $error,
    'button' => 'Save',
    'update' => true,
]);
?>