<?php

namespace Dedoc\Scramble\Tests\Infer\Scope;

use Dedoc\Scramble\Tests\Support\AnalysisHelpers;
use Dedoc\Scramble\Tests\SymfonyTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class ScopeTest extends SymfonyTestCase
{
    use AnalysisHelpers;

    private function getStatementTypeForScopeTest(string $statement, array $extensions = [])
    {
        return $this->analyzeFile('<?php', $extensions)->getExpressionType($statement);
    }

    #[Test]
    #[DataProvider('propertyFetchTypesProvider')]
    public function infersPropertyFetchNodesTypes(string $code, string $expectedTypeString): void
    {
        $this->assertSame($expectedTypeString, $this->getStatementTypeForScopeTest($code)->toString());
    }

    public static function propertyFetchTypesProvider(): array
    {
        return [
            ['$foo->bar', 'unknown'],
            ['$foo->bar->{"baz"}', 'unknown'],
        ];
    }

    #[Test]
    #[DataProvider('ternaryExpressionsTypesProvider')]
    public function infersTernaryExpressionsNodesTypes(string $code, string $expectedTypeString): void
    {
        $this->assertSame($expectedTypeString, $this->getStatementTypeForScopeTest($code)->toString());
    }

    public static function ternaryExpressionsTypesProvider(): array
    {
        return [
            ['unknown() ? 1 : null', 'int(1)|null'],
            ['unknown() ? 1 : 1', 'int(1)'],
            ['unknown() ?: 1', 'unknown|int(1)'],
            ['(int) unknown() ?: "w"', 'int|string(w)'],
            ['1 ?: 1', 'int(1)'],
            ['unknown() ? 1 : unknown()', 'int(1)|unknown'],
            ['unknown() ? unknown() : unknown()', 'unknown'],
            ['unknown() ?: unknown()', 'unknown'],
            ['unknown() ?: true ?: 1', 'unknown|boolean(true)|int(1)'],
            ['unknown() ?: unknown() ?: unknown()', 'unknown'],
        ];
    }

    #[Test]
    #[DataProvider('nullCoalescingExpressionsProvider')]
    public function infersExpressionsFromANullCoalescingOperator(string $code, string $expectedTypeString): void
    {
        $this->assertSame($expectedTypeString, $this->getStatementTypeForScopeTest($code)->toString());
    }

    public static function nullCoalescingExpressionsProvider(): array
    {
        return [
            ['unknown() ?? 1', 'unknown|int(1)'],
            ['(int) unknown() ?? "w"', 'int|string(w)'],
            ['1 ?? 1', 'int(1)'],
            ['unknown() ?? unknown()', 'unknown'],
            ['unknown() ?? true ?? 1', 'unknown|boolean(true)|int(1)'],
            ['unknown() ?? unknown() ?? unknown()', 'unknown'],
        ];
    }

    #[Test]
    #[DataProvider('matchNodeTypesProvider')]
    public function infersMatchNodeType(string $code, string $expectedTypeString): void
    {
        $this->assertSame($expectedTypeString, $this->getStatementTypeForScopeTest($code)->toString());
    }

    public static function matchNodeTypesProvider(): array
    {
        return [
            [<<<'EOD'
match (unknown()) {
    42 => 1,
    default => null,
}
EOD, 'int(1)|null'],
        ];
    }

    #[Test]
    #[DataProvider('throwNodeTypesProvider')]
    public function infersThrowNodeType(string $code, string $expectedTypeString): void
    {
        $this->assertSame($expectedTypeString, $this->getStatementTypeForScopeTest($code)->toString());
    }

    public static function throwNodeTypesProvider(): array
    {
        return [
            ['throw new Exception("foo")', 'void'],
        ];
    }
}
