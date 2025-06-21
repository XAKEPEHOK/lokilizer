<?php

use DiBify\DiBify\Repository\Components\Sorting;
use League\Plates\Template\Template;
use Slim\Http\ServerRequest;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;

/** @var Template $this */
/** @var ServerRequest $request */
/** @var RouteUri $route */
/** @var array $params */
/** @var array $columns */
/** @var callable $rowParams */
/** @var array $rows */
/** @var int $count */
/** @var callable $view */

?>

<script>
    $(document).ready(function () {
        $('.grid-search-input').change(function () {
            const $form = $('#grid-search');
            if ($form[0].checkValidity()) {
                $form.submit();
            } else {
                $form[0].reportValidity()
            }
        });
    });
</script>
<?php
$withParams = function (array $extraParams) use ($route, $params) {
    $params = array_merge($params, $extraParams);
    return $this->e($route()->withQuery(http_build_query($params)));
};

$renderAttrs = function (array $attrs) {
    $html = '';
    foreach ($attrs as $attr => $value) {
        $html .= $this->e($attr) . '="' . (is_array($value) ? implode(' ', array_map(fn($v) => $this->e($v), $value)) : $this->e($value)) . '"';
    }
    return $html;
};

$filter = function ($column) use ($params, $renderAttrs) {
    if (is_null($column['filter'])) {
        return '';
    }

    $name = $column['name'];
    $value = $params['_filter_' . $name] ?? '';
    $label = "<label for='field_{$this->e($name)}' class='form-label visually-hidden'>{$this->e($column['header'])}</label>";

    $attrs = $column['filterAttrs'] ?? [];
    unset($attrs['id']);
    unset($attrs['name']);
    unset($attrs['value']);
    $classes = $attrs['classes'] ?? '';
    unset($attrs['classes']);

    if (is_array($column['filter'])) {
        $options = [];
        $options[] = "<option value='' " . ($value === '' ? 'selected' : '') . "></option>";
        foreach ($column['filter'] as $optionKey => $optionValue) {
            if (is_array($optionValue)) {
                $options[] = '<optgroup label="' . $this->e($optionKey) . '">';
                foreach ($optionValue as $itemKey => $itemValue) {
                    $selected = $itemKey == $value ? 'selected' : '';
                    $options[] = "<option value='{$this->e($itemKey)}' {$selected}>{$this->e($itemValue)}</option>";
                }
                $options[] = '</optgroup>';
            } else {
                $selected = $optionKey == $value ? 'selected' : '';
                $options[] = "<option value='{$this->e($optionKey)}' {$selected}>{$this->e($optionValue)}</option>";
            }
        }

        return implode(' ', [
            $label,
            "<select {$renderAttrs($attrs)} class='form-select grid-search-input {$classes}' id='field_{$this->e($name)}' name='_filter_{$this->e($name)}'>",
            ...$options,
            "</select>",
        ]);
    }

    if (!isset($attrs['type'])) {
        $attrs['type'] = 'search';
        $attrs['pattern'] = match ($column['filter']) {
            'string' => '.*',
            'number' => '^[<>]?-?\d+$',
            'datetime' => '^\s*[<>]?\d{4}-\d{2}-\d{2}(\s\d{2}(:\d{2})?)?\s*$',
            default => '.*'
        };
    }

    return "{$label}<input {$renderAttrs($attrs)} class='form-control grid-search-input {$classes}' id='field_{$this->e($name)}' name='_filter_{$this->e($name)}' value='{$this->e($value)}'>";
};

?>

<div class="w-100">
    <form id="grid-search" class="container-fluid" action="" method="get">
        <input type="hidden" name="_sortBy" value="<?= $this->e($params['_sortBy']) ?>">
        <input type="hidden" name="_sortDirection" value="<?= $this->e($params['_sortDirection']) ?>">
        <input type="hidden" name="_page" value="<?= $this->e($params['_page']) ?>">
        <input type="hidden" name="_pageSize" value="<?= $this->e($params['_pageSize']) ?>">
        <div class="row">
            <?php foreach ($columns as $columnKey => $column): ?>
                <div class="col-lg-2 col-md-3 col-sm-6 col-xs-12 mb-2">

                    <?php if ($columnKey === $params['_sortBy']): ?>
                        <a
                                class="text-decoration-none"
                                href="<?= $withParams(['_sortBy' => $columnKey, '_sortDirection' => $params['_sortDirection'] === Sorting::SORT_DESC ? Sorting::SORT_ASC : Sorting::SORT_DESC]) ?>">
                            <?= $this->e($column['header']) ?>
                            <?= $params['_sortBy'] === $columnKey && $params['_sortDirection'] === Sorting::SORT_ASC ? '🔼' : '' ?>
                            <?= $params['_sortBy'] === $columnKey && $params['_sortDirection'] === Sorting::SORT_DESC ? '🔽' : '' ?>
                        </a>
                    <?php endif ?>
                    <?php if ($column['sortable'] && $columnKey !== $params['_sortBy']): ?>
                        <a
                                class="text-decoration-none"
                                href="<?= $withParams(['_sortBy' => $columnKey, '_sortDirection' => Sorting::SORT_DESC]) ?>">
                            <?= $this->e($column['header']) ?>
                        </a>
                    <?php endif ?>

                    <?php if (!$column['sortable']): ?>
                        <?= $this->e($column['header']) ?>
                    <?php endif ?>

                    <?= $filter($column) ?>
                </div>
            <?php endforeach; ?>
        </div>
    </form>

    <div class="mt-4 mb-3">
        <?=$this->insert('widgets/_pagination', ['params' => $params, 'count' => $count]); ?>
    </div>

    <div>
        <?php foreach ($rows as $row): ?>
        <?=$view($row)?>
        <?php endforeach; ?>
    </div>

    <div class="my-5">
        <?=$this->insert('widgets/_pagination', ['params' => $params, 'count' => $count]); ?>
    </div>
</div>
