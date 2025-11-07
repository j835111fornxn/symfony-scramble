<?php

namespace Dedoc\Scramble\Tests;

/**
 * Stub class to replace Laravel's JsonResource for testing purposes.
 * This is a minimal implementation that provides the basic structure
 * needed for Scramble's type inference tests.
 */
abstract class JsonResource
{
    /**
     * The resource instance.
     *
     * @var mixed
     */
    public $resource;

    /**
     * Create a new resource instance.
     *
     * @param  mixed  $resource
     * @return void
     */
    public function __construct($resource)
    {
        $this->resource = $resource;
    }

    /**
     * Transform the resource into an array.
     *
     * @param  mixed  $request
     * @return array
     */
    abstract public function toArray($request);

    /**
     * Create a new resource instance.
     *
     * @param  mixed  ...$parameters
     * @return static
     */
    public static function make(...$parameters)
    {
        return new static(...$parameters);
    }

    /**
     * Create a new resource collection instance.
     *
     * @param  mixed  $resource
     * @return mixed
     */
    public static function collection($resource)
    {
        return $resource;
    }

    /**
     * Merge a value based on a given condition.
     *
     * @param  bool  $condition
     * @param  mixed  $value
     * @param  mixed  $default
     * @return mixed
     */
    public function when($condition, $value, $default = null)
    {
        if ($condition) {
            return value($value);
        }

        return func_num_args() === 3 ? value($default) : null;
    }

    /**
     * Merge a value into the array.
     *
     * @param  mixed  $value
     * @return mixed
     */
    public function merge($value)
    {
        return value($value);
    }

    /**
     * Merge a value based on a given condition.
     *
     * @param  bool  $condition
     * @param  mixed  $value
     * @return mixed
     */
    public function mergeWhen($condition, $value)
    {
        return $condition ? value($value) : [];
    }
}

if (!function_exists('value')) {
    /**
     * Return the default value of the given value.
     *
     * @param  mixed  $value
     * @return mixed
     */
    function value($value)
    {
        return $value instanceof \Closure ? $value() : $value;
    }
}
