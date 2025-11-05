<?php

namespace Dedoc\Scramble\Support;

/**
 * Proxy for higher-order collection messages.
 * Enables syntax like: $collection->filter->method($args)
 * Which is shorthand for: $collection->filter(fn($item) => $item->method($args))
 */
class HigherOrderCollectionProxy
{
    protected Collection $collection;

    protected string $method;

    public function __construct(Collection $collection, string $method)
    {
        $this->collection = $collection;
        $this->method = $method;
    }

    public function __get(string $key)
    {
        return $this->collection->{$this->method}(function ($value) use ($key) {
            return is_array($value) ? $value[$key] : $value->{$key};
        });
    }

    public function __call(string $method, array $parameters)
    {
        return $this->collection->{$this->method}(function ($value) use ($method, $parameters) {
            return $value->{$method}(...$parameters);
        });
    }
}
