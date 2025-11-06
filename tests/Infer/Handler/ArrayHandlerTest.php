<?php

namespace Dedoc\Scramble\Tests\Infer\Handler;

use Dedoc\Scramble\Support\Type\KeyedArrayType;
use Dedoc\Scramble\Tests\Support\AnalysisHelpers;
use Dedoc\Scramble\Tests\SymfonyTestCase;
use PHPUnit\Framework\Attributes\Test;

final class ArrayHandlerTest extends SymfonyTestCase
{
    use AnalysisHelpers;

    #[Test]
    public function infersKeyedArrayShapeType(): void
    {
        $type = $this->getStatementType("['foo' => 1, 'bar' => 'foo', 23]");

        $this->assertInstanceOf(KeyedArrayType::class, $type);
        $this->assertSame('array{foo: int(1), bar: string(foo), 0: int(23)}', $type->toString());
    }

    #[Test]
    public function infersListType(): void
    {
        $type = $this->getStatementType("[1, 2, 'foo']");

        $this->assertInstanceOf(KeyedArrayType::class, $type);
        $this->assertTrue($type->isList);
        $this->assertSame('list{int(1), int(2), string(foo)}', $type->toString());
    }

    #[Test]
    public function infersArraySpreadInResultingType(): void
    {
        $this->assertSame(
            'array{0: int(42), b: string(wow), a: int(1), 1: int(16), 2: int(23)}',
            $this->getStatementType("[42, 'b' => 'foo', ...['a' => 1, 'b' => 'wow', 16], 23]")->toString()
        );
    }

    #[Test]
    public function infersArraySpreadFromOtherMethods(): void
    {
        // @todo: Move test to reference resolving tests group
        $type = $this->analyzeFile(<<<'EOD'
<?php
class Foo {
    public function foo () {
        return ['b' => 'foo', ['c' => 'w', ...$this->bar()]];
    }
    public function bar () {
        return ['a' => 123];
    }
}
EOD)->getClassDefinition('Foo');

        $this->assertSame(
            '(): array{b: string(foo), 0: array{c: string(w), a: int(123)}}',
            $type->methods['foo']->type->toString()
        );
    }
}
