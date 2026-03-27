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

// Основная форма редактирования
$this->insert('project/_project_form', [
    'form' => $form,
    'error' => $error,
    'button' => 'Save',
    'update' => true,
]);
?>

<!-- Кнопка удаления -->
<div class="mt-4 pt-3 border-top">
    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirmNameModal">
        🗑️ Delete project
    </button>
</div>

<!-- === Этап 1: Подтверждение имени проекта === -->
<div class="modal fade" id="confirmNameModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">⚠️ Confirm project name</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <p>
                    To proceed with deletion, please type the exact project name:
                </p>
                <p class="fw-bold text-danger"><?= $this->e($project->getName()) ?></p>
                <input
                    type="text"
                    id="projectNameInput"
                    class="form-control mx-auto mt-3"
                    style="max-width: 300px;"
                    placeholder="Type project name"
                    autocomplete="off"
                >
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button
                    type="button"
                    id="confirmNameBtn"
                    class="btn btn-primary"
                    disabled
                >
                    Confirm name
                </button>
            </div>
        </div>
    </div>
</div>

<!-- === Этап 2: Таймер подтверждения === -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">⏰ Final confirmation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <p>
                    Project <strong><?= $this->e($project->getName()) ?></strong> will be <strong>permanently deleted</strong>.
                </p>
                <p class="text-danger"><strong>This cannot be undone!</strong></p>
                <p class="mt-3">
                    Please wait while we prepare the deletion...
                </p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="closeDeleteModal()">Cancel</button>
                <form method="POST" action="/project/<?= $this->e((string) $project->id()) ?>/delete" id="deleteProjectForm">
                    <?php if ($csrf = $request->getAttribute('csrf')): ?>
                        <input type="hidden" name="<?= $csrf->getTokenNameKey() ?>" value="<?= $csrf->getTokenValue() ?>">
                        <input type="hidden" name="<?= $csrf->getTokenTypeKey() ?>" value="<?= $csrf->getTokenValue() ?>">
                    <?php endif; ?>
                    <button
                        type="submit"
                        id="confirmDeleteBtn"
                        class="btn btn-danger"
                        disabled
                        style="width: 200px;"
                    >
                        Confirm deletion (<span id="countdown">15</span>s)
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(function () {
    const projectName = <?= json_encode($project->getName()) ?>;
    const $nameInput = $('#projectNameInput');
    const $confirmNameBtn = $('#confirmNameBtn');
    let deleteTimer = null;

    // Активация кнопки "Confirm name", если поле не пустое
    $nameInput.on('input', function () {
        const value = $(this).val().trim();
        $confirmNameBtn.prop('disabled', value === '');
    });

    // Подтверждение имени
    $confirmNameBtn.on('click', function () {
        const value = $nameInput.val().trim();
        if (value === projectName) {
            $('#confirmNameModal').modal('hide');
            $('#confirmDeleteModal').modal('show');
            startDeleteCountdown();
        } else {
            alert('Project name does not match. Please try again.');
            $nameInput.focus();
        }
    });

    function startDeleteCountdown() {
        let sec = 15;
        const $btn = $('#confirmDeleteBtn');
        const $countdown = $('#countdown');
        $btn.prop('disabled', true);
        $countdown.text(sec);

        deleteTimer = setInterval(() => {
            sec--;
            $countdown.text(sec);
            if (sec <= 0) {
                clearInterval(deleteTimer);
                $btn.prop('disabled', false).html('Confirm deletion');
            }
        }, 1000);
    }

    // Сброс таймера при закрытии второго модального окна
    window.closeDeleteModal = function () {
        if (deleteTimer) {
            clearInterval(deleteTimer);
            deleteTimer = null;
        }
        $('#confirmDeleteModal').modal('hide');
    };

    // Сброс при закрытии первого окна
    $('#confirmNameModal').on('hidden.bs.modal', function () {
        $nameInput.val('');
        $confirmNameBtn.prop('disabled', true);
    });
});
</script>