<?php

namespace Dedoc\Scramble\Tests;

final class ComplexInferTypesTest extends SymfonyTestCase
{
    public function testInfersParamType(): void
    {
        $code = <<<'EOD'
<?php
function foo (int $a = 4) {
    return $a;
}
EOD;

        $result = $this->analyzeFile($code);

        $this->assertSame('TA', $result->getFunctionDefinition('foo')->type->getReturnType()->toString());
    }

    public function testInfersTypeFromAssignment(): void
    {
        $this->markTestSkipped('implement var type testing way');
        $code = <<<'EOD'
<?php
$a = 2;
$a = 5;
EOD;

        $result = $this->analyzeFile($code);

        $this->assertSame('int(5)', $result->getVarType('a')->toString());
    }

    public function testAssignmentWorksWithClosureScopes(): void
    {
        $this->markTestSkipped('implement var type testing way');
        $code = <<<'EOD'
<?php
$a = 2;
$b = fn () => $a;
EOD;

        $result = $this->analyzeFile($code);

        $this->assertSame('(): int(2)', $result->getVarType('b')->toString());
    }

    public function testAssignmentWorksWithFnScope(): void
    {
        $this->markTestSkipped('implement var type testing way');
        $code = <<<'EOD'
<?php
$a = 2;
$b = function () use ($a) {
    return $a;
};
EOD;

        $result = $this->analyzeFile($code);

        $this->assertSame('(): int(2)', $result->getVarType('b')->toString());
    }

    public function testArrayTypeIsAnalyzedWithDetails(): void
    {
        $code = <<<'EOD'
<?php
class Foo {
    public function toArray(): array
    {
        return ['foo' => 'bar'];
    }
}
EOD;

        $result = $this->analyzeFile($code);

        $this->assertSame(
            'array{foo: string(bar)}',
            $result->getClassDefinition('Foo')->getMethodCallType('toArray')->toString()
        );
    }

    /**
     * When int, float, bool, return type annotated, there is no point in using types from return
     * as there is no more useful information about the function can be extracted.
     * Sure we could've extracted some literals, but for now there is no point (?).
     */
    public function testUsesFunctionReturnAnnotationTypeWhenIntFloatBoolUsed(): void
    {
        $code = <<<'EOD'
<?php
class Foo {
    public function bar(): int
    {
        return [];
    }
}
EOD;

        $result = $this->analyzeFile($code);

        $this->assertSame('int', $result->getClassDefinition('Foo')->getMethodCallType('bar')->toString());
    }

    public function testInfersClassFetchType917(): void
    {
        $code = <<<'EOD'
<?php
function foo (string $class) {
    return (new $class)::class;
}
EOD;

        $result = $this->analyzeFile($code);

        $this->assertSame('string', $result->getFunctionDefinition('foo')->type->getReturnType()->toString());
    }

    public function testInfersClassFetchType912(): void
    {
        $code = <<<'EOD'
<?php
function bar (): mixed {
    return unknown();
}
function foo () {
    return bar()->sample();
}
EOD;

        $result = $this->analyzeFile($code);

        $this->assertSame('unknown', $result->getFunctionDefinition('foo')->type->getReturnType()->toString());
    }
}
