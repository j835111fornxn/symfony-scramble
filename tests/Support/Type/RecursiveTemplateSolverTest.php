<?php

namespace Dedoc\Scramble\Tests\Support\Type;

use Dedoc\Scramble\Support\Collection;
use Dedoc\Scramble\Support\Type\ArrayType;
use Dedoc\Scramble\Support\Type\FunctionType;
use Dedoc\Scramble\Support\Type\Generic;
use Dedoc\Scramble\Support\Type\IntegerType;
use Dedoc\Scramble\Support\Type\Literal\LiteralIntegerType;
use Dedoc\Scramble\Support\Type\RecursiveTemplateSolver;
use Dedoc\Scramble\Support\Type\StringType;
use Dedoc\Scramble\Support\Type\TemplateType;
use Dedoc\Scramble\Support\Type\Union;
use Dedoc\Scramble\Tests\SymfonyTestCase;
use PHPUnit\Framework\Attributes\Test;

final class RecursiveTemplateSolverTest extends SymfonyTestCase
{
    private RecursiveTemplateSolver $solver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->solver = new RecursiveTemplateSolver;
    }

    #[Test]
    public function findsSimplestType(): void
    {
        $foundType = $this->solver->solve(
            $t = new TemplateType('T'),
            new IntegerType,
            $t,
        );

        $this->assertSame('int', $foundType->toString());
    }

    #[Test]
    public function findsUnionType(): void
    {
        $foundType = $this->solver->solve(
            new Union([
                $t = new TemplateType('T'),
                new TemplateType('G'),
            ]),
            new Union([
                new IntegerType,
                new StringType,
            ]),
            $t,
        );

        $this->assertSame('int|string', $foundType->toString());
    }

    #[Test]
    public function findsGenericType(): void
    {
        $foundType = $this->solver->solve(
            new Union([
                new Generic('A', [new IntegerType, $t = new TemplateType('T')]),
                new Generic(Collection::class, [new IntegerType, $t]),
            ]),
            new Generic(Collection::class, [new IntegerType, new IntegerType]),
            $t,
        );

        $this->assertSame('int', $foundType->toString());
    }

    #[Test]
    public function findsStructuralMatchingTypeWithGenerics(): void
    {
        $foundType = $this->solver->solve(
            new Union([
                $t = new TemplateType('T'),
                new Generic(Collection::class, [new IntegerType, $t]),
            ]),
            new Generic(Collection::class, [new IntegerType, new IntegerType]),
            $t,
        );

        $this->assertSame('int', $foundType->toString());
    }

    #[Test]
    public function findsStructuralMatchingTypeWithCallables(): void
    {
        $foundType = $this->solver->solve(
            new Union([
                $t = new TemplateType('T'),
                new FunctionType('{}', [], $t),
            ]),
            new FunctionType('{}', [], new IntegerType),
            $t,
        );

        $this->assertSame('int', $foundType->toString());
    }

    #[Test]
    public function findsStructuralMatchingTypeWithArraysAndIterables(): void
    {
        $foundType = $this->solver->solve(
            new Generic('iterable', [new IntegerType, $t = new TemplateType('T')]),
            new ArrayType(new LiteralIntegerType(42)),
            $t,
        );

        $this->assertSame('int(42)', $foundType->toString());
    }
}
