<?php

use League\Plates\Template\Template;
use Slim\Http\ServerRequest;

/** @var Template $this */
/** @var ServerRequest $request */
/** @var string $title */

$this->layout('_layout', ['request' => $request, 'title' => $title]);
?>
<div class="container-sm p-5">
    <div class="row justify-content-center">
        <div class="col-sm-12 col-md-10 col-lg-6 mx-auto">
            <a class="d-block mx-auto mb-3 text-center" href="<?=$this->e($_ENV['PROJECT_HOME'])?>">
                <img class="w-50" src="/logo.png" alt="<?=$this->e($_ENV['PROJECT_NAME'])?>">
            </a>
            <?= $this->section('content') ?>
            <br>
            <br>

            <?php /* Please, do not remove this block. This links may help me in SEO */ ?>
            <?php /* This is small cost for you, but important thing for me */ ?>
            <?= $this->insert('_adv') ?>

        </div>
    </div>
</div>