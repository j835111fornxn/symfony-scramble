<?php

namespace Dedoc\Scramble\Tests\Infer;

use Dedoc\Scramble\Tests\Support\DataProviders;
use Dedoc\Scramble\Tests\SymfonyTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class FunctionTemplateTest extends SymfonyTestCase
{
    #[Test]
    public function generatesFunctionTypeWithGenericCorrectly(): void
    {
        $type = $this->analyzeFile(<<<'EOD'
<?php
function foo ($a) {
    return $a;
}
EOD)->getFunctionDefinition('foo');

        $this->assertSame('<TA>(TA): TA', $type->type->toString());
    }

    #[Test]
    public function getsATypeOfCallOfAFunctionWithGenericCorrectly(): void
    {
        $type = $this->analyzeFile(<<<'EOD'
<?php
function foo ($a) {
    return $a;
}
EOD)->getExpressionType("foo('wow')");

        $this->assertSame('string(wow)', $type->toString());
    }

    #[Test]
    #[DataProvider('extendableTemplateTypesProvider')]
    public function addsATypeConstraintOntoTemplateTypeForSomeTypes(string $paramType, string $expectedParamType, string $expectedTemplateDefinitionType = ''): void
    {
        $def = $this->analyzeFile("<?php function foo ($paramType \$a) {}")->getFunctionDefinition('foo');

        $this->assertSame($expectedParamType, $def->type->arguments['a']->toString());

        if (! $expectedTemplateDefinitionType) {
            $this->assertEmpty($def->type->templates);
        } else {
            $this->assertSame($expectedTemplateDefinitionType, $def->type->templates[0]->toDefinitionString());
        }
    }

    public static function extendableTemplateTypesProvider(): array
    {
        return DataProviders::extendableTemplateTypes();
    }

    #[Test]
    public function infersAReturnTypeOfCallOfAFunctionWithArgumentDefaultConst(): void
    {
        $type = $this->analyzeFile(<<<'EOD'
<?php
function foo (int $a = \Illuminate\Http\Response::HTTP_CREATED) {
    return ['a' => $a];
}
EOD)->getExpressionType('foo()');

        $this->assertSame('array{a: int(201)}', $type->toString());
    }
}
