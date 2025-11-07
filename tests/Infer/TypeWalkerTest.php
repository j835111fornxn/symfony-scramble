<?php

namespace Dedoc\Scramble\Tests\Infer;

use Dedoc\Scramble\Support\Type\IntegerType;
use Dedoc\Scramble\Support\Type\StringType;
use Dedoc\Scramble\Support\Type\TypeWalker;
use Dedoc\Scramble\Support\Type\Union;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TypeWalkerTest extends TestCase
{
    #[Test]
    public function replacesNonSelfReferencingTypeInSelfReferencingType(): void
    {
        $type = new Union([new IntegerType]);
        $type->types[] = $type;

        $replacedType = (new TypeWalker)->replace($type, fn ($t) => $t instanceof IntegerType ? new StringType : null);

        $this->assertInstanceOf(StringType::class, $replacedType->types[0]);
        $this->assertSame($replacedType, $replacedType->types[1]);
    }
}
