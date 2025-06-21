<?php
/** @var Template $this */
/** @var ServerRequest $request */
/** @var RouteUri $route */

/** @var SpecialGlossary $glossary */

use League\Plates\Template\Template;
use Slim\Http\ServerRequest;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Models\Glossary\SpecialGlossary;
use XAKEPEHOK\Lokilizer\Models\Project\Components\Role\Permission;

?>

<div class="card mb-4">
    <div class="card-header">
        <div class="row">
            <div class="col">
                <code><?= $this->e($glossary->getKeyPrefix()) ?></code>
            </div>
            <div class="col text-end">
                Phrases count:
                <span class="badge text-bg-primary"><?= count($glossary->getItems()) ?></span>
                <?php foreach ($glossary->getLanguages() as $lang): ?>
                    <span class="badge text-bg-info">
                        <?= strtoupper($lang->value) ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($glossary->getSummary())): ?>
            <span class="text-secondary">No summary</span>
        <?php endif; ?>
        <?= $this->e($glossary->getSummary()) ?>
    </div>
    <div class="card-footer text-body-secondary">
        <div class="row">
            <div class="col">
                <?php if (Current::can(Permission::MANAGE_GLOSSARY)): ?>
                    <a href="<?= $route("glossary/{$glossary->id()}") ?>" class="btn btn-primary btn-sm">Edit</a>
                    <form
                            method="post"
                            action="<?=$route("glossary/{$glossary->id()}")?>"
                            class="d-inline-block submit-confirmation"
                            data-confirmation="Are you sure you want to DELETE this glossary?">
                        <button class="btn btn-danger btn-sm" name="delete" value="delete" type="submit">Delete</button>
                    </form>
                <?php endif; ?>
            </div>
            <div class="col text-end">
                $<?= round($glossary->LLMCost()->getResult(), 4) ?>
            </div>
        </div>
    </div>
</div>
