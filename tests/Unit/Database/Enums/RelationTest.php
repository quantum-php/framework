<?php

namespace Quantum\Tests\Unit\Database\Enums;

use Quantum\Database\Enums\Relation;
use PHPUnit\Framework\TestCase;
use BackedEnum;

class RelationTest extends TestCase
{
    public function testRelationIsAStringBackedEnum(): void
    {
        $this->assertTrue(enum_exists(Relation::class));
        $this->assertTrue(is_subclass_of(Relation::class, BackedEnum::class));
    }

    public function testCasesKeepTheirValues(): void
    {
        $this->assertSame([
            'HAS_ONE' => 'hasOne',
            'HAS_MANY' => 'hasMany',
            'BELONGS_TO' => 'belongsTo',
            'BELONGS_TO_MANY' => 'belongsToMany',
        ], array_column(Relation::cases(), 'value', 'name'));
    }
}
