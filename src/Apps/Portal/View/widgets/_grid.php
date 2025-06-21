<?php

use DiBify\DiBify\Model\Reference;
use DiBify\DiBify\Repository\Components\Sorting;
use League\Plates\Template\Template;
use Slim\Http\ServerRequest;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use XAKEPEHOK\Lokilizer\Models\Project\Project;
use XAKEPEHOK\Lokilizer\Models\User\User;

/** @var Template $this */
/** @var ServerRequest $request */
/** @var RouteUri $route */
/** @var array $params */
/** @var array $columns */
/** @var callable $rowParams */
/** @var array $rows */
/** @var int $count */

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
            "<select form='grid-search' {$renderAttrs($attrs)} class='form-select grid-search-input {$classes}' id='field_{$this->e($name)}' name='_filter_{$this->e($name)}'>",
            ...$options,
            "</select>",
        ]);
    }

    if (!isset($attrs['type'])) {
        $attrs['type'] = 'search';
        $attrs['pattern'] = match ($column['filter']) {
            'string' => '.*',
            'number', 'money' => '^[<>]?-?\d+$',
            'datetime' => '^\s*[<>]?\d{4}-\d{2}-\d{2}(\s\d{2}(:\d{2})?)?\s*$',
            default => '.*'
        };
    }

    return "{$label}<input form='grid-search' {$renderAttrs($attrs)} class='form-control grid-search-input {$classes}' id='field_{$this->e($name)}' name='_filter_{$this->e($name)}' value='{$this->e($value)}'>";
};

$cellByType = function ($column, $row) use ($route) {
    switch ($column['type']) {
        case 'action':
            /** @var DateTimeInterface $value */
            $value = $column['value']($row);
            if (empty($value)) {
                return '';
            }
            $links = [];
            foreach ($value as $emoji => $uri) {
                if ($uri === null) {
                    continue;
                }
                $links[] = "<a class='text-decoration-none' href='{$this->e($uri)}'>{$this->e($emoji)}</a>";
            }
            return implode(' ', $links);
        case 'widget':
            /** @var array $value */
            $value = $column['value']($row);
            if (empty($value)) {
                return '';
            }
            return $this->fetch(...$value);
        case 'datetime':
            /** @var DateTimeInterface $value */
            $value = $column['value']($row);
            if (is_null($value)) {
                return '';
            }
            return "<span class='text-nowrap'>" . implode('', [
                    '<span class="text-nowrap">' . $value->format('Y-m-d') . '</span>',
                    '<br>',
                    '<span class="text-nowrap">' . $value->format('H:i:s') . '</span>',
                ]) . '</span>';
        case 'timeago':
            /** @var DateTimeInterface $value */
            $value = $column['value']($row);
            if (is_null($value)) {
                return '';
            }
            return $this->fetch('widgets/_timeago', ['datetime' => $value]);
        case 'reference':
            /** @var Reference $value */
            $value = $column['value']($row);
            if (is_null($value)) {
                return '';
            }
            return "<span class='text-nowrap'>" . match ($value->getModelAlias()) {
                    Project::getModelAlias() => "💼 <a data-bs-toggle='tooltip' data-bs-title='{$this->e($value->getModel()->getEmail())}' href='" . $this->e($route("companies/view/{$value->id()}")) . "'>Company #{$this->e($value->id())}</a>",
                    User::getModelAlias() => "👤 User #{$this->e($value->id())}",
                    default => "❔ " . $this->e(ucfirst($value->getModelAlias())) . " #{$this->e($value->id())}"
                } . '</span>';
        case 'uri':
            /** @var string $value */
            $value = $column['value']($row);
            if (is_null($value)) {
                return '';
            }
            if (is_array($value)) {
                $text = array_key_first($value);
                $uri = $value[$text];
                return "<a rel='noopener nofollow noreferrer' target='_blank' href='{$this->e($uri)}'>{$this->e($text)}</a>";
            }
            return "<a rel='noopener nofollow noreferrer' target='_blank' href='{$this->e($value)}'>{$this->e($value)}</a>";
        case 'bool':
            return $column['value']($row) === true ? '✅' : '';
        case 'html':
            return $column['value']($row);
        case 'line':
            return '<span class="text-nowrap">' . trim($this->e($column['value']($row))) . '</span>';
        default:
            return nl2br(trim($this->e($column['value']($row))));
    }
};

?>

<div class="w-100 border-top pt-2">
    <div class="text-end">
        <div class="dropdown">
            <button class="btn btn-sm btn-outline-info dropdown-toggle" type="button" data-bs-toggle="dropdown"
                    aria-expanded="false">
                <span class="badge rounded-pill text-bg-info">Found: <?= $count ?></span> ⚙️
            </button>
            <ul class="dropdown-menu">
                <?php foreach ($columns as $columnKey => $column): ?>
                    <li>
                        <div class="dropdown-item">
                            <input class="form-check-input" type="checkbox" value="" id="<?= $columnKey ?>" checked>
                            <label class="form-check-label" for="<?= $columnKey ?>">
                                <?= $this->e($column['header']) ?>
                            </label>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <form id="grid-search" class="position-relative overflow-x-auto pb-3" action="" method="get">
        <input type="hidden" name="_sortBy" value="<?= $this->e($params['_sortBy']) ?>">
        <input type="hidden" name="_sortDirection" value="<?= $this->e($params['_sortDirection']) ?>">
        <input type="hidden" name="_page" value="<?= $this->e($params['_page']) ?>">
        <input type="hidden" name="_pageSize" value="<?= $this->e($params['_pageSize']) ?>">
    </form>
    <table class="table">
        <thead>
        <tr>
            <?php foreach ($columns as $columnKey => $column): ?>
                <th scope="col" class="text-nowrap">
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
                </th>
            <?php endforeach; ?>
        </tr>
        <?php if (count(array_filter(array_column($columns, 'filter')))): ?>
            <tr class="align-top">
                <?php foreach ($columns as $columnKey => $column): ?>
                    <td>
                        <div class="input-groupinput-group-sm">
                            <?= $filter($column) ?>
                        </div>
                    </td>
                <?php endforeach; ?>
            </tr>
        <?php endif; ?>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr <?= $renderAttrs($rowParams($row)) ?>>
                <?php foreach ($columns as $columnKey => $column): ?>
                    <td <?= (isset($column['attrs']) ? ($renderAttrs($column['attrs']($row))) : '') ?>>
                        <?php foreach ($column['value'] as $index => $columnData): ?>
                            <div <?php /*=(isset($columnData['attrs']) ? ($renderAttrs($columnData['attrs']($row))) : '')*/ ?>>
                                <?= $cellByType($columnData, $row) ?>
                            </div>
                        <?php endforeach; ?>
                    </td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="my-5">
        <?=$this->insert('widgets/_pagination', ['params' => $params, 'count' => $count]); ?>
    </div>
</div>
