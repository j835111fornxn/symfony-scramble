<?php

namespace Dedoc\Scramble\Tests\Generator\Operation;

use Dedoc\Scramble\Support\Generator\Operation;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class OperationTest extends TestCase
{
    #[Test]
    public function deprecatedKeyIsProperlySet(): void
    {
        $operation = new Operation('get');
        $operation->deprecated(true);

        $array = $operation->toArray();

        $this->assertTrue($operation->deprecated);
        $this->assertArrayHasKey('deprecated', $array);
        $this->assertTrue($array['deprecated']);
    }

    #[Test]
    public function defaultDeprecatedKeyIsFalse(): void
    {
        $operation = new Operation('get');

        $array = $operation->toArray();

        $this->assertFalse($operation->deprecated);
        $this->assertArrayNotHasKey('deprecated', $array);
    }

    #[Test]
    public function setExtensionProperty(): void
    {
        $operation = new Operation('get');
        $operation->setExtensionProperty('custom-key', 'custom-value');

        $array = $operation->toArray();

        $this->assertSame(['x-custom-key' => 'custom-value'], $array);
    }
}
