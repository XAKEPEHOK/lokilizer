<?php
/**
 * Created for sr-app
 * Date: 2025-01-17 01:28
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Tools;

use League\Plates\Engine;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RenderAction;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Models\Localization\Db\RecordRepo;
use XAKEPEHOK\Lokilizer\Models\Localization\Record;
use Yiisoft\Arrays\ArrayHelper;

class GroupsAction extends RenderAction
{

    const SORT_BY_COUNT = 'Count';
    const SORT_BY_KEY_NAME = 'Key name';

    public function __construct(
        Engine             $renderer,
        private RecordRepo $repo
    )
    {
        parent::__construct($renderer);
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $importantContext = boolval(intval($request->getQueryParam('importantContext', 0)));
        $params = [
            'importantContext' => $importantContext,
            'withOutdated' => boolval(intval($request->getQueryParam('withOutdated', 0))),
            'sortBy' => $request->getQueryParam('sortBy', self::SORT_BY_COUNT),
        ];

        /** @var Record[] $models */
        $models = ArrayHelper::index(
            $this->repo->findAll(withOutdated: $params['withOutdated']),
            fn(Record $record) => $record->getKey()
        );

        $groups = [];
        if ($importantContext) {
            foreach ($models as $key => $model) {
                $parts = explode('.', $key);
                for ($i = 0; $i < count($parts); $i++) {
                    $prefix = implode('.', array_slice($parts, 0, $i + 1));
                    $groups[$prefix][$model->id()->get()] = $model;
                }
            }

            $groups = array_filter(
                $groups,
                fn(array $models) => count($models) < 100
            );

            do {
                $median = array_median(array_map('count', $groups));
                $groups = array_filter(
                    $groups,
                    fn(array $models) => count($models) > $median
                );
            } while (count($groups) > 0 && min(array_map('count', $groups)) < 3);

            $groupsKeys = array_keys($groups);

            /** @var array<string, Record[]> $groups */
            $groups = array_filter(
                $groups,
                function (string $key) use ($groupsKeys) {
                    foreach ($groupsKeys as $groupsKey) {
                        if ($key === $groupsKey) {
                            continue;
                        }
                        if (str_starts_with($key, $groupsKey)) {
                            return false;
                        }
                    }
                    return true;
                },
                ARRAY_FILTER_USE_KEY
            );

//            $result = [];
//            foreach ($groups as $key => $group) {
//                $result[$key] = implode('; ', array_map(
//                    fn(Record $record) => $record->getValue()->getStringContext(),
//                    $group
//                ));
//            }
//
//            $result = array_map(
//                function (array $cluster) {
//                    return array_combine(array_keys($cluster), array_keys($cluster));
//                },
//                $this->transformerService->clustering($result, 0.2, 1)
//            );
//
//            usort($result, function (array $a, array $b) {
//                return count($b) <=> count($a);
//            });
//            return $response->withJson($result, null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        } else {
            foreach ($models as $model) {
                $groups[$model->getParent()][] = $model;
            }

            $dangling = array_map(
                fn(array $group) => current($group),
                array_filter(
                    $groups,
                    fn(array $group) => count($group) == 1
                )
            );

            $groups = array_filter(
                $groups,
                fn(array $group) => count($group) > 1
            );
        }

        if ($params['sortBy'] === self::SORT_BY_COUNT) {
            uasort($groups, function (array $a, array $b) {
                return count($b) <=> count($a);
            });
        } else {
            ksort($groups);
        }

        return $this->render($response, 'tools/tools_groups', [
            'request' => $request,
            'form' => $params,
            'groups' => $groups,
            'dangling' => $dangling ?? [],
        ]);
    }
}