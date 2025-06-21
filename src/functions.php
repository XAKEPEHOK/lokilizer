<?php
/**
 * Created for ploito-core
 * Datetime: 24.10.2018 18:27
 * @author Timur Kasumov aka XAKEPEHOK
 */

/**
 * Аналог empty(), но с корректной проверкой кустых строк (строка "0" не считается пустой)
 * @param $value
 * @return bool
 */
function is_empty($value): bool {

    if (is_string($value)) {
        return $value === '';
    }

    if (is_array($value) && $value === [0]) {
        return false;
    }

    return empty($value);
}

/**
 * Возвращает первое совпадение по регулярному выражению или null если совпадений нет
 * @param string $pattern
 * @param string $subject
 * @return null|string
 */
function preg_match_one(string $pattern, string $subject): ?string {
    $matches = [];

    if (preg_match($pattern, $subject, $matches)) {
        return $matches[1];
    }

    return null;
}

/**
 * Функция принимает массив ключей и одно значение, а на выходе отдает массив с указанными ключами и $value в качестве значения
 * @param array $keys
 * @param $value
 * @return array
 */
function array_combine_one(array $keys, $value): array
{
    $result = [];
    foreach ($keys as $key) {
        $result[$key] = $value;
    }
    return $result;
}

function array_extract(&$array, string|int $key): mixed {
    if (array_key_exists($key, $array)) {
        $value = $array[$key];
        unset($array[$key]);
        return $value;
    }
    return null;
}

/**
 * Преобразует массив массивов так, что один из ключей вложенного массива становится
 * его ключом в родительском массиве, удаляя при этом значение с ключом из вложенного
 *
 * @param array $data массив массивов
 * @param string $key имя ключа, которое должно стать ключом родительского массива
 * @return array
 */
function array_field_as_key(array $data, string $key): array
{
    $result = [];
    foreach ($data as $array) {
        $arrayKey = $array[$key];
        unset($array[$key]);
        $result[$arrayKey] = $array;
    }

    return $result;
}

/**
 * Преобразует массив массивов так, что ключ каждого массива становится одним из его значений с заданным ключом
 * @param array $dataArray
 * @param string $field
 * @return array
 */
function array_key_as_field(array $dataArray, string $field): array
{
    $result = [];
    foreach ($dataArray as $key => $data) {
        $data[$field] = $key;
        $result[] = $data;
    }
    return $result;
}

/**
 * @param array|ArrayAccess $data
 * @param string $keyName
 * @param string $valueName
 * @return array
 */
function array_assoc_to_flat($data, string $keyName, string $valueName)
{
    $result = [];
    foreach ($data as $key => $value) {
        $result[] = [
            $keyName => $key,
            $valueName => $value,
        ];
    }
    return $result;
}

/**
 * @param float[] $array
 * @param int|null $roundPrecision
 * @return float|null
 */
function array_median(array $array, int $roundPrecision = null): ?float
{
    $data = array_filter(array_values($array), fn($value) => !is_null($value));
    $count = count($data); //total numbers in array

    if ($count === 0) {
        return null;
    }

    if ($count === 1) {
        $result = current($data);
        return is_null($roundPrecision) ? $result : round($result, $roundPrecision);
    }

    sort($data);

    $middle = floor(($count - 1) / 2); // find the middle value, or the lowest middle value
    if ($count % 2) { // odd number, middle is the median
        $median = $data[$middle];
    } else { // even number, calculate avg of 2 medians
        $low = $data[$middle];
        $high = $data[$middle + 1];
        $median = (($low + $high) / 2);
    }

    return is_null($roundPrecision) ? $median : round($median, $roundPrecision);
}

function array_avg(array $array, int $roundPrecision = null): ?float {
    $data = array_filter(array_values($array), fn($value) => !is_null($value));
    $count = count($data);
    if ($count === 0) {
        return null;
    }
    $result = array_sum($data) / $count;
    return is_null($roundPrecision) ? $result : round($result, $roundPrecision);
}

function array_median_avg(array $array, int $roundPrecision = null): ?float {
    if (count($array) === 0) {
        return null;
    }
    $result = (array_median($array) + array_avg($array)) / 2;
    return is_null($roundPrecision) ? $result : round($result, $roundPrecision);
}

function array_standard_deviation(array $numbers): float
{
    return sqrt(array_reduce($numbers, function($prev, $item){
        return $prev + pow($item, 2);
    }, 0) / count($numbers));
}

/**
 * Возвращает строку подстроку из строки после последнего разделителя без самого разделителя
 * @param string $string
 * @param string $delimiter
 * @return null|string
 */
function explodeLast(string $string, string $delimiter): ?string {
    return substr(strrchr($string, $delimiter), 1);
}

/**
 * @param $objectOrName
 * @return string
 */
function get_namespace($objectOrName): string {

    if (is_object($objectOrName)) {
        $class = get_class($objectOrName);
    } else {
        $class = (string) $objectOrName;
    }

    return substr($class, 0, strrpos($class, '\\') + 1);
}

function isBinary(?string $string) {
    if ($string === null) {
        return false;
    }
    return preg_match('~[^\x20-\x7E\t\r\n]~', $string) > 0;
}

function get_percent_value($total, $value): float {
    $percent = $total / 100;
    return round($value / $percent, 2);
}

function convertRangeToRange(
    float $value,
    float $oldStart,
    float $oldEnd,
    float $newStart,
    float $newEnd
): float {
    $scale = ($newEnd - $newStart) / ($oldEnd - $oldStart);
    return $newStart + ($value - $oldStart) * $scale;
}