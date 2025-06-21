<?php
/**
 * Created for sr-app
 * Date: 29.07.2024 18:56
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Components\Collection;

use ArrayAccess;
use Countable;
use Iterator;
use JsonSerializable;
use RuntimeException;

class Collection implements ArrayAccess, Iterator, Countable, JsonSerializable
{

    protected bool $isReadonly = false;
    protected array $collection = [];

    public function __construct(array $data = [])
    {
        $this->collection = $data;
    }

    // ArrayAccess methods
    public function offsetSet($offset, $value): void
    {
        if ($this->isReadonly) {
            throw new RuntimeException('Collection is read-only');
        }
        if (is_null($offset)) {
            $this->collection[] = $value;
        } else {
            $this->collection[$offset] = $value;
        }
    }

    public function offsetExists($offset): bool
    {
        return isset($this->collection[$offset]);
    }

    public function offsetUnset($offset): void
    {
        if ($this->isReadonly) {
            throw new RuntimeException('Collection is read-only');
        }
        unset($this->collection[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return $this->collection[$offset] ?? null;
    }

    // Iterator methods
    public function current(): mixed
    {
        return current($this->collection);
    }

    public function key(): mixed
    {
        return key($this->collection);
    }

    public function next(): void
    {
        next($this->collection);
    }

    public function rewind(): void
    {
        reset($this->collection);
    }

    public function valid(): bool
    {
        return key($this->collection) !== null;
    }

    // Countable method
    public function count(): int
    {
        return count($this->collection);
    }

    public function jsonSerialize(): array
    {
        return array_values($this->collection);
    }
}