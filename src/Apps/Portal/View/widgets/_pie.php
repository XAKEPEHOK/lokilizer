<?php

use League\Plates\Template\Template;

/** @var Template $this */
/** @var null|string $title */
/** @var null|string $label */
/** @var null|bool|string $legend */
/** @var array $data */
/** @var array $colors */
/** @var bool $countInLabel */

$legend = !isset($legend) ? true : $legend;

if (!isset($countInLabel)) {
    $countInLabel = true;
}

$labels = [];
foreach ($data as $label => $value) {
    if ($countInLabel) {
        $labels[] = $value . " " . $label;
    } else {
        $labels[] = $label;
    }
}

?>

<canvas class="chart-widget">
    <?= json_encode([
        'type' => 'pie',
        'data' => [
            'labels' => $labels,
            'datasets' => [
                [
                    'data' => array_values($data),
                    'borderWidth' => 1,
                    'backgroundColor' => $colors ?? null,
                ]
            ]
        ],
        'options' => [
            'animation' => [
                'duration' => 0,
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => $legend !== false,
                    'position' => is_string($legend) ? $legend : 'top',
                    'labels' => [
                        'boxWidth' => 20,
                    ],
                ],
                'title' => [
                    'display' => isset($title),
                    'text' => $title ?? '',
                ],
            ],
        ],
    ]) ?>
</canvas>
