<?php
/** @var Template $this */
/** @var ServerRequest $request */
/** @var RouteUri $route */

/** @var array $menu */

use League\Plates\Template\Template;
use Slim\Http\ServerRequest;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\MenuItem;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;

$visibleMenu = [];
foreach ($menu as $rootKey => $menuItem) {
    if (is_array($menuItem)) {
        $nested = [];
        foreach ($menuItem as $nestedKey => $item) {
            if ($item instanceof MenuItem) {
                if ($item->isVisible()) {
                    $nested[$nestedKey] = $item->route;
                }
                continue;
            }
            $nested[$nestedKey] = $item;
        }

        $keys = array_keys($nested);
        $values = array_values($nested);
        $start = 0;
        $end = count($values) - 1;

        while ($start <= $end && $values[$start] === null) {
            $start++;
        }

        while ($end >= $start && $values[$end] === null) {
            $end--;
        }

        $nested = array_slice($nested, $start, $end - $start + 1, true);

        if (!empty($nested)) {
            $visibleMenu[$rootKey] = $nested;
        }
        continue;
    }

    if ($menuItem instanceof MenuItem) {
        if ($menuItem->isVisible()) {
            $visibleMenu[$rootKey] = $menuItem->route;
        }
        continue;
    }

    $visibleMenu[$rootKey] = $menuItem;
}

?>

<ul class="navbar-nav me-auto mb-2 mb-lg-0">
    <?php foreach ($visibleMenu as $item => $routeOrSubmenu): ?>
        <?php if (is_array($routeOrSubmenu)): ?>
            <?php $isActive = str_starts_with($route(), $route(explode('/', current($routeOrSubmenu))[0])) ?>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle text-nowrap <?= $isActive ? 'active' : '' ?>" href="#"
                   role="button" data-bs-toggle="dropdown">
                    <?= $this->e($item) ?>
                </a>
                <ul class="dropdown-menu">
                    <?php foreach ($routeOrSubmenu as $subItem => $subRoute): ?>
                        <li>
                            <?php if ($subRoute): ?>
                                <a class="dropdown-item <?= strval($route()) === strval($route($subRoute)) ? 'active' : '' ?>"
                                   href="<?= $route($subRoute) ?>">
                                    <?= $this->e($subItem) ?>
                                </a>
                            <?php endif; ?>
                            <?php if (!$subRoute): ?>
                                <hr class="dropdown-divider">
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </li>
            <?php continue; ?>
        <?php endif; ?>

        <li class="nav-item">
            <a class="nav-link text-nowrap <?= $route() === $route($routeOrSubmenu) ? 'active' : '' ?>"
               href="<?= $route($routeOrSubmenu) ?>">
                <?= $this->e($item) ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>