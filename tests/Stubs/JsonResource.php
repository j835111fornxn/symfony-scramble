<?php

namespace Illuminate\Http\Resources\Json;

/**
 * Stub class for Laravel's JsonResource to allow tests to load.
 * Tests using this class should be skipped or migrated to Symfony equivalents.
 *
 * This is a temporary compatibility layer during the Laravel to Symfony migration.
 */
abstract class JsonResource
{
    protected $resource;

    public function __construct($resource)
    {
        $this->resource = $resource;
    }

    public static function make($resource)
    {
        return new static($resource);
    }

    public static function collection($resource)
    {
        return collect($resource)->map(fn ($item) => new static($item));
    }

    public function __get($name)
    {
        return $this->resource->$name ?? null;
    }

    protected function when($condition, $value)
    {
        return $condition ? $value : null;
    }

    protected function whenNotNull($value)
    {
        return $value !== null ? $value : null;
    }

    protected function whenLoaded($relationship, $value = null)
    {
        return $value;
    }

    protected function whenCounted($relationship, $value = null)
    {
        return $value;
    }

    protected function merge($value)
    {
        return $value;
    }

    protected function mergeWhen($condition, $value)
    {
        return $condition ? ($value instanceof \Closure ? $value() : $value) : [];
    }

    abstract public function toArray($request);
}
