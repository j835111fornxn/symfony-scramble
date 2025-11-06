<?php

namespace Dedoc\Scramble\Tests\Infer;

use Dedoc\Scramble\Tests\SymfonyTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class AnnotatedReturnTypesTest extends SymfonyTestCase
{
    #[Test]
    #[DataProvider('genericsAnnotationProvider')]
    public function generatesGenericsAnnotationCorrectly(
        string $returnAnnotation,
        string $returnExpression,
        string $expectedInferredReturnTypeString
    ): void {
        $definition = $this->analyzeFile(<<<EOD
<?php
function foo (): {$returnAnnotation} {
    return {$returnExpression};
}
EOD
        )->getFunctionDefinition('foo');

        $this->assertSame(
            $expectedInferredReturnTypeString,
            $definition->getReturnType()->toString()
        );
    }

    public static function genericsAnnotationProvider(): array
    {
        return [
            ['Foo_AnnotatedReturnTypesTest', 'new Foo_AnnotatedReturnTypesTest(42)', 'Foo_AnnotatedReturnTypesTest<int(42)>'],
            ['int', 'new Foo_AnnotatedReturnTypesTest(42)', 'int'],
            ['Foo_AnnotatedReturnTypesTest', '42', 'Foo_AnnotatedReturnTypesTest'],
        ];
    }

    #[Test]
    public function understandsStaticKeywordsAnnotations(): void
    {
        $type = $this->getStatementType('(new Dedoc\Scramble\Tests\Infer\AnnotatedReturnTypesTest_StaticCallsBar)->fooMethod()->build()');

        $this->assertSame('array{from: string(bar)}', $type->toString());
    }
}

class Foo_AnnotatedReturnTypesTest
{
    public function __construct(private int $wow) {}
}

class AnnotatedReturnTypesTest_StaticCallsBar
{
    public function fooMethod(): AnnotatedReturnTypesTest_StaticCallsBuild
    {
        return new AnnotatedReturnTypesTest_StaticCallsBuild;
    }
}

class AnnotatedReturnTypesTest_StaticCallsBuild
{
    public function build(): array
    {
        return ['from' => 'bar'];
    }
}
