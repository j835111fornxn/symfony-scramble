<?php

namespace Dedoc\Scramble\Support\Type;

use Dedoc\Scramble\Support\Arr;

class Union extends AbstractType
{
    /**
     * @param  Type[]  $types
     */
    public function __construct(public array $types) {}

    public function nodes(): array
    {
        return ['types'];
    }

    public function isSame(Type $type)
    {
        if (!$type instanceof Union) {
            return false;
        }

        if (count($this->types) !== count($type->types)) {
            return false;
        }

        return collect($this->types)->every(fn(Type $t, $i) => $t->isSame($type->types[$i]));
    }

    public function widen(): Type
    {
        // TypeWidener service should be injected or accessed through service locator
        // For now, return self as widening requires context
        // TODO: Refactor to use proper DI when removing app() helpers
        return $this;
    }

    public function accepts(Type $otherType): bool
    {
        foreach ($this->types as $type) {
            if ($type->accepts($otherType)) {
                return true;
            }
        }

        return false;
    }

    public function acceptedBy(Type $otherType): bool
    {
        foreach ($this->types as $type) {
            if (! $type->acceptedBy($otherType)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Create a Union type from the given types.
     * 
     * Accepts either:
     * - Multiple Type arguments: wrap($type1, $type2)
     * - Single array of Types: wrap([$type1, $type2])
     * - Array unpacking: wrap(...$arrayOfTypes)
     * 
     * @param Type|Type[] $types First argument (Type or array of Types)
     * @param Type ...$moreTypes Additional Type arguments (if first arg was Type)
     * @return Type
     */
    public static function wrap($types, Type ...$moreTypes): Type
    {
        // If first arg is a Type object and we have more args, collect them all
        if ($types instanceof Type) {
            $types = [$types, ...$moreTypes];
        }
        // If first arg is already an array, use it (array_map pattern)
        elseif (is_array($types)) {
            // $types is already an array of Type objects
        }
        // Otherwise invalid input
        else {
            throw new \InvalidArgumentException('First argument must be Type or array of Type objects');
        }

        $types = collect(array_values($types))
            ->unique(fn(Type $t) => $t->toString())
            ->values()
            ->all();

        if (! count($types)) {
            return new VoidType;
        }

        if (count($types) === 1) {
            return $types[0];
        }

        return new self($types);
    }

    public function toString(): string
    {
        return implode('|', array_map(fn($t) => $t->toString(), $this->types));
    }
}
