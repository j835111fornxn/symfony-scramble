<?php

namespace Dedoc\Scramble\Tests\Infer\DefinitionBuilders;

use Dedoc\Scramble\Infer\DefinitionBuilders\FunctionLikeReflectionDefinitionBuilder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FunctionLikeReflectionDefinitionBuilderTest extends TestCase
{
    #[Test]
    public function it_builds_the_definition_for_is_null(): void
    {
        $def = (new FunctionLikeReflectionDefinitionBuilder('is_null'))->build();

        $this->assertSame('(null|mixed): boolean', $def->type->toString());
    }
}
