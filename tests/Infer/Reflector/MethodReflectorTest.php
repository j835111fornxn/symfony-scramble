<?php

namespace Dedoc\Scramble\Tests\Infer\Reflector;

use Dedoc\Scramble\Infer\Reflector\MethodReflector;
use Dedoc\Scramble\Tests\Infer\Reflector\Files\Foo;
use Dedoc\Scramble\Tests\SymfonyTestCase;
use PHPUnit\Framework\Attributes\Test;

final class MethodReflectorTest extends SymfonyTestCase
{
    #[Test]
    public function getsMethodCodeFromParentDeclaration(): void
    {
        $reflector = MethodReflector::make(Foo::class, 'foo');

        $this->assertStringContainsString('return 1;', $reflector->getMethodCode());
    }

    #[Test]
    public function getsMethodAstFromDeclarationIfLineSeparatorIsCr(): void
    {
        $reflector = MethodReflector::make(Foo::class, 'foo');

        $this->assertNotNull($reflector->getAstNode());
    }
}
