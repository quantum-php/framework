<?php

namespace Quantum\Tests\Unit\Database\Enums;

use Quantum\Database\Enums\Key;
use PHPUnit\Framework\TestCase;
use BackedEnum;

class KeyTest extends TestCase
{
    public function testKeyIsAStringBackedEnum(): void
    {
        $this->assertTrue(enum_exists(Key::class));
        $this->assertTrue(is_subclass_of(Key::class, BackedEnum::class));
    }

    public function testCasesKeepTheirValues(): void
    {
        $this->assertSame([
            'PRIMARY' => 'primary',
            'INDEX' => 'index',
            'UNIQUE' => 'unique',
            'FULLTEXT' => 'fulltext',
            'SPATIAL' => 'spatial',
        ], array_column(Key::cases(), 'value', 'name'));
    }
}
