<?php

namespace Dedoc\Scramble\Tests\Infer;

use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Tests\SymfonyTestCase;
use PHPUnit\Framework\Attributes\Test;

final class MethodTemplateTest extends SymfonyTestCase
{
    #[Test]
    public function generatesFunctionTypeWithGenericCorrectly(): void
    {
        $type = $this->analyzeFile(<<<'EOD'
<?php
class Foo {
    public function foo ($a) {
        return $a;
    }
}
EOD)->getClassDefinition('Foo');

        $this->assertSame('<TA>(TA): TA', $type->methods['foo']->type->toString());
    }

    #[Test]
    public function getsATypeOfCallOfAFunctionWithGenericCorrectly(): void
    {
        $type = $this->analyzeFile(<<<'EOD'
<?php
class Foo {
    public function foo ($a) {
        return $a;
    }
}
EOD)->getExpressionType("(new Foo)->foo('wow')");

        $this->assertSame('string(wow)', $type->toString());
    }

    #[Test]
    public function getsATypeOfCallOfAFunctionWithGenericClassCorrectly(): void
    {
        $this->analyzeFile(<<<'EOD'
<?php
class Foo {
   public function foo (Foo $a) {
       return $a;
   }
}
EOD);

        $type = new ObjectType('Foo');

        $this->assertSame('Foo', $type->getMethodReturnType('foo')->toString());
    }

    #[Test]
    public function getsATypeOfCallOfAFunctionWithGenericIfParameterIsPassedAndHasDefaultValue(): void
    {
        $file = $this->analyzeFile(<<<'EOD'
<?php
class Foo {
    public function foo($a = 'wow') {
        return $a;
    }
}
EOD);

        $this->assertSame('string(wow)', $file->getExpressionType('(new Foo)->foo()')->toString());
        $this->assertSame('string(bar)', $file->getExpressionType("(new Foo)->foo('bar')")->toString());
    }

    #[Test]
    public function getsATypeOfConstructorCallIfParameterHasDefaultValue(): void
    {
        $file = $this->analyzeFile(<<<'EOD'
<?php
class Foo {
    public $prop;

    public function __construct($a = 'wow') {
        $this->prop = $a;
    }
}
EOD);

        $this->assertSame('Foo<string(wow)>', $file->getExpressionType('new Foo')->toString());
        $this->assertSame('Foo<string(foo)>', $file->getExpressionType('new Foo("foo")')->toString());
    }
}
