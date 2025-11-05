<?php

namespace Dedoc\Scramble\Support;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * A lightweight collection class to replace Laravel's Collection.
 * Implements only the methods used in this project.
 */
class Collection implements ArrayAccess, Countable, IteratorAggregate
{
    protected array $items = [];

    public function __construct($items = [])
    {
        $this->items = $this->getArrayableItems($items);
    }

    protected function getArrayableItems($items): array
    {
        if (is_array($items)) {
            return $items;
        }

        if ($items instanceof self) {
            return $items->all();
        }

        if ($items instanceof Traversable) {
            return iterator_to_array($items);
        }

        return (array) $items;
    }

    public function all(): array
    {
        return $this->items;
    }

    public function filter(?callable $callback = null): self
    {
        if ($callback === null) {
            return new static(array_filter($this->items));
        }

        return new static(array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH));
    }

    public function map(callable $callback): self
    {
        return new static(array_map($callback, $this->items, array_keys($this->items)));
    }

    public function mapWithKeys(callable $callback): self
    {
        $result = [];
        foreach ($this->items as $key => $value) {
            $assoc = $callback($value, $key);
            foreach ($assoc as $mapKey => $mapValue) {
                $result[$mapKey] = $mapValue;
            }
        }
        return new static($result);
    }

    public function each(callable $callback): self
    {
        foreach ($this->items as $key => $item) {
            if ($callback($item, $key) === false) {
                break;
            }
        }

        return $this;
    }

    public function every(callable $callback): bool
    {
        foreach ($this->items as $key => $item) {
            if (!$callback($item, $key)) {
                return false;
            }
        }

        return true;
    }

    public function some(callable $callback): bool
    {
        foreach ($this->items as $key => $item) {
            if ($callback($item, $key)) {
                return true;
            }
        }

        return false;
    }

    public function first(?callable $callback = null, $default = null)
    {
        if ($callback === null) {
            if (empty($this->items)) {
                return $default;
            }
            return reset($this->items);
        }

        foreach ($this->items as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return $default;
    }

    public function firstWhere(string $key, $operator = null, $value = null)
    {
        return $this->first(function ($item) use ($key, $operator, $value) {
            $itemValue = is_array($item) ? ($item[$key] ?? null) : ($item->$key ?? null);
            
            if (func_num_args() === 2) {
                return $itemValue == $operator;
            }

            return match ($operator) {
                '=' => $itemValue == $value,
                '==' => $itemValue == $value,
                '===' => $itemValue === $value,
                '!=' => $itemValue != $value,
                '!==' => $itemValue !== $value,
                '>' => $itemValue > $value,
                '>=' => $itemValue >= $value,
                '<' => $itemValue < $value,
                '<=' => $itemValue <= $value,
                default => false,
            };
        });
    }

    public function pluck(string $value, ?string $key = null): self
    {
        $results = [];

        foreach ($this->items as $item) {
            $itemValue = is_array($item) ? ($item[$value] ?? null) : ($item->$value ?? null);

            if ($key === null) {
                $results[] = $itemValue;
            } else {
                $itemKey = is_array($item) ? ($item[$key] ?? null) : ($item->$key ?? null);
                $results[$itemKey] = $itemValue;
            }
        }

        return new static($results);
    }

    public function groupBy($groupBy, bool $preserveKeys = false): self
    {
        $results = [];

        foreach ($this->items as $key => $value) {
            $groupKey = is_callable($groupBy) 
                ? $groupBy($value, $key)
                : (is_array($value) ? ($value[$groupBy] ?? null) : ($value->$groupBy ?? null));

            if (!isset($results[$groupKey])) {
                $results[$groupKey] = new static();
            }

            if ($preserveKeys) {
                $results[$groupKey]->items[$key] = $value;
            } else {
                $results[$groupKey]->items[] = $value;
            }
        }

        return new static($results);
    }

    public function sortBy($callback, int $options = SORT_REGULAR, bool $descending = false): self
    {
        $results = $this->items;

        $callback = is_callable($callback) 
            ? $callback 
            : fn($item) => is_array($item) ? ($item[$callback] ?? null) : ($item->$callback ?? null);

        uasort($results, function ($a, $b) use ($callback, $descending) {
            $aValue = $callback($a);
            $bValue = $callback($b);

            $result = $aValue <=> $bValue;

            return $descending ? -$result : $result;
        });

        return new static($results);
    }

    public function unique($key = null, bool $strict = false): self
    {
        if ($key === null) {
            return new static(array_unique($this->items, SORT_REGULAR));
        }

        $exists = [];
        $results = [];

        foreach ($this->items as $item) {
            $id = is_callable($key)
                ? $key($item)
                : (is_array($item) ? ($item[$key] ?? null) : ($item->$key ?? null));

            if (!in_array($id, $exists, $strict)) {
                $exists[] = $id;
                $results[] = $item;
            }
        }

        return new static($results);
    }

    public function flatten(int $depth = INF): self
    {
        $result = [];

        foreach ($this->items as $item) {
            if (!is_array($item) && !($item instanceof self)) {
                $result[] = $item;
            } elseif ($depth === 1) {
                $result = array_merge($result, is_array($item) ? $item : $item->all());
            } else {
                $result = array_merge($result, (new static($item))->flatten($depth - 1)->all());
            }
        }

        return new static($result);
    }

    public function values(): self
    {
        return new static(array_values($this->items));
    }

    public function keys(): self
    {
        return new static(array_keys($this->items));
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    public function pipe(callable $callback)
    {
        return $callback($this);
    }

    public function join(string $glue, string $finalGlue = ''): string
    {
        if ($finalGlue === '') {
            return implode($glue, $this->items);
        }

        if ($this->count() === 0) {
            return '';
        }

        if ($this->count() === 1) {
            return (string) $this->items[0];
        }

        $items = $this->all();
        $final = array_pop($items);

        return implode($glue, $items) . $finalGlue . $final;
    }

    // ArrayAccess implementation
    public function offsetExists($key): bool
    {
        return array_key_exists($key, $this->items);
    }

    public function offsetGet($key): mixed
    {
        return $this->items[$key];
    }

    public function offsetSet($key, $value): void
    {
        if ($key === null) {
            $this->items[] = $value;
        } else {
            $this->items[$key] = $value;
        }
    }

    public function offsetUnset($key): void
    {
        unset($this->items[$key]);
    }

    // IteratorAggregate implementation
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    public function toArray(): array
    {
        return $this->map(fn($value) => $value instanceof self ? $value->toArray() : $value)->all();
    }

    public function __toString(): string
    {
        return json_encode($this->toArray());
    }
}
