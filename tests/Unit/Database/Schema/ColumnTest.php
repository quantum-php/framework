<?php

namespace Quantum\Tests\Unit\Database\Schema;

use Quantum\Database\Schemas\Column;
use Quantum\Database\Enums\Type;
use PHPUnit\Framework\TestCase;

class ColumnTest extends TestCase
{
    public function testColumnConstructor(): void
    {
        $this->assertInstanceOf(Column::class, new Column('test'));
    }

    public function testColumnAcceptsAnySqlTypeAsText(): void
    {
        $this->assertSame('BIGINT', (new Column('test', 'bigint', 20))->get(Column::TYPE));
        $this->assertSame('INTEGER', (new Column('test', 'integer'))->get(Column::TYPE));
    }

    public function testColumnTypeIsTheSameFromTheEnumAndFromText(): void
    {
        $this->assertSame(
            (new Column('test', Type::VARCHAR, 255))->get(Column::TYPE),
            (new Column('test', 'varchar', 255))->get(Column::TYPE)
        );
    }

    public function testColumnRename(): void
    {
        $column = new Column('test', Type::INT, 11);

        $column->renameTo('new_test');

        $this->assertEquals('new_test', $column->get('newName'));
    }

    public function testColumnGet(): void
    {
        $column = new Column('test', Type::INT, 11);

        $this->assertEquals('test', $column->get(Column::NAME));

        $this->assertEquals('INT', $column->get(Column::TYPE));

        $this->assertEquals(11, $column->get(Column::CONSTRAINT));
    }

    public function testColumnAutoIncrement(): void
    {
        $column = new Column('test', Type::INT, 11);

        $column->autoIncrement();

        $this->assertEquals('AUTO_INCREMENT', $column->get(Column::AUTO_INCREMENT));

        $this->assertEquals('primary', $column->get('indexKey'));
    }

    public function testColumnPrimaryKey(): void
    {
        $column = new Column('test', Type::INT, 11);

        $column->primary();

        $this->assertEquals('primary', $column->get('indexKey'));
    }

    public function testColumnIndexKey(): void
    {
        $column = new Column('test', Type::INT, 11);

        $column->index('idx_name');

        $this->assertEquals('index', $column->get('indexKey'));

        $this->assertEquals('idx_name', $column->get('indexName'));
    }

    public function testColumnUniqueKey(): void
    {
        $column = new Column('test', Type::INT, 11);

        $column->unique('idx_unique_name');

        $this->assertEquals('unique', $column->get('indexKey'));

        $this->assertEquals('idx_unique_name', $column->get('indexName'));
    }

    public function testColumnFulltextKey(): void
    {
        $column = new Column('test', Type::INT, 11);

        $column->fulltext('idx_fulltext_name');

        $this->assertEquals('fulltext', $column->get('indexKey'));

        $this->assertEquals('idx_fulltext_name', $column->get('indexName'));
    }

    public function testColumnSpatialKey(): void
    {
        $column = new Column('test', Type::INT, 11);

        $column->spatial('idx_spatial_name');

        $this->assertEquals('spatial', $column->get('indexKey'));

        $this->assertEquals('idx_spatial_name', $column->get('indexName'));
    }

    public function testAddOrRemoveColumnNullableProperty(): void
    {
        $column = new Column('test', Type::VARCHAR, 255);

        $this->assertEquals('NOT NULL', $column->get(Column::NULLABLE));

        $column->nullable();

        $this->assertEquals('NULL', $column->get(Column::NULLABLE));

        $column->nullable(false);

        $this->assertEquals('NOT NULL', $column->get(Column::NULLABLE));
    }

    public function testAddOrRemoveColumnDefaultValue(): void
    {
        $column = new Column('test', Type::INT, 11);

        $this->assertNull($column->get(Column::DEFAULT));

        $column->default(5);

        $this->assertEquals(5, $column->get(Column::DEFAULT));

        $column->default(null);

        $this->assertNull($column->get(Column::DEFAULT));
    }

    public function testAddOrRemoveColumnAttribute(): void
    {
        $column = new Column('test', Type::INT, 11);

        $this->assertNull($column->get(Column::ATTRIBUTE));

        $column->attribute(Column::ATTR_BINARY);

        $this->assertEquals('BINARY', $column->get(Column::ATTRIBUTE));

        $column->attribute(Column::ATTR_UNSIGNED);

        $this->assertEquals('UNSIGNED', $column->get(Column::ATTRIBUTE));

        $column->attribute(null);

        $this->assertNull($column->get(Column::ATTRIBUTE));
    }

    public function testAddOrRemoveColumnComment(): void
    {
        $column = new Column('test', Type::INT, 11);

        $this->assertNull($column->get(Column::COMMENT));

        $column->comment('This is test column');

        $this->assertEquals('This is test column', $column->get(Column::COMMENT));

        $column->comment(null);

        $this->assertNull($column->get(Column::COMMENT));
    }

    public function testAfterColumn(): void
    {
        $column = new Column('test', Type::INT, 11);

        $column->after('prev');

        $this->assertEquals('prev', $column->get(Column::AFTER));
    }
}
