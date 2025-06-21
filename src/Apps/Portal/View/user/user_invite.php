<?php

use League\Plates\Template\Template;
use PrinsFrank\Standards\Language\LanguageAlpha2;
use Slim\Http\ServerRequest;
use XAKEPEHOK\Lokilizer\Models\Project\Components\Role\Role;

/** @var Template $this */
/** @var ServerRequest $request */
/** @var string $role */
/** @var LanguageAlpha2[] $languages */
/** @var string[] $selectedLanguages */
/** @var string $error */

$this->layout('project_layout', ['request' => $request, 'title' => 'User invite', 'containerClass' => 'container-sm'])
?>


<div class="col-12 mx-auto">
    <form id="user-role-update"  method="post">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <div class="form-floating mb-3">
            <select class="form-select" id="role" name="role">
                <?php foreach (Role::cases() as $case): ?>
                    <option value="<?= $this->e($case->value) ?>" <?= $case->value === $role ? 'selected' : '' ?>>
                        <?= $this->e($case->name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <label for="role">Role</label>
        </div>

        <div class="mb-3">
            <?php foreach ($languages as $language): ?>
                <div class="form-check">
                    <input
                            class="form-check-input"
                            type="checkbox"
                            name="selectedLanguages[]"
                            value="<?= $this->e($language->value) ?>"
                            id="lang-<?= $this->e($language->value) ?>"
                        <?=in_array($language, $selectedLanguages) ? 'checked' : ''?>
                    >
                    <label class="form-check-label" for="lang-<?= $this->e($language->value) ?>">
                        <?= $this->e($language->name) ?>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>
    </form>
    <div class="vstack gap-2">
        <button form="user-role-update" class="btn btn-primary w-100 py-2" type="submit">Create invite</button>
    </div>
</div>
