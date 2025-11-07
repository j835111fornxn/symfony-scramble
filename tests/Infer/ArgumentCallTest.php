<?php

namespace Dedoc\Scramble\Tests\Infer;

use Dedoc\Scramble\Tests\SymfonyTestCase;
use PHPUnit\Framework\Attributes\Test;

final class ArgumentCallTest extends SymfonyTestCase
{
    #[Test]
    public function infersReturnTypeOfPropertyFetchOnObject(): void
    {
        $type = $this->analyzeFile(<<<'EOD'
<?php
class Foo {
    public function bar(\Dedoc\Scramble\Tests\Infer\Bar $object)
    {
        return $object->id;
    }
}
EOD)->getExpressionType('(new Foo)->bar()');

        $this->assertSame('int', $type->toString());
    }

    #[Test]
    public function infersReturnTypeOfPropertyFetchOnCreatedObject(): void
    {
        $type = $this->analyzeFile(<<<'EOD'
<?php
class Foo {
    public function bar()
    {
        $object = new \Dedoc\Scramble\Tests\Infer\Bar(123);

        return $object->id;
    }
}
EOD)->getExpressionType('(new Foo)->bar()');

        $this->assertSame('int(123)', $type->toString());
    }

    #[Test]
    public function infersReturnTypeOfMethodCallOnArgument(): void
    {
        $type = $this->analyzeFile(<<<'EOD'
<?php
class Foo {
    public function bar(\Dedoc\Scramble\Tests\Infer\Bar $object)
    {
        return $object->foo();
    }
}
EOD)->getExpressionType('(new Foo)->bar()');

        $this->assertSame('int', $type->toString());
    }
}

class Bar
{
    public function __construct(
        public int $id,
    ) {}

    public function foo()
    {
        return $this->id;
    }
}
