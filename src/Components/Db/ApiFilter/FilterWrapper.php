<?php
/**
 * Created for lv-app
 * Date: 01.06.2021
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Components\Db\ApiFilter;


use Adbar\Dot;
use DateTimeInterface;
use XAKEPEHOK\Lokilizer\Components\Db\ApiFilter\Range\DatetimeRange;
use XAKEPEHOK\Lokilizer\Components\Db\ApiFilter\Range\NumberRange;

final class FilterWrapper
{

    private ApiFilterInterface $filter;
    private Dot $query;

    public function __construct(ApiFilterInterface $filter, Dot $query)
    {
        $this->filter = $filter;
        $this->query = $query;
    }

    public function ids(string $field, string $valueKey, callable $handleValue = null): self
    {
        //$valueKey не имеет значения по умолчанию во избежание ошибок с названиями полей в базе и в API

        if ($this->query->has($valueKey)) {
            $value = $this->getHandledValue($valueKey, $handleValue);

            if (is_array($value)) {
                if (!empty($value)) {
                    $this->filter->ids($field, $value);
                }
            } elseif (!is_null($value)) {
                $this->filter->ids($field, [$value]);
            }

            if (is_null($value)) {
                $this->filter->empty($field, false, true);
            }
        }

        return $this;
    }

    public function equals(string $field, string $valueKey = null, callable $handleValue = null): self
    {
        $this->equality($field, $valueKey, $handleValue, true);

        return $this;
    }

    public function same(string $field, string $valueKey = null, callable $handleValue = null): self
    {
        $this->equality($field, $valueKey, $handleValue, false);
        return $this;
    }

    public function like(string $field, string $valueKey = null, callable $handleValue = null): self
    {
        if (is_null($valueKey)) {
            $valueKey = $field;
        }

        if ($this->query->has($valueKey)) {
            $value = $this->getHandledValue($valueKey, $handleValue);

            if (is_null($value) || $value === '') {
                $this->filter->empty($field, true, false);
            } else {
                $this->filter->like($field, $this->query->get($valueKey));
            }
        }

        return $this;
    }

    public function in(string $field, string $valueKey, callable $handleValue = null): self
    {
        //$valueKey не имеет значения по умолчанию во избежание ошибок с названиями полей в базе и в API

        if ($this->query->has($valueKey)) {
            $value = $this->getHandledValue($valueKey, $handleValue);

            if (is_array($value)) {
                if (!empty($value)) {
                    $this->filter->in($field, $value);
                }
            }

            if (is_null($value)) {
                $this->filter->empty($field, false, true);
            }
        }

        return $this;
    }

    public function range(string $field, string $valueKey = null, callable $handleValue = null): self
    {
        if (is_null($valueKey)) {
            $valueKey = $field;
        }

        if ($this->query->has($valueKey)) {
            $value = $this->getHandledValue($valueKey, $handleValue);

            if (is_null($value)) {
                $this->filter->empty($field, false, false);
            } else {
                $gte = $value['gte'] ?? null;
                $lte = $value['lte'] ?? null;

                $sample = is_null($gte) ? $lte : $gte;
                if (is_null($sample)) {
                    return $this;
                }

                if ($sample instanceof DateTimeInterface) {
                    $class = DatetimeRange::class;
                } elseif (is_numeric($sample)) {
                    $class = NumberRange::class;
                } else {
                    return $this;
                }

                $this->filter->range($field, new $class($gte, $lte));
            }
        }
        return $this;
    }

    public function empty(string $field, string $valueKey = null, bool $includeEmptyString, bool $includeEmptyArray): self
    {
        if (is_null($valueKey)) {
            $valueKey = $field;
        }

        if ($this->query->has($valueKey)) {
            $this->filter->empty($field, $includeEmptyString, $includeEmptyArray);
        }

        return $this;
    }

    public function getFilter(): ApiFilterInterface
    {
        return $this->filter;
    }

    public function getQuery(): Dot
    {
        return $this->query;
    }

    public function getValue(string $key)
    {
        return $this->query->get($key);
    }

    private function equality(string $field, string $valueKey = null, callable $handleValue = null, bool $includeEmptyString = false): self
    {
        if (is_null($valueKey)) {
            $valueKey = $field;
        }

        if ($this->query->has($valueKey)) {
            $value = $this->getHandledValue($valueKey, $handleValue);

            if (is_null($value) || $value === '') {
                $this->filter->empty($field, $includeEmptyString, false);
            } else {
                $this->filter->equals($field, $this->query->get($valueKey));
            }
        }

        return $this;
    }

    private function getHandledValue(string $valueKey, ?callable $handleValue)
    {
        $handleValue = $handleValue ?? fn($val) => $val;
        return $handleValue($this->query->get($valueKey));
    }

}