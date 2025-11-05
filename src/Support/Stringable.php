<?php

namespace Dedoc\Scramble\Support;

/**
 * Stringable class for fluent string manipulation.
 * Provides chainable methods similar to Laravel's Str::of() result.
 */
class Stringable
{
    protected string $value;

    public function __construct(string $value = '')
    {
        $this->value = $value;
    }

    /**
     * Return the string value.
     */
    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Get the string value.
     */
    public function toString(): string
    {
        return $this->value;
    }

    /**
     * Convert the string to lowercase.
     */
    public function lower(): static
    {
        $this->value = Str::lower($this->value);

        return $this;
    }

    /**
     * Convert the string to uppercase.
     */
    public function upper(): static
    {
        $this->value = Str::upper($this->value);

        return $this;
    }

    /**
     * Convert the string to snake_case.
     */
    public function snake(string $delimiter = '_'): static
    {
        $this->value = Str::snake($this->value, $delimiter);

        return $this;
    }

    /**
     * Convert the string to camelCase.
     */
    public function camel(): static
    {
        $this->value = Str::camel($this->value);

        return $this;
    }

    /**
     * Convert the string to StudlyCase.
     */
    public function studly(): static
    {
        $this->value = Str::studly($this->value);

        return $this;
    }

    /**
     * Convert the string to kebab-case.
     */
    public function kebab(): static
    {
        $this->value = Str::kebab($this->value);

        return $this;
    }

    /**
     * Replace text within the string.
     */
    public function replace(string|array $search, string|array $replace): static
    {
        $this->value = Str::replace($search, $replace, $this->value);

        return $this;
    }

    /**
     * Replace the first occurrence of a given value in the string.
     */
    public function replaceFirst(string $search, string $replace): static
    {
        $this->value = Str::replaceFirst($search, $replace, $this->value);

        return $this;
    }

    /**
     * Replace the last occurrence of a given value in the string.
     */
    public function replaceLast(string $search, string $replace): static
    {
        $this->value = Str::replaceLast($search, $replace, $this->value);

        return $this;
    }

    /**
     * Replace the given value at the start of the string.
     */
    public function replaceStart(string $search, string $replace): static
    {
        if (str_starts_with($this->value, $search)) {
            $this->value = $replace . substr($this->value, strlen($search));
        }

        return $this;
    }

    /**
     * Replace the given value at the end of the string.
     */
    public function replaceEnd(string $search, string $replace): static
    {
        if (str_ends_with($this->value, $search)) {
            $this->value = substr($this->value, 0, -strlen($search)) . $replace;
        }

        return $this;
    }

    /**
     * Cap a string with a single instance of a given value.
     */
    public function start(string $cap): static
    {
        $quoted = preg_quote($cap, '/');

        $this->value = $cap . preg_replace('/^(?:' . $quoted . ')+/u', '', $this->value);

        return $this;
    }

    /**
     * Cap a string with a single instance of a given value.
     */
    public function finish(string $cap): static
    {
        $quoted = preg_quote($cap, '/');

        $this->value = preg_replace('/(?:' . $quoted . ')+$/u', '', $this->value) . $cap;

        return $this;
    }

    /**
     * Limit the number of characters in a string.
     */
    public function limit(int $limit = 100, string $end = '...'): static
    {
        $this->value = Str::limit($this->value, $limit, $end);

        return $this;
    }

    /**
     * Determine if the string starts with a given substring.
     */
    public function startsWith(string|array $needles): bool
    {
        return Str::startsWith($this->value, $needles);
    }

    /**
     * Determine if the string ends with a given substring.
     */
    public function endsWith(string|array $needles): bool
    {
        return Str::endsWith($this->value, $needles);
    }

    /**
     * Determine if the string contains a given substring.
     */
    public function contains(string|array $needles, bool $ignoreCase = false): bool
    {
        return Str::contains($this->value, $needles, $ignoreCase);
    }

    /**
     * Get all of the pattern matches in the string.
     */
    public function matchAll(string $pattern): Collection
    {
        preg_match_all($pattern, $this->value, $matches);

        if (empty($matches[0])) {
            return collect([]);
        }

        return collect($matches[1] ?? $matches[0]);
    }

    /**
     * Trim the string of the given characters.
     */
    public function trim(string $characters = " \t\n\r\0\x0B"): static
    {
        $this->value = trim($this->value, $characters);

        return $this;
    }

    /**
     * Left trim the string of the given characters.
     */
    public function ltrim(string $characters = " \t\n\r\0\x0B"): static
    {
        $this->value = ltrim($this->value, $characters);

        return $this;
    }

    /**
     * Right trim the string of the given characters.
     */
    public function rtrim(string $characters = " \t\n\r\0\x0B"): static
    {
        $this->value = rtrim($this->value, $characters);

        return $this;
    }

    /**
     * Return the remainder of a string after the first occurrence of a given value.
     */
    public function after(string $search): static
    {
        $this->value = Str::after($this->value, $search);

        return $this;
    }

    /**
     * Return the remainder of a string after the last occurrence of a given value.
     */
    public function afterLast(string $search): static
    {
        $this->value = Str::afterLast($this->value, $search);

        return $this;
    }

    /**
     * Get the portion of a string before the first occurrence of a given value.
     */
    public function before(string $search): static
    {
        $this->value = Str::before($this->value, $search);

        return $this;
    }

    /**
     * Get the portion of a string before the last occurrence of a given value.
     */
    public function beforeLast(string $search): static
    {
        $this->value = Str::beforeLast($this->value, $search);

        return $this;
    }

    /**
     * Append the given values to the string.
     */
    public function append(string ...$values): static
    {
        $this->value .= implode('', $values);

        return $this;
    }

    /**
     * Prepend the given values to the string.
     */
    public function prepend(string ...$values): static
    {
        $this->value = implode('', $values) . $this->value;

        return $this;
    }
}
