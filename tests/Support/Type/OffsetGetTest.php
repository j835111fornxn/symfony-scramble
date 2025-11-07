<?php

namespace Dedoc\Scramble\Tests\Support\Type;

use Dedoc\Scramble\Tests\SymfonyTestCase;
use PHPUnit\Framework\Attributes\Test;

final class OffsetGetTest extends SymfonyTestCase
{
    #[Test]
    public function handlesArrayFetch(): void
    {
        $this->assertHasType('int(1)', function () {
            return expect(['a' => 1]['a']);
        });
    }

    #[Test]
    public function handlesArrayDeepFetch(): void
    {
        $this->assertHasType('int(42)', function () {
            return expect(['b' => ['c' => 42]]['b']['c']);
        });
    }
}
