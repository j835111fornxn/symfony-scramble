<?php

namespace Dedoc\Scramble\Support\Type;

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
        if (! $type instanceof Union) {
            return false;
        }

        if (count($this->types) !== count($type->types)) {
            return false;
        }

        return collect($this->types)->every(fn (Type $t, $i) => $t->isSame($type->types[$i]));
    }

    public function widen(): Type
    {
        // Note: Widening logic moved to TypeWidener service
        // This method should be called through TypeWidener::widen($type)
        // For backward compatibility, return self
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
     * Flexible wrapper that accepts:
     * - Multiple Type arguments: wrap($type1, $type2)
     * - Single array of Types: wrap([$type1, $type2])
     * - Array unpacking: wrap(...$arrayOfTypes)
     * - array_map result: wrap(array_map(...))
     *
     * @param  Type|array<Type>  $types
     */
    public static function wrap(Type|array $types, Type ...$moreTypes): Type
    {
        // If first arg is a Type object and we have more args, collect them all
        if ($types instanceof Type) {
            $types = [$types, ...$moreTypes];
        }
        // If first arg is already an array, use it (array_map pattern, wrap([...]), etc.)
        // $types is already an array of Type objects

        $types = collect(array_values($types))
            ->unique(fn (Type $t) => $t->toString())
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
        return implode('|', array_map(fn ($t) => $t->toString(), $this->types));
    }
}
