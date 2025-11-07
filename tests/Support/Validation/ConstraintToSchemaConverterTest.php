<?php

namespace Dedoc\Scramble\Tests\Support\Validation;

use Dedoc\Scramble\Support\Generator\Types\ArrayType;
use Dedoc\Scramble\Support\Generator\Types\NumberType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\Validation\ConstraintToSchemaConverter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints as Assert;

class ConstraintToSchemaConverterTest extends TestCase
{
    private ConstraintToSchemaConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new ConstraintToSchemaConverter;
    }

    public function test_applies_length_constraint_to_string(): void
    {
        $type = new StringType;
        $constraint = new Assert\Length(min: 5, max: 50);

        $this->converter->applyConstraints([$constraint], $type, 'test');

        $this->assertEquals(5, $type->getAttribute('minLength'));
        $this->assertEquals(50, $type->getAttribute('maxLength'));
    }

    public function test_applies_range_constraint_to_number(): void
    {
        $type = new NumberType;
        $constraint = new Assert\Range(min: 10, max: 100);

        $this->converter->applyConstraints([$constraint], $type, 'test');

        $this->assertEquals(10, $type->getAttribute('minimum'));
        $this->assertEquals(100, $type->getAttribute('maximum'));
    }

    public function test_applies_email_constraint(): void
    {
        $type = new StringType;
        $constraint = new Assert\Email;

        $this->converter->applyConstraints([$constraint], $type, 'test');

        $this->assertEquals('email', $type->format);
    }

    public function test_applies_regex_constraint(): void
    {
        $type = new StringType;
        $constraint = new Assert\Regex(pattern: '/^[A-Z]+$/');

        $this->converter->applyConstraints([$constraint], $type, 'test');

        $this->assertEquals('/^[A-Z]+$/', $type->getAttribute('pattern'));
    }

    public function test_applies_count_constraint_to_array(): void
    {
        $type = new ArrayType;
        $constraint = new Assert\Count(min: 1, max: 10);

        $this->converter->applyConstraints([$constraint], $type, 'test');

        $this->assertEquals(1, $type->getAttribute('minItems'));
        $this->assertEquals(10, $type->getAttribute('maxItems'));
    }

    public function test_applies_choice_constraint(): void
    {
        $type = new StringType;
        $constraint = new Assert\Choice(choices: ['active', 'inactive', 'pending']);

        $this->converter->applyConstraints([$constraint], $type, 'test');

        $this->assertEquals(['active', 'inactive', 'pending'], $type->enum);
    }

    public function test_applies_positive_constraint(): void
    {
        $type = new NumberType;
        $constraint = new Assert\Positive;

        $this->converter->applyConstraints([$constraint], $type, 'test');

        $this->assertEquals(1, $type->getAttribute('minimum'));
        $this->assertTrue($type->getAttribute('exclusiveMinimum'));
    }

    public function test_applies_positive_or_zero_constraint(): void
    {
        $type = new NumberType;
        $constraint = new Assert\PositiveOrZero;

        $this->converter->applyConstraints([$constraint], $type, 'test');

        $this->assertEquals(0, $type->getAttribute('minimum'));
    }

    public function test_applies_url_constraint(): void
    {
        $type = new StringType;
        $constraint = new Assert\Url;

        $this->converter->applyConstraints([$constraint], $type, 'test');

        $this->assertEquals('uri', $type->format);
    }

    public function test_applies_uuid_constraint(): void
    {
        $type = new StringType;
        $constraint = new Assert\Uuid;

        $this->converter->applyConstraints([$constraint], $type, 'test');

        $this->assertEquals('uuid', $type->format);
    }

    public function test_applies_datetime_constraint(): void
    {
        $type = new StringType;
        $constraint = new Assert\DateTime;

        $this->converter->applyConstraints([$constraint], $type, 'test');

        $this->assertEquals('date-time', $type->format);
    }

    public function test_applies_date_constraint(): void
    {
        $type = new StringType;
        $constraint = new Assert\Date;

        $this->converter->applyConstraints([$constraint], $type, 'test');

        $this->assertEquals('date', $type->format);
    }

    public function test_applies_time_constraint(): void
    {
        $type = new StringType;
        $constraint = new Assert\Time;

        $this->converter->applyConstraints([$constraint], $type, 'test');

        $this->assertEquals('time', $type->format);
    }

    public function test_applies_multiple_constraints(): void
    {
        $type = new StringType;
        $constraints = [
            new Assert\NotBlank,
            new Assert\Length(min: 5, max: 100),
            new Assert\Email,
        ];

        $this->converter->applyConstraints($constraints, $type, 'test');

        $this->assertEquals(5, $type->getAttribute('minLength'));
        $this->assertEquals(100, $type->getAttribute('maxLength'));
        $this->assertEquals('email', $type->format);
    }
}
