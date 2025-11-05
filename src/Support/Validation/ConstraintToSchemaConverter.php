<?php

namespace Dedoc\Scramble\Support\Validation;

use Dedoc\Scramble\Support\Generator\Types\ArrayType;
use Dedoc\Scramble\Support\Generator\Types\NumberType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\Generator\Types\Type;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints;

/**
 * Converts Symfony Validator constraints to OpenAPI schema properties.
 *
 * Maps validation constraints to schema attributes like min/max length,
 * format, pattern, enum values, etc.
 */
class ConstraintToSchemaConverter
{
    /**
     * Apply an array of constraints to a Type object.
     *
     * @param  Constraint[]  $constraints
     */
    public function applyConstraints(array $constraints, Type $type, string $propertyName): void
    {
        foreach ($constraints as $constraint) {
            $this->applyConstraint($constraint, $type, $propertyName);
        }
    }

    /**
     * Apply a single constraint to a Type object.
     */
    private function applyConstraint(Constraint $constraint, Type $type, string $propertyName): void
    {
        match (true) {
            $constraint instanceof Constraints\Length => $this->applyLength($constraint, $type),
            $constraint instanceof Constraints\Range => $this->applyRange($constraint, $type),
            $constraint instanceof Constraints\Email => $this->applyEmail($constraint, $type),
            $constraint instanceof Constraints\Regex => $this->applyRegex($constraint, $type),
            $constraint instanceof Constraints\Count => $this->applyCount($constraint, $type),
            $constraint instanceof Constraints\Choice => $this->applyChoice($constraint, $type),
            $constraint instanceof Constraints\Positive => $this->applyPositive($constraint, $type),
            $constraint instanceof Constraints\PositiveOrZero => $this->applyPositiveOrZero($constraint, $type),
            $constraint instanceof Constraints\Negative => $this->applyNegative($constraint, $type),
            $constraint instanceof Constraints\NegativeOrZero => $this->applyNegativeOrZero($constraint, $type),
            $constraint instanceof Constraints\Url => $this->applyUrl($constraint, $type),
            $constraint instanceof Constraints\Uuid => $this->applyUuid($constraint, $type),
            $constraint instanceof Constraints\DateTime => $this->applyDateTime($constraint, $type),
            $constraint instanceof Constraints\Date => $this->applyDate($constraint, $type),
            $constraint instanceof Constraints\Time => $this->applyTime($constraint, $type),
            default => null,
        };
    }

    private function applyLength(Constraints\Length $constraint, Type $type): void
    {
        if (! $type instanceof StringType) {
            return;
        }

        if ($constraint->min !== null) {
            $type->setMin($constraint->min);
        }

        if ($constraint->max !== null) {
            $type->setMax($constraint->max);
        }
    }

    private function applyRange(Constraints\Range $constraint, Type $type): void
    {
        if (! $type instanceof NumberType) {
            return;
        }

        if ($constraint->min !== null) {
            $type->setMin($constraint->min);
        }

        if ($constraint->max !== null) {
            $type->setMax($constraint->max);
        }
    }

    private function applyEmail(Constraints\Email $constraint, Type $type): void
    {
        $type->format = 'email';
    }

    private function applyRegex(Constraints\Regex $constraint, Type $type): void
    {
        if ($constraint->pattern) {
            $type->setAttribute('pattern', $constraint->pattern);
        }
    }

    private function applyCount(Constraints\Count $constraint, Type $type): void
    {
        if (! $type instanceof ArrayType) {
            return;
        }

        if ($constraint->min !== null) {
            $type->setMin($constraint->min);
        }

        if ($constraint->max !== null) {
            $type->setMax($constraint->max);
        }
    }

    private function applyChoice(Constraints\Choice $constraint, Type $type): void
    {
        if (! empty($constraint->choices)) {
            $type->enum = $constraint->choices;
        }
    }

    private function applyPositive(Constraints\Positive $constraint, Type $type): void
    {
        if (! $type instanceof NumberType) {
            return;
        }

        $type->setMin(1);
        $type->setAttribute('exclusiveMinimum', true);
    }

    private function applyPositiveOrZero(Constraints\PositiveOrZero $constraint, Type $type): void
    {
        if (! $type instanceof NumberType) {
            return;
        }

        $type->setMin(0);
    }

    private function applyNegative(Constraints\Negative $constraint, Type $type): void
    {
        if (! $type instanceof NumberType) {
            return;
        }

        $type->setMax(-1);
        $type->setAttribute('exclusiveMaximum', true);
    }

    private function applyNegativeOrZero(Constraints\NegativeOrZero $constraint, Type $type): void
    {
        if (! $type instanceof NumberType) {
            return;
        }

        $type->setMax(0);
    }

    private function applyUrl(Constraints\Url $constraint, Type $type): void
    {
        $type->format = 'uri';
    }

    private function applyUuid(Constraints\Uuid $constraint, Type $type): void
    {
        $type->format = 'uuid';
    }

    private function applyDateTime(Constraints\DateTime $constraint, Type $type): void
    {
        $type->format = 'date-time';
    }

    private function applyDate(Constraints\Date $constraint, Type $type): void
    {
        $type->format = 'date';
    }

    private function applyTime(Constraints\Time $constraint, Type $type): void
    {
        $type->format = 'time';
    }
}
