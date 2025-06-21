<?php

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Components;

use Adbar\Dot;
use DateTimeImmutable;
use DiBify\DiBify\Repository\Components\Pagination;
use DiBify\DiBify\Repository\Components\Sorting;
use League\Plates\Engine;
use XAKEPEHOK\Lokilizer\Components\Db\ApiSearchInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use Throwable;

abstract class RenderAction
{

    public function __construct(
        protected readonly Engine $renderer
    )
    {
    }

    abstract public function __invoke(Request $request, Response $response): Response|ResponseInterface;

    protected function render(Response $response, string $template, array $data = []): Response
    {
        try {
            $request = $data['request'];
            $route = new RouteUri($request);
            if (isset($data['fsp'])) {
                /** @var ApiSearchInterface $repo */
                $repo = $data['fsp']['repo'];
                $prepare = $data['fsp']['prepare'] ?? function () {};
                $columns = $this->fspPrepareColumns($data['fsp']['columns']);
                $params = $this->fspPrepareParams($columns, $request->getQueryParams(), $data['fsp']['sort'] ?? 'ids');
                $count = $repo->countByAPI($this->fspPrepareFilters($columns, $params));
                $params['_page'] = max(1, min(ceil($count/$params['_pageSize']), $params['_page']));

                $sort = null;
                $sortColumn = $params['_sortBy'] ?? null;
                if ($sortColumn && isset($columns[$sortColumn]) && $columns[$sortColumn]['sortable']) {
                    $sort = new Sorting($columns[$sortColumn]['sortable'], $params['_sortDirection']);
                }

                $data['fsp']['request'] = $request;
                $data['fsp']['route'] = $route;
                $data['fsp']['params'] = $params;
                $data['fsp']['columns'] = $columns;
                $data['fsp']['count'] = $count;

                $models = $repo->searchByAPI(
                    filters: $this->fspPrepareFilters($columns, $params),
                    pagination: new Pagination($params['_page'], $params['_pageSize']),
                    sort: $sort
                );

                foreach ($models as $model) {
                    $prepare($model);
                }

                $data['fsp']['rows'] = $models;

                if (!isset($data['fsp']['rowParams'])) {
                    $data['fsp']['rowParams'] = fn() => [];
                }
            }

            $this->renderer->addData([
                'route' => $route,
                'request' => $request,
            ]);
            return $response->write($this->renderer->render($template, $data));
        } catch (Throwable $exception) {
            return $this->render($response, 'errors/fatal', [
                'request' => $request,
                'exception' => $exception,
            ]);
        }
    }

    protected function fspPrepareFilters(array $columns, array $params): array
    {
        $params = array_filter($params, fn($value) => $value !== '' && $value !== null);
        $deletedPrefix = [];
        foreach ($params as $key => $value) {
            $deletedPrefix[str_replace('_filter_', '', $key)] = $value;
        }

        $params = $deletedPrefix;
        $result = new Dot($deletedPrefix);

        foreach ($columns as $name => $column) {
            if (!isset($params[$name])) {
                continue;
            }

            $filter = $column['filter'];
            $raw = trim($params[$name]);
            $param = $raw;

            if (in_array($filter, ['number', 'datetime'])) {
                if (str_starts_with($raw, '>')) {
                    $param = [
                        'gte' => trim($param, '>'),
                        'lte' => null,
                    ];
                } elseif (str_starts_with($raw, '<')) {
                    $param = [
                        'gte' => null,
                        'lte' => trim($param, '<'),
                    ];
                } else {
                    $param = [
                        'gte' => $param,
                        'lte' => $param,
                    ];
                }

                switch ($filter) {
                    case 'datetime':
                        if ($param['gte']) {
                            $_filler = '0000-00-00 00:00:00';
                            $param['gte'] = new DateTimeImmutable($param['gte'] . substr($_filler, strlen($param['gte'])));
                        }
                        if ($param['lte']) {
                            $_filler = '0000-00-00 23:59:59';
                            $param['lte'] = new DateTimeImmutable($param['lte'] . substr($_filler, strlen($param['lte'])));
                        }
                        break;
                }
            }

            //Bool filter
            if (is_array($filter) && count($filter) === 2 && array_is_list($filter)) {
                $param = boolval(intval($param));
            }

            $result->set($column['filterPath'], $param);
        }

        return $result->all();
    }

    protected function fspPrepareParams(array $columns, array $params, string $sortBy): array
    {
        $sortable = array_map(fn(array $data) => $data['sortable'], $columns);
        $sortable = array_filter($sortable, fn(mixed $value) => !empty($value));

        if (!isset($params['_sortBy'])) {
            $params['_sortBy'] = $sortBy;
        }

        if (!isset($sortable[$params['_sortBy']])) {
            $params['_sortBy'] = $sortBy;
        }

        if (!isset($params['_sortDirection'])) {
            $params['_sortDirection'] = Sorting::SORT_DESC;
        }

        if (!isset($params['_page'])) {
            $params['_page'] = 1;
        }

        $params['_page'] = max(1, $params['_page']);
        $params['_pageSize'] = max(1, min(50, $params['_pageSize'] ?? 50));

        return $params;
    }

    private function fspPrepareColumns(array $columns): array
    {
        foreach ($columns as $name => &$column) {

            $column['name'] = $name;

            if (!isset($column['type'])) {
                $column['type'] = 'string';
            }

            if (!isset($column['sortable'])) {
                $column['sortable'] = null;
            }

            if (is_bool($column['sortable'])) {
                $column['sortable'] = $column['sortable'] ? $name : null;
            }

            if (!isset($column['filter'])) {
                $column['filter'] = null;
            }

            if ($column['filter'] === 'bool') {
                $column['filter'] = [
                    0 => '🔴 No',
                    1 => '🟢 Yes',
                ];
            }

            if (!isset($column['filterPath'])) {
                $column['filterPath'] = $name;
            }

            if (isset($column['value']) && !is_array($column['value'])) {
                $column['value'] = [
                    [
                        'type' => $column['type'],
                        'attrs' => $column['attrs'] ?? fn() => [],
                        'value' => $column['value']
                    ]
                ];
            }

            foreach ($column['value'] ?? [] as &$value) {
                if (!isset($value['type'])) {
                    $value['type'] = 'string';
                }
            }
        }

        return $columns;
    }

}