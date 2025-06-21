<?php

use League\Plates\Template\Template;
use Slim\Http\ServerRequest;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;

/** @var Template $this */
/** @var ServerRequest $request */
/** @var RouteUri $route */
/** @var array $fsp */

$this->layout('project_layout', ['request' => $request, 'title' => '🧠 LLM endpoints']);
?>
<div class="mb-4">
    <a class="btn btn-primary" href="<?=$route('llm/add')?>">
        Add LLM
    </a>
</div>
<?php $this->insert('widgets/_grid', $fsp)?>