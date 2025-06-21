<?php
/** @var Template $this */
/** @var ServerRequest $request */
/** @var RouteUri $route */
/** @var array $params */

use League\Plates\Template\Template;
use Slim\Http\ServerRequest;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;

$withParams = function (array $extraParams) use ($route, $params) {
    $params = array_merge($params, $extraParams);
    return $this->e($route()->withQuery(http_build_query($params)));
};
?>

<?php if ($count > 0): ?>
    <nav class="d-flex justify-content-center">
        <ul class="pagination">
            <?php
            // Переменные, которые вы возможно получаете через $_GET или другие источники
            $pageNumber = $params['_page'];  // Текущая страница
            $pageSize = $params['_pageSize'];

            // Вычисление общего количества страниц
            $totalPages = ceil($count / $pageSize);

            // Определение начальной и конечной страницы для отображения
            $startPage = max(1, $pageNumber - 2);
            $endPage = min($totalPages, $pageNumber + 2);

            // Кнопка "Предыдущая страница"
            $prevDisabled = ($pageNumber == 1) ? ' disabled' : '';

            if ($startPage !== 1) {
                echo '<li class="page-item' . $prevDisabled . '">';
                echo '<a class="page-link" href="' . $withParams(['_page' => 1]) . '">';
                echo '<span>First</span>';
                echo '</a>';
                echo '</li>';
            }

            echo '<li class="page-item' . $prevDisabled . '">';
            echo '<a class="page-link" href="' . $withParams(['_page' => $pageNumber - 1]) . '" aria-label="Previous">';
            echo '<span aria-hidden="true">&laquo;</span>';
            echo '</a>';
            echo '</li>';


            // Корректировка начальной страницы, если видимых страниц меньше 4
            if ($pageNumber - $startPage < 2) {
                $endPage = min($totalPages, $endPage + (2 - ($pageNumber - $startPage)));
            }
            // Корректировка конечной страницы, если видимых страниц меньше 4
            if ($endPage - $pageNumber < 2) {
                $startPage = max(1, $startPage - (2 - ($endPage - $pageNumber)));
            }

            // Цифровые кнопки страниц
            for ($i = $startPage; $i <= $endPage; $i++) {
                if ($pageNumber == $i) {
                    echo '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
                } else {
                    echo '<li class="page-item"><a class="page-link" href="' . $withParams(['_page' => $i]) . '">' . $i . '</a></li>';
                }
            }

            // Кнопка "Следующая страница"
            $nextDisabled = ($pageNumber == $totalPages) ? ' disabled' : '';
            echo '<li class="page-item' . $nextDisabled . '">';
            echo '<a class="page-link" href="' . $withParams(['_page' => $pageNumber + 1]) . '">';
            echo '<span>&raquo;</span>';
            echo '</a>';
            echo '</li>';

            if ($endPage !== $totalPages) {
                echo '<li class="page-item' . $nextDisabled . '">';
                echo '<a class="page-link" href="' . $withParams(['_page' => $totalPages]) . '">';
                echo "<span>Last: {$totalPages}</span>";
                echo '</a>';
                echo '</li>';
            }
            ?>
            <li class="page-item disabled">
                <a class="page-link bg-info-subtle">Items: <?=$count?></a>
            </li>
        </ul>
    </nav>
<?php endif; ?>
