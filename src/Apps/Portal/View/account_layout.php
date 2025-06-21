<?php

use League\Plates\Template\Template;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use XAKEPEHOK\Lokilizer\Models\User\User;
use Slim\Http\ServerRequest;

/** @var Template $this */
/** @var ServerRequest $request */
/** @var string $title */
/** @var string $containerClass */

$containerClass = $containerClass ?? 'container';

$this->layout('_layout', ['request' => $request, 'title' => $title]);
$route = new RouteUri($request);

/** @var User $user */
$user = $request->getAttribute('user');

$menu = [
    '📂 My projects' => '',

];
?>
<div style="height: 100svh;">
    <nav class="navbar navbar-expand-md bg-body-tertiary mb-3">
        <div class="container">
            <a class="navbar-brand" href="<?= $route('') ?>">
                <img src="/logo_mini.png" alt="<?=$this->e($_ENV['PROJECT_NAME'])?>" height="24">
                <?= $this->e($_ENV['PROJECT_NAME']) ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-layout">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbar-layout">
                <?=$this->insert('_menu', ['menu' => $menu])?>
                <?=$this->insert('_menu_account')?>
            </div>
        </div>
    </nav>
    <div class="<?=$this->e($containerClass)?> px-3 position-relative overflow-x-auto" style="min-height: 80svh">
        <?php if (!empty($_GET['_alert'])):?>
        <div class="alert alert-<?=$_GET['_alert_type'] ?? 'info'?>">
            <?=$this->e($_GET['_alert'])?>
        </div>
        <?php endif;?>
        <h1 class="mb-3"><?=$this->e($title)?></h1>
        <?= $this->section('content') ?>
    </div>
</div>