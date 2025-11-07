<?php

namespace Dedoc\Scramble\Tests\Support\Type;

use Dedoc\Scramble\Support\Type\Literal\LiteralIntegerType;
use Dedoc\Scramble\Support\Type\TypePath;
use Dedoc\Scramble\Tests\SymfonyTestCase;
use PHPUnit\Framework\Attributes\Test;

final class TypePathTest extends SymfonyTestCase
{
    #[Test]
    public function findsType(): void
    {
        $type = $this->getStatementType(<<<'EOD'
['a' => fn (int $b) => 123]
EOD);

        $path = TypePath::findFirst(
            $type,
            fn ($t) => $t instanceof LiteralIntegerType,
        );

        $this->assertSame('int(123)', $path?->getFrom($type)->toString());
    }
}
