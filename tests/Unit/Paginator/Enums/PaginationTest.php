<?php

namespace Quantum\Tests\Unit\Paginator\Enums;

use Quantum\Paginator\Enums\Pagination;
use PHPUnit\Framework\TestCase;
use BackedEnum;

class PaginationTest extends TestCase
{
    public function testPaginationIsAStringBackedEnum(): void
    {
        $this->assertTrue(enum_exists(Pagination::class));
        $this->assertTrue(is_subclass_of(Pagination::class, BackedEnum::class));
    }

    public function testCasesKeepTheirValues(): void
    {
        $this->assertSame([
            'PAGINATION_CLASS' => 'pagination',
            'PAGINATION_CLASS_ACTIVE' => 'active',
            'PER_PAGE' => 'per_page',
            'PAGE' => 'page',
        ], array_column(Pagination::cases(), 'value', 'name'));
    }
}
