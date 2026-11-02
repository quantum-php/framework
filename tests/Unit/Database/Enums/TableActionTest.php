<?php

namespace Quantum\Tests\Unit\Database\Enums;

use Quantum\Database\Enums\TableAction;
use PHPUnit\Framework\TestCase;
use BackedEnum;

class TableActionTest extends TestCase
{
    public function testTableActionIsAnIntBackedEnum(): void
    {
        $this->assertTrue(enum_exists(TableAction::class));
        $this->assertTrue(is_subclass_of(TableAction::class, BackedEnum::class));
    }

    public function testCasesKeepTheirValues(): void
    {
        $this->assertSame([
            'CREATE' => 1,
            'ALTER' => 2,
            'DROP' => 3,
            'RENAME' => 4,
        ], array_column(TableAction::cases(), 'value', 'name'));
    }
}
