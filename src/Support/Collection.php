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
            if (! $callback($item, $key)) {
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

    public function last(?callable $callback = null, $default = null)
    {
        if ($callback === null) {
            if (empty($this->items)) {
                return $default;
            }
            $items = $this->items;

            return end($items);
        }

        $items = array_reverse($this->items, true);
        foreach ($items as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return $default;
    }

    public function push(...$values): self
    {
        foreach ($values as $value) {
            $this->items[] = $value;
        }

        return $this;
    }

    public function where($key, $operator = null, $value = null): self
    {
        return $this->filter(function ($item) use ($key, $operator, $value) {
            $retrieved = is_array($item) ? ($item[$key] ?? null) : ($item->$key ?? null);

            if (func_num_args() === 2) {
                return $retrieved == $operator;
            }

            return match ($operator) {
                '=' => $retrieved == $value,
                '==' => $retrieved == $value,
                '===' => $retrieved === $value,
                '!=' => $retrieved != $value,
                '!==' => $retrieved !== $value,
                '>' => $retrieved > $value,
                '>=' => $retrieved >= $value,
                '<' => $retrieved < $value,
                '<=' => $retrieved <= $value,
                default => false,
            };
        });
    }

    public function tap(callable $callback): self
    {
        $callback($this);

        return $this;
    }

    public function partition($key, $operator = null, $value = null): self
    {
        $passed = [];
        $failed = [];

        $callback = is_callable($key) ? $key : function ($item) use ($key, $operator, $value) {
            $retrieved = is_array($item) ? ($item[$key] ?? null) : ($item->$key ?? null);

            if (func_num_args() === 2) {
                return $retrieved == $operator;
            }

            return match ($operator) {
                '=' => $retrieved == $value,
                '==' => $retrieved == $value,
                '===' => $retrieved === $value,
                '!=' => $retrieved != $value,
                '!==' => $retrieved !== $value,
                '>' => $retrieved > $value,
                '>=' => $retrieved >= $value,
                '<' => $retrieved < $value,
                '<=' => $retrieved <= $value,
                default => false,
            };
        };

        foreach ($this->items as $key => $item) {
            if ($callback($item, $key)) {
                $passed[$key] = $item;
            } else {
                $failed[$key] = $item;
            }
        }

        return new static([new static($passed), new static($failed)]);
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

            if (! isset($results[$groupKey])) {
                $results[$groupKey] = new static;
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
            : fn ($item) => is_array($item) ? ($item[$callback] ?? null) : ($item->$callback ?? null);

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

            if (! in_array($id, $exists, $strict)) {
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
            if (! is_array($item) && ! ($item instanceof self)) {
                $result[] = $item;
            } elseif ($depth === 1) {
                $result = array_merge($result, is_array($item) ? $item : $item->all());
            } else {
                $result = array_merge($result, (new static($item))->flatten($depth - 1)->all());
            }
        }

        return new static($result);
    }

    public function flatMap(callable $callback): self
    {
        return $this->map($callback)->flatten(1);
    }

    public function reduce(callable $callback, $initial = null)
    {
        $result = $initial;

        foreach ($this->items as $key => $value) {
            $result = $callback($result, $value, $key);
        }

        return $result;
    }

    public function prepend($value, $key = null): self
    {
        if ($key === null) {
            array_unshift($this->items, $value);
        } else {
            $this->items = [$key => $value] + $this->items;
        }

        return new static($this->items);
    }

    public function get($key, $default = null)
    {
        if (array_key_exists($key, $this->items)) {
            return $this->items[$key];
        }

        return $default;
    }

    public function has($key): bool
    {
        return array_key_exists($key, $this->items);
    }

    public function concat($source): self
    {
        $result = new static($this);

        foreach ($source as $item) {
            $result->items[] = $item;
        }

        return $result;
    }

    public function merge($items): self
    {
        return new static(array_merge($this->items, $this->getArrayableItems($items)));
    }

    public function sortKeys($descending = false): self
    {
        $items = $this->items;
        $descending ? krsort($items) : ksort($items);

        return new static($items);
    }

    public function only($keys): self
    {
        if (is_null($keys)) {
            return new static($this->items);
        }

        if ($keys instanceof self) {
            $keys = $keys->all();
        }

        $keys = is_array($keys) ? $keys : func_get_args();

        return new static(array_intersect_key($this->items, array_flip($keys)));
    }

    public function values(): self
    {
        return new static(array_values($this->items));
    }

    public function keys(): self
    {
        return new static(array_keys($this->items));
    }

    public function keyBy($keyBy): self
    {
        $results = [];

        foreach ($this->items as $key => $item) {
            $resolvedKey = is_callable($keyBy)
                ? $keyBy($item, $key)
                : (is_array($item) ? ($item[$keyBy] ?? null) : ($item->$keyBy ?? null));

            $results[$resolvedKey] = $item;
        }

        return new static($results);
    }

    public function union($items): self
    {
        return new static($this->items + $this->getArrayableItems($items));
    }

    public function contains($key, $operator = null, $value = null): bool
    {
        if (func_num_args() === 1 && is_callable($key)) {
            $placeholder = new \stdClass;

            return $this->first($key, $placeholder) !== $placeholder;
        }

        if (func_num_args() === 1) {
            return in_array($key, $this->items, true);
        }

        return $this->contains(function ($item) use ($key, $operator, $value) {
            $retrieved = is_array($item) ? ($item[$key] ?? null) : ($item->$key ?? null);

            $strings = array_filter([$retrieved, $value], fn ($val) => is_string($val) || (is_object($val) && method_exists($val, '__toString')));

            if (count($strings) < 2 && count(array_filter([$retrieved, $value], 'is_object')) === 1) {
                return in_array($operator, ['!=', '<>', '!=='], true);
            }

            return match ($operator ?? '=') {
                '=' => $retrieved == $value,
                '==' => $retrieved == $value,
                '===' => $retrieved === $value,
                '!=' => $retrieved != $value,
                '!==' => $retrieved !== $value,
                '>' => $retrieved > $value,
                '>=' => $retrieved >= $value,
                '<' => $retrieved < $value,
                '<=' => $retrieved <= $value,
                default => false,
            };
        });
    }

    public function containsStrict($key, $value = null): bool
    {
        if (func_num_args() === 1) {
            return in_array($key, $this->items, true);
        }

        return $this->contains(function ($item) use ($key, $value) {
            $retrieved = is_array($item) ? ($item[$key] ?? null) : ($item->$key ?? null);

            return $retrieved === $value;
        });
    }

    public function reject($callback = true): self
    {
        $useAsCallable = $this->useAsCallable($callback);

        return $this->filter(function ($value, $key) use ($callback, $useAsCallable) {
            return $useAsCallable
                ? ! $callback($value, $key)
                : $value != $callback;
        });
    }

    protected function useAsCallable($value): bool
    {
        return ! is_string($value) && is_callable($value);
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
        return ! $this->isEmpty();
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

        return implode($glue, $items).$finalGlue.$final;
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
        return $this->map(fn ($value) => $value instanceof self ? $value->toArray() : $value)->all();
    }

    public function __toString(): string
    {
        return json_encode($this->toArray());
    }

    /**
     * Dynamically access collection proxies (higher order messages).
     *
     * @param  string  $key
     * @return HigherOrderCollectionProxy
     */
    public function __get($key)
    {
        if (! in_array($key, ['filter', 'map', 'pluck', 'reject', 'sortBy', 'sortByDesc', 'unique'], true)) {
            throw new \Exception("Property [{$key}] does not exist on this collection instance.");
        }

        return new HigherOrderCollectionProxy($this, $key);
    }
}
