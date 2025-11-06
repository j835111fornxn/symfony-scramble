<?php

namespace Dedoc\Scramble\Tests\Infer;

use Dedoc\Scramble\Support\Type\Generic;
use Dedoc\Scramble\Support\Type\Literal\LiteralStringType;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Type;
use Dedoc\Scramble\Support\Type\TypeTraverser;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TypeTraverserTest extends TestCase
{
    #[Test]
    public function mutatesType(): void
    {
        $traverser = new TypeTraverser([
            new class
            {
                public function enter(Type $type) {}

                public function leave(Type $type)
                {
                    if ($type instanceof ObjectType && $type->name === 'replace_me') {
                        return new LiteralStringType('replaced');
                    }

                    return null;
                }
            },
        ]);

        $type = new Generic('self', [
            new ObjectType('replace_me'),
        ]);

        $result = $traverser->traverse($type);

        $this->assertInstanceOf(Generic::class, $result);
        $this->assertInstanceOf(LiteralStringType::class, $result->templateTypes[0]);
        $this->assertSame('replaced', $result->templateTypes[0]->value);
    }
}
