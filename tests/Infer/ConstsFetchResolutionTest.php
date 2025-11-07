<?php

namespace Dedoc\Scramble\Tests\Infer;

use Dedoc\Scramble\Tests\Infer\stubs\ResponseTrait;
use Dedoc\Scramble\Tests\SymfonyTestCase;
use PHPUnit\Framework\Attributes\Test;

final class ConstsFetchResolutionTest extends SymfonyTestCase
{
    #[Test]
    public function infersAReturnTypeOfTraitMethod(): void
    {
        $type = $this->getStatementType('(new \Dedoc\Scramble\Tests\Infer\Bar_Consts)->foo()');

        $this->assertSame('int(100)', $type->toString());
    }
}

class Bar_Consts
{
    use ResponseTrait;
}
