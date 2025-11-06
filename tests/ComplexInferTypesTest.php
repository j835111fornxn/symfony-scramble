<?php

namespace Dedoc\Scramble\Tests;

final class ComplexInferTypesTest extends SymfonyTestCase
{
    public function test_infers_param_type(): void
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

    public function test_infers_type_from_assignment(): void
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

    public function test_assignment_works_with_closure_scopes(): void
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

    public function test_assignment_works_with_fn_scope(): void
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

    public function test_array_type_is_analyzed_with_details(): void
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
    public function test_uses_function_return_annotation_type_when_int_float_bool_used(): void
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

    public function test_infers_class_fetch_type917(): void
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

    public function test_infers_class_fetch_type912(): void
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
