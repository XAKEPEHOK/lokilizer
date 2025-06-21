<?php
/**
 * Created for lokilizer
 * Date: 2025-02-22 16:04
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Actions\Tools;

use League\Plates\Engine;
use PrinsFrank\Standards\Language\LanguageAlpha2;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RenderAction;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Models\Localization\Db\RecordRepo;
use XAKEPEHOK\Lokilizer\Models\Localization\Record;
use XAKEPEHOK\Lokilizer\Services\TransformerService;

class KeysAction extends RenderAction
{

    public function __construct(
        private RecordRepo $recordRepo,
        private TransformerService $transformerService,
        private CacheInterface $cache,
        Engine             $renderer
    )
    {
        parent::__construct($renderer);
    }

    public function __invoke(Request $request, Response $response): Response|ResponseInterface
    {
        $language = LanguageAlpha2::Russian;
        $min = 4;

        /** @var RedisAdapter $cache */
        $cachePrefix = Current::getProject()->id() . '_' . $language->value . '_' . $min . '';

        $sentences = $this->cache->get("{$cachePrefix}_sentences", function (ItemInterface $item) use ($language) {
            $item->expiresAfter(60 * 60 * 24);
            $values = array_map(
                fn(Record $record): string => implode(' ', $this->extractWords($record->getValue($language)?->getStringContext() ?? '')),
                $this->recordRepo->findAll(false)
            );

            $words = [];
            foreach ($values as $value) {
                $extracted = $this->splitArrayByShortWords($this->extractWords($value));

                if (empty($extracted)) {
                    continue;
                }

                $words = array_merge($words, $extracted);
            }
            return $words;
        });


        $phrasesVectors = $this->cache->get("{$cachePrefix}_vectors_phrase", function (ItemInterface $item) use ($sentences) {
            $item->expiresAfter(60 * 60 * 24);
            $phrases = [];
            $words = [];

            foreach ($sentences as $sentence) {
                if (count($sentence) < 2) {
                    continue;
                }

                foreach ($sentence as $word) {
                    $words[$word] = $word;
                }

                $wordPairs = array_map(
                    fn(array $wordsArray) => implode(' ', $wordsArray),
                    $this->getConsecutivePairs(...array_values($sentence))
                );

                foreach ($wordPairs as $phrase) {
                    $phrases[$phrase] = $phrase;
                }
            }

            $phrasesVectors = $this->transformerService->vectorize($phrases);
            $wordsVectors = $this->transformerService->vectorize($words);

            $result = [];
            foreach ($phrasesVectors as $phrase => $vector) {
                list($word_1, $word_2) = explode(' ', $phrase);
                $result[$phrase] = [...$vector, ...$wordsVectors[$word_1], ...$wordsVectors[$word_2]];
            }

            return $result;
        });

        $phrasesSteps = [];
        for ($i = 0; $i < 4; $i++) {
            $cacheKey = "{$cachePrefix}_phrases_{$i}";
//            $this->cache->delete($cacheKey);
            $phrasesSteps[$i] = $this->cache->get($cacheKey, function (ItemInterface $item) use ($phrasesVectors, &$phrasesSteps, $i, $min) {
                $item->expiresAfter(60 * 60 * 24);

                $flat = [];
                for ($j = 0; $j < $i; $j++) {
                    $prev = $phrasesSteps[$j] ?? [];
                    $flat = array_merge($flat, ...$prev);
                }

                $phrases = array_filter(
                    $phrasesVectors,
                    fn(array $vector, string $phrase) => !in_array($phrase, $flat),
                    ARRAY_FILTER_USE_BOTH
                );

                $phrases = array_map(
                    fn(array $cluster) => array_values($cluster),
                    $this->transformerService->dbscan(
                        vectors: $phrases,
                        eps: ($i + 1) / 100,
                        minSamples: $min
                    )
                );

                return array_slice($phrases, 1);
            });
        }

        $phrasesSteps = array_merge(...$phrasesSteps);

