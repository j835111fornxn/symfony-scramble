<?php

namespace Dedoc\Scramble\Tests\Support\Type;

use Dedoc\Scramble\Tests\SymfonyTestCase;
use PHPUnit\Framework\Attributes\Test;

final class OffsetUnsetTest extends SymfonyTestCase
{
    #[Test]
    public function handlesArrayUnset(): void
    {
        $this->assertHasType('list{}', function () {
            $a = ['foo' => 42];
            unset($a['foo']);

            return expect($a);
        });
    }
}
