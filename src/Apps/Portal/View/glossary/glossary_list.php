<?php

use League\Plates\Template\Template;
use Slim\Http\ServerRequest;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use XAKEPEHOK\Lokilizer\Models\Glossary\Glossary;

/** @var Template $this */
/** @var ServerRequest $request */
/** @var RouteUri $route */
/** @var array $fsp */

$this->layout('project_layout', ['request' => $request, 'title' => '📙 Special glossary']);
?>
<div class="mb-4">
    <a href="<?=$route('glossary/new')?>" class="btn btn-success">Create special glossary</a>
</div>
<?php $this->insert('widgets/_list', [
    ...$fsp,
    'view' => function (Glossary $glossary) {
        /** @var Template $this */
        return $this->insert('glossary/_glossary_view_card', ['glossary' => $glossary]);
    }
])?>