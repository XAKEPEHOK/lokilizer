<?php
/** @var Template $this */
/** @var ServerRequest $request */

/** @var string $title */

use League\Plates\Template\Template;
use Slim\Http\ServerRequest;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Components\Db\Storage\Mongo\MongoStorage;
use XAKEPEHOK\Lokilizer\Models\User\Components\Theme;

$theme = Current::hasUser() ? Current::getUser()->getTheme() : Theme::Dark;
?>
<!doctype html>
<html lang="en" data-bs-theme="<?=$theme->value?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $this->e($title) ?> | <?=$this->e($_ENV['PROJECT_NAME'])?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="/style.css?3" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
            integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r"
            crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"
            integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy"
            crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
    <script src="https://cdn.jsdelivr.net/npm/timeago@1.6.7/jquery.timeago.min.js"></script>

    <script src="/scripts.js?9"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Color+Emoji&display=swap" rel="stylesheet">
    <style>
        :root {
            --bs-font-sans-serif: "Segoe UI", Roboto, "Helvetica Neue", "Noto Sans", "Liberation Sans", Arial, "Noto Color Emoji", sans-serif;
        }
    </style>

</head>
<body>
<?php if ($_ENV['APP_ENV'] === 'dev'): ?>
    <div class="accordion" id="dbQueries">
        <div class="accordion-item border-warning  rounded-0">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                        data-bs-target="#dbQueriesList">
                    Time: <?=round(microtime(true) - $request->getAttribute('startedAt', 0), 2)?>;
                    Db queries: <?= count(MongoStorage::$queries) ?>
                </button>
            </h2>
            <div id="dbQueriesList" class="accordion-collapse collapse" data-bs-parent="#dbQueries">
                <div class="accordion-body">
                    <ol>
                        <?php foreach (MongoStorage::$queries as $query): ?>
                            <li><code><?= $this->e($query) ?></code></li>
                        <?php endforeach; ?>
                    </ol>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
<?= $this->section('content') ?>
</body>
</html>