<?php

use XAKEPEHOK\Lokilizer\Models\User\User;
use League\Plates\Template\Template;
use Slim\Http\ServerRequest;

/** @var Template $this */
/** @var ServerRequest $request */
/** @var string|null $title */
/** @var int|null $current */
/** @var int|null $max */
/** @var array<integer, array{time: integer, key: string, message: string|string[], type: string}> $logs */
/** @var array{message: string, type: string}|null $finish */

/** @var User $user */
$user = $request->getAttribute('user');

$this->layout('project_layout', ['request' => $request, 'title' => $title ?? 'Task progress']) ?>

<script>
    $(document).ready(function () {
        var interval;

        function updateProgressContainer() {
            $.ajax({
                url: window.location.href, // Текущая страница
                method: 'GET',
                success: function (data) {
                    let title = $(data).find('title').html();
                    $('title').html(title);

                    let h1 = $(data).find('h1').html();
                    $('h1').html(h1);

                    let progress = $(data).find('#progress-container').html();
                    $('#progress-container').html(progress);

                    // console.log('refresh');
                    if ($('#progress-finished').length > 0) {
                        clearInterval(interval);
                        console.log('finished');
                    }
                },
                error: function () {
                    console.error('Ошибка при обновлении содержимого контейнера.');
                }
            });
        }

        interval = setInterval(updateProgressContainer, 3000);
    });
</script>

<div id="progress-container">
    <?php if (is_null($current)): ?>
        <div class="text-center">
            <div class="spinner-border" style="width: 3rem; height: 3rem;" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($finish): ?>
        <div id="progress-finished" class="alert alert-<?= $this->e($finish['type']) ?>">
            <?php if (str_starts_with($finish['message'], 'https://')): ?>
                <a href="<?= $this->e($finish['message']) ?>"><?= $this->e($finish['message']) ?></a>
            <?php else: ?>
                <?= $this->e($finish['message']) ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($current !== null): ?>

        <?php if ($max > 0): ?>
            <?php $percent = $current / ($max / 100); ?>
            <div class="progress" role="progressbar">
                <div class="progress-bar" style="width: <?= $percent ?>%">
                    <?= $this->e($current) ?>/<?= $this->e($max) ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($max == 0 || $max === null): ?>
            <div class="d-flex justify-content-center align-items-center gap-3">
                <?php if (!$finish): ?>
                    <div class="spinner-border" style="width: 3rem; height: 3rem;" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <span class="fs-3"><?= $this->e($current) ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!$finish): ?>
            <form class="my-3 text-center" action="" method="post">
                <button class="btn btn-danger" type="submit">Force stop</button>
            </form>
        <?php endif; ?>

        <?php if (!empty($logs)): ?>
            <table class="table mt-3">
                <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr class="table-<?= $this->e($log['type']) ?>">
                        <td class="w-25 text-wrap text-break font-monospace">
                            <?= $this->e($log['key']) ?>
                        </td>
                        <td>
                            <?php if (is_array($log['message'])): ?>
                                <ul>
                                    <?php foreach ($log['message'] as $message): ?>
                                        <li>
                                            <?= $this->e($message) ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>


                            <?php if (is_string($log['message'])): ?>
                                <?= $this->e($log['message']) ?>
                            <?php endif; ?>
                        </td>
                        <!--                        <td class="text-end">-->
                        <!--                            --><?php //= date('Y-m-d H:i:s', $log['time']) ?>
                        <!--                        </td>-->
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>
</div>