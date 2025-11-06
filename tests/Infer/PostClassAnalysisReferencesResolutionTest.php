<?php

namespace Dedoc\Scramble\Tests\Infer;

use PHPUnit\Framework\Attributes\Test;
use Dedoc\Scramble\Tests\SymfonyTestCase;

final class PostClassAnalysisReferencesResolutionTest extends SymfonyTestCase
{
    #[Test]
    public function resolvesTemplatesTemplates(): void
    {
        $type = $this->analyzeFile(<<<'EOD'
<?php
class Foo {
    public function foo($q) {
        return $q;
    }
    public function bar() {
        return $this->foo(fn ($q) => $q);
    }
}
EOD)->getClassDefinition('Foo');

        $this->assertSame('(): <TQ>(TQ): TQ', $type->methods['bar']->type->toString());
    }
}
