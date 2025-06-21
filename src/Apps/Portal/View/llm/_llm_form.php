<?php

use League\Plates\Template\Template;
use Slim\Http\ServerRequest;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use XAKEPEHOK\Lokilizer\Models\LLM\LLMEndpoint;

/** @var Template $this */
/** @var ServerRequest $request */
/** @var RouteUri $route */
/** @var array $form */
/** @var string $error */
/** @var string $test */
/** @var ?LLMEndpoint $llm */
/** @var string $button */
?>

<?php if (!empty($test)): ?>
    <div class="alert alert-info">
        <?= $this->e($test) ?>
    </div>
<?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="mt-4 row">
        <div class="col mx-auto">

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?= $this->e($error) ?>
                </div>
            <?php endif; ?>

            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" class="form-control" id="name" name="name" value="<?= $this->e($form['name']) ?>"
                       required minlength="3">
            </div>

            <div class="row mb-3">
                <div class="col-8">
                    <label for="name" class="form-label">Uri</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="uri" name="uri"
                               value="<?= $this->e($form['uri']) ?>" required>
                        <span class="input-group-text">/chat/completions</span>
                    </div>
                    <div class="form-text">Example: <code>https://api.openai.com/v1</code></div>
                </div>
                <div class="col-4">
                    <label for="name" class="form-label">Timeout</label>
                    <input type="text" class="form-control" id="timeout" name="timeout"
                           value="<?= $this->e($form['timeout']) ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="name" class="form-label">Proxy</label>
                <input type="text" class="form-control" id="proxy" name="proxy" value="<?= $this->e($form['proxy']) ?>">
                <div class="form-text">
                    Leave blank to disable or
                    <ul>
                        <li><code>https://username:password@proxy.example.com:1080</code></li>
                        <li><code>socks5://username:password@proxy.example.com:1080</code></li>
                        <li><code>socks5h://username:password@proxy.example.com:1080</code></li>
                    </ul>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-6">
                    <label for="name" class="form-label">Model</label>
                    <input type="text" class="form-control" id="model" name="model"
                           value="<?= $this->e($form['model']) ?>" required autocomplete="off">
                </div>

                <div class="col-6">
                    <label for="name" class="form-label">Token</label>
                    <input type="password" class="form-control" id="token" name="token"
                           value="<?= $this->e($form['token']) ?>" required autocomplete="new-password">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-6">
                    <label for="name" class="form-label">Input price per 1M token</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="text" class="form-control" id="pricingInput" name="pricingInput"
                               value="<?= $this->e($form['pricingInput']) ?>" required>
                    </div>
                </div>
                <div class="col-6">
                    <label for="name" class="form-label">Output price per 1M token</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="text" class="form-control" id="pricingOutput" name="pricingOutput"
                               value="<?= $this->e($form['pricingOutput']) ?>" required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-xs-12 col-6">
                    <button type="submit" class="btn btn-primary"><?= $this->e($button) ?></button>
                    <?php if (isset($llm)): ?>
                        <button class="btn btn-danger" name="delete" value="delete" type="submit" form="delete">🗑
                        </button>
                    <?php endif; ?>
                </div>
                <div class="col-xs-12 col-6">
                    <div class="d-flex align-content-end">
                        <button type="submit" name="test" value="1" class="btn btn btn-outline-info ms-auto">Test
                            connection
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
<?php if (isset($llm)): ?>
    <form
            id="delete"
            method="post"
            action="<?= $route("llm/{$llm->id()}") ?>?delete=1"
            class="d-inline-block submit-confirmation"
            data-confirmation="Are you sure you want to DELETE this LLM endpoint?">
    </form>
<?php endif; ?>