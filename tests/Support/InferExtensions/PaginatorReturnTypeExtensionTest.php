<?php

namespace Dedoc\Scramble\Tests\Support\InferExtensions;

use Dedoc\Scramble\Support\InferExtensions\PaginateMethodsReturnTypeExtension;
use Dedoc\Scramble\Tests\Fixtures\Laravel\Models\SampleUserModel;
use Dedoc\Scramble\Tests\SymfonyTestCase;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class PaginatorReturnTypeExtensionTest extends SymfonyTestCase
{
    #[Test]
    #[DataProvider('paginateTypesProvider')]
    public function guessesPaginateType(string $expression, string $expectedTypeString): void
    {
        $type = $this->getStatementType($expression, [
            new PaginateMethodsReturnTypeExtension,
        ]);

        $this->assertSame($expectedTypeString, $type->toString());
    }

    public static function paginateTypesProvider(): array
    {
        return [
            [SampleUserModel::class.'::paginate()', LengthAwarePaginator::class.'<int, unknown>'],
            [SampleUserModel::class.'::query()->paginate()', LengthAwarePaginator::class.'<int, unknown>'],
            [SampleUserModel::class.'::query()->fastPaginate()', LengthAwarePaginator::class.'<int, unknown>'],
            [SampleUserModel::class.'::query()->where("foo", "bar")->paginate()', LengthAwarePaginator::class.'<int, unknown>'],

            [SampleUserModel::class.'::cursorPaginate()', CursorPaginator::class.'<int, unknown>'],
            [SampleUserModel::class.'::query()->cursorPaginate()', CursorPaginator::class.'<int, unknown>'],

            [SampleUserModel::class.'::simplePaginate()', Paginator::class.'<int, unknown>'],
            [SampleUserModel::class.'::query()->simplePaginate()', Paginator::class.'<int, unknown>'],
        ];
    }
}