//        $this->cache->delete("{$cachePrefix}_words");
        $words = $this->cache->get("{$cachePrefix}_words", function (ItemInterface $item) use ($sentences, $phrasesSteps, $min) {
            $item->expiresAfter(60 * 60 * 24);

            $phrases = array_merge(...$phrasesSteps);

            $words = [];
            foreach ($sentences as $sentence) {
                $joined = implode(' ', $sentence);
                foreach ($phrases as $phrase) {
                    $joined = str_replace($phrase, ' ', $joined);
                }

                $sentence = explode(' ', $joined);
                foreach ($sentence as $word) {
                    if (strlen($word) === 0) {
                        continue;
                    }
                    $words[$word] = $word;
                }
            }

            return array_map(
                fn(array $cluster) => array_values($cluster),
                array_slice(array_values($this->transformerService->clustering(
                    strings: $words,
                    eps: 0.02,
                    minSamples: $min
                )), 1)
            );
        });

        return $response->withJson([...$phrasesSteps, ...$words], null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function getConsecutivePairs(string ...$words): array
    {
        $pairs = [];
        $count = count($words);

        // Если слов меньше двух, пары сформировать невозможно.
        if ($count < 2) {
            return $pairs;
        }

        for ($i = 0; $i < $count - 1; $i++) {
            // Каждая пара состоит из текущего слова и следующего.
            $pairs[] = [$words[$i], $words[$i + 1]];
        }

        return $pairs;
    }

    private function extractWords(string $text): array {
        $words = [];

        // Шаг 1: Разбиваем строку по символам, не входящим в [A-Za-z0-9]
        $tokens = preg_split('/[^\p{L}]+/ui', $text, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($tokens as $token) {
            // Шаг 2: Выделяем сегменты, состоящие либо из цифр, либо из букв
            preg_match_all('/[\p{L}]+/ui', $token, $matches);
            foreach ($matches[0] as $segment) {
                // Шаг 3: Если сегмент состоит из букв, разбиваем его по границам CamelCase
                if (ctype_alpha($segment)) {
                    // Разбивка по переходу от строчной буквы к заглавной (например, "HelloWorld" -> ["Hello", "World"])
                    $subParts = preg_split('/(?<=[\p{Ll}])(?=[\p{Lu}])/', $segment);
                    foreach ($subParts as $subPart) {
                        $words[] = mb_strtolower($subPart);
                    }
                } else {
                    // Для цифр просто добавляем их (без изменений регистра)
                    $words[] = mb_strtolower($segment);
                }
            }
        }

        return $words;
    }

    private function splitArrayByShortWords(array $words, int $length = 2): array {
        $result = [];
        $currentGroup = [];

        foreach ($words as $word) {
            if (mb_strlen($word) <= $length) {
                // Если в текущей группе есть слова, добавляем группу в результат
                if (!empty($currentGroup)) {
                    $result[] = $currentGroup;
                    $currentGroup = [];
                }
                // Пропускаем само разделительное слово
                continue;
            }
            // Добавляем слово в текущую группу
            $currentGroup[] = $word;
        }

        // Если остались слова в текущей группе, добавляем её в результат
        if (!empty($currentGroup)) {
            $result[] = $currentGroup;
        }

        return $result;
    }

    private function sumNGramFrequencies(array $nGramsFreq, string $text): int
    {
        // Если массив частот пуст, возвращаем 0
        if (empty($nGramsFreq)) {
            return 0;
        }

        // Определяем длину n-граммы по первому ключу массива
        $keys = array_keys($nGramsFreq);
        $n = mb_strlen($keys[0], 'UTF-8');

        // Приводим входную строку к нижнему регистру для регистронезависимого поиска
        $text = mb_strtolower($text, 'UTF-8');
        $textLength = mb_strlen($text, 'UTF-8');

        $sum = 0;

        // Если строка короче, чем длина n-граммы, возвращаем 0
        if ($textLength < $n) {
            return 0;
        }

        // Извлекаем все n-граммы из строки и суммируем их частоты из массива $nGramsFreq
        for ($i = 0; $i <= $textLength - $n; $i++) {
            $ngram = mb_substr($text, $i, $n, 'UTF-8');
            if (isset($nGramsFreq[$ngram])) {
                $sum += $nGramsFreq[$ngram];
            }
        }

        return $sum;
    }

    private function countNGrams(array $strings, int $n): array
    {
        $ngrams = [];

        foreach ($strings as $string) {
            // Приводим строку к нижнему регистру для регистронезависимого подсчета
            $string = mb_strtolower($string, 'UTF-8');
            $length = mb_strlen($string, 'UTF-8');

            // Если длина строки меньше n, пропускаем ее
            if ($length < $n) {
                continue;
            }

            // Проходим по строке и извлекаем все n-граммы
            for ($i = 0; $i <= $length - $n; $i++) {
                $ngram = mb_substr($string, $i, $n, 'UTF-8');
                if (array_key_exists($ngram, $ngrams)) {
                    $ngrams[$ngram]++;
                } else {
                    $ngrams[$ngram] = 1;
                }
            }
        }

        arsort($ngrams);
        return $ngrams;
    }

    private function recursiveCount(array $array, &$chains)
    {
        foreach ($array as $key => $value) {
            $chains[$key] = ($chains[$key] ?? 0) + 1;
            if (is_array($value)) {
                $this->recursiveCount($value, $chains);
            }
        }
    }
}