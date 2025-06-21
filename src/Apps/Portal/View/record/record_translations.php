<?php

use League\Plates\Template\Template;
use PrinsFrank\Standards\Language\LanguageAlpha2;
use Slim\Http\ServerRequest;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use XAKEPEHOK\Lokilizer\Models\Localization\Record;

/** @var Template $this */
/** @var ServerRequest $request */
/** @var RouteUri $route */
/** @var array $fsp */
/** @var LanguageAlpha2 $language */
/** @var LanguageAlpha2[] $languages */
/** @var callable $distill */

$this->layout('project_layout', ['request' => $request, 'title' => '🔤 Translations']);
?>
<div class="mb-4"></div>
<?php $this->insert('widgets/_list', [
    ...$fsp,
    'view' => function (Record $record) use ($languages, $distill) {
        /** @var Template $this */
        return $this->insert('widgets/_record_group', ['record' => $record, 'languages' => $languages, 'distill' => $distill]);
    }
])?>