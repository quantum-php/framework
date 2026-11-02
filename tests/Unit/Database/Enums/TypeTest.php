<?php

namespace Quantum\Tests\Unit\Database\Enums;

use Quantum\Database\Enums\Type;
use PHPUnit\Framework\TestCase;
use BackedEnum;

class TypeTest extends TestCase
{
    public function testTypeIsAStringBackedEnum(): void
    {
        $this->assertTrue(enum_exists(Type::class));
        $this->assertTrue(is_subclass_of(Type::class, BackedEnum::class));
    }

    public function testCasesKeepTheirValues(): void
    {
        $this->assertSame([
            'INT' => 'int',
            'TINYINT' => 'tinyint',
            'FLOAT' => 'float',
            'DOUBLE' => 'double',
            'DECIMAL' => 'decimal',
            'BOOL' => 'bool',
            'CHAR' => 'char',
            'VARCHAR' => 'varchar',
            'TEXT' => 'text',
            'BINARY' => 'binary',
            'BLOB' => 'blob',
            'ENUM' => 'enum',
            'DATE' => 'date',
            'DATETIME' => 'datetime',
            'TIMESTAMP' => 'timestamp',
            'JSON' => 'json',
            'GEOMETRY' => 'geometry',
        ], array_column(Type::cases(), 'value', 'name'));
    }
}
