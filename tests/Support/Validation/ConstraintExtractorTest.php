<?php

namespace Dedoc\Scramble\Tests\Support\Validation;

use Dedoc\Scramble\Support\Validation\ConstraintExtractor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

class ConstraintExtractorTest extends TestCase
{
    private ConstraintExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();

        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $this->extractor = new ConstraintExtractor($validator);
    }

    public function test_extracts_constraints_from_class(): void
    {
        $constraints = $this->extractor->extractFromClass(TestValidatedDto::class);

        $this->assertArrayHasKey('email', $constraints);
        $this->assertArrayHasKey('name', $constraints);
        $this->assertArrayHasKey('age', $constraints);

        $this->assertInstanceOf(Assert\NotBlank::class, $constraints['email'][0]);
        $this->assertInstanceOf(Assert\Email::class, $constraints['email'][1]);
        $this->assertInstanceOf(Assert\NotBlank::class, $constraints['name'][0]);
    }

    public function test_extracts_constraints_for_specific_property(): void
    {
        $constraints = $this->extractor->extractFromProperty(TestValidatedDto::class, 'email');

        $this->assertCount(2, $constraints);
        $this->assertInstanceOf(Assert\NotBlank::class, $constraints[0]);
        $this->assertInstanceOf(Assert\Email::class, $constraints[1]);
    }

    public function test_checks_if_class_has_constraints(): void
    {
        $this->assertTrue($this->extractor->hasConstraints(TestValidatedDto::class));
        $this->assertFalse($this->extractor->hasConstraints(\stdClass::class));
    }

    public function test_filters_constraints_by_validation_groups(): void
    {
        $constraints = $this->extractor->extractFromClass(
            TestValidatedDtoWithGroups::class,
            groups: ['create']
        );

        // 'email' and 'name' belong to 'create' group
        $this->assertArrayHasKey('email', $constraints);
        $this->assertArrayHasKey('name', $constraints);

        // 'updatedAt' only belongs to 'update' group, should not be included
        $this->assertArrayNotHasKey('updatedAt', $constraints);
    }

    public function test_filters_constraints_by_multiple_validation_groups(): void
    {
        $constraints = $this->extractor->extractFromClass(
            TestValidatedDtoWithGroups::class,
            groups: ['create', 'update']
        );

        // All properties should be included
        $this->assertArrayHasKey('email', $constraints);
        $this->assertArrayHasKey('name', $constraints);
        $this->assertArrayHasKey('updatedAt', $constraints);
    }

    public function test_has_constraints_respects_validation_groups(): void
    {
        $this->assertTrue(
            $this->extractor->hasConstraints(TestValidatedDtoWithGroups::class, groups: ['create'])
        );

        $this->assertTrue(
            $this->extractor->hasConstraints(TestValidatedDtoWithGroups::class, groups: ['update'])
        );

        $this->assertFalse(
            $this->extractor->hasConstraints(TestValidatedDtoWithGroups::class, groups: ['nonexistent'])
        );
    }

    public function test_extracts_property_constraints_with_groups(): void
    {
        $constraints = $this->extractor->extractFromProperty(
            TestValidatedDtoWithGroups::class,
            'name',
            groups: ['create']
        );

        // 'name' has NotBlank (only 'create') and Length ('create' and 'update')
        $this->assertCount(2, $constraints);
        $this->assertInstanceOf(Assert\NotBlank::class, $constraints[0]);
        $this->assertInstanceOf(Assert\Length::class, $constraints[1]);

        // 'updatedAt' is not in 'create' group
        $constraints = $this->extractor->extractFromProperty(
            TestValidatedDtoWithGroups::class,
            'updatedAt',
            groups: ['create']
        );

        $this->assertEmpty($constraints);
    }
}

/**
 * Test DTO with validation constraints.
 */
class TestValidatedDto
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;

    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 100)]
    public string $name;

    #[Assert\Range(min: 18, max: 120)]
    public int $age;

    #[Assert\Choice(choices: ['active', 'inactive'])]
    public string $status;
}

/**
 * Test DTO with validation groups.
 */
class TestValidatedDtoWithGroups
{
    #[Assert\NotBlank(groups: ['create'])]
    #[Assert\Email(groups: ['create', 'update'])]
    public string $email;

    #[Assert\NotBlank(groups: ['create'])]
    #[Assert\Length(min: 3, max: 100, groups: ['create', 'update'])]
    public string $name;

    #[Assert\NotBlank(groups: ['update'])]
    public ?\DateTimeInterface $updatedAt;
}
