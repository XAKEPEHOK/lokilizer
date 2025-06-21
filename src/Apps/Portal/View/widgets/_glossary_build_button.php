<?php

use League\Plates\Template\Template;
use Slim\Http\ServerRequest;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use XAKEPEHOK\Lokilizer\Components\Current;

/** @var Template $this */
/** @var ServerRequest $request */
/** @var RouteUri $route */
/** @var string $key */
/** @var string $class */

?>

<?php if (!empty($key)): ?>
    <form
            class="d-inline-block submit-confirmation"
            action="<?= $this->e($route('glossary/_build')) ?>"
            method="post"
            data-confirmation="Are you sure you want to build glossary for this key?"
    >
        <div class="dropup d-inline-block">
            <button class="btn btn-outline-primary dropdown-toggle <?=$this->e($class ?? '')?>" type="button" data-bs-toggle="dropdown"
                    title="Build glossary summary for this key">
                📜
            </button>
            <ul class="dropdown-menu">
                <?php foreach (Current::getLLMEndpoints() as $llm): ?>
                    <li>
                        <button type="submit" class="dropdown-item" name="build" value="<?= base64_encode(json_encode([$this->e($key), $llm->id()])) ?>">
                            <?= $this->e($llm->getName()) ?>
                        </button>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </form>

<?php endif; ?>