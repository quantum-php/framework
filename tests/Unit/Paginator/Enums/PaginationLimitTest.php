<?php

namespace Quantum\Tests\Unit\Paginator\Enums;

use Quantum\Paginator\Enums\PaginationLimit;
use PHPUnit\Framework\TestCase;
use BackedEnum;

class PaginationLimitTest extends TestCase
{
    public function testPaginationLimitIsAnEnumWithoutBackingType(): void
    {
        $this->assertTrue(enum_exists(PaginationLimit::class));
        $this->assertFalse(is_subclass_of(PaginationLimit::class, BackedEnum::class));
    }

    public function testConstantsKeepTheirValues(): void
    {
        $this->assertSame(1, PaginationLimit::FIRST_PAGE_NUMBER);
        $this->assertSame(3, PaginationLimit::MINIMUM_PAGE_ITEMS_COUNT);
        $this->assertSame(3, PaginationLimit::EDGE_PADDING);
    }
}
