<?php

namespace Quantum\Tests\Unit\Database\Schema;

use Quantum\Database\Enums\TableAction;
use Quantum\Database\Schemas\Column;
use Quantum\Database\Schemas\Table;
use Quantum\Database\Enums\Type;
use Quantum\Database\Enums\Key;
use PHPUnit\Framework\TestCase;
use TypeError;
use Mockery;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class TableTest extends TestCase
{
    public function setUp(): void
    {
        $db = Mockery::mock('overload:Quantum\Database\Database');

        $db->shouldReceive('execute')->andReturn(true);
    }

    public function testSetActionAndGetQuery(): void
    {
        $table = new Table('test');
        $table->setAction(TableAction::RENAME, ['newName' => 'general']);

        $expectedSql = 'RENAME TABLE `test` TO `general`;';

        $this->assertEquals($expectedSql, $table->getSql());
    }

    public function testGetSqlIsEmptyWhenNoActionIsSet(): void
    {
        $this->assertSame('', (new Table('test'))->getSql());
    }

    public function testSetActionRejectsTheActionAsNumber(): void
    {
        $this->expectException(TypeError::class);

        (new Table('test'))->setAction(2);
    }

    public function testTableConstructor(): void
    {
        $this->assertInstanceOf(Table::class, new Table('test'));
    }

    public function testAddColumn(): void
    {
        $table = new Table('test');
        $table->setAction(TableAction::CREATE);
        $table->addColumn('id', Type::INT, 11);

        $expectedSql = 'CREATE TABLE `test` (`id` INT(11) NOT NULL);';

        $this->assertEquals($expectedSql, $table->getSql());
    }

    public function testAddColumnWithAttributes(): void
    {
        $table = new Table('test');
        $table->setAction(TableAction::CREATE);
        $table->addColumn('id', Type::INT, 11)->autoIncrement()->attribute(Column::ATTR_UNSIGNED);

        $expectedSql = 'CREATE TABLE `test` (`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT, PRIMARY KEY (`id`));';

        $this->assertEquals($expectedSql, $table->getSql());
    }

    public function testAddColumnWithIndexs(): void
    {
        $table = new Table('test');
        $table->setAction(TableAction::CREATE);
        $table->addColumn('profile_id', Type::INT, 11)->index();
        $table->addColumn('username', Type::INT, 11)->unique();
        $table->addColumn('email', Type::INT, 11)->unique();

        $expectedSql = 'CREATE TABLE `test` (`profile_id` INT(11) NOT NULL, `username` INT(11) NOT NULL, `email` INT(11) NOT NULL, INDEX (`profile_id`), UNIQUE (`username`), UNIQUE (`email`));';

        $this->assertEquals($expectedSql, $table->getSql());
    }

    public function testModifyColumn(): void
    {
        $table = new Table('test');
        $table->setAction(TableAction::ALTER);
        $table->modifyColumn('name', Type::VARCHAR, 50);

        $expectedSql = 'ALTER TABLE `test` MODIFY COLUMN `name` VARCHAR(50) NOT NULL;';

        $this->assertEquals($expectedSql, $table->getSql());
    }

    public function testModifyColumnWithAttributes(): void
    {
        $table = new Table('test');
        $table->setAction(TableAction::ALTER);
        $table->modifyColumn('name', Type::VARCHAR, 100)->default('New user')->comment('User nikname');

        $expectedSql = 'ALTER TABLE `test` MODIFY COLUMN `name` VARCHAR(100) NOT NULL DEFAULT \'New user\' COMMENT \'User nikname\';';

        $this->assertEquals($expectedSql, $table->getSql());
    }

    public function testAddColumnAndModifyColumn(): void
    {
        $table = new Table('test');
        $table->setAction(TableAction::ALTER);
        $table->addColumn('surename', Type::VARCHAR, 50);
        $table->modifyColumn('profession', Type::ENUM, ['plumber', 'driver', 'security'])->default('driver');

        $expectedSql = 'ALTER TABLE `test` ADD COLUMN `surename` VARCHAR(50) NOT NULL, MODIFY COLUMN `profession` ENUM(\'plumber\', \'driver\', \'security\') NOT NULL DEFAULT \'driver\';';

        $this->assertEquals($expectedSql, $table->getSql());
    }

    public function testRenameColumn(): void
    {
        $table = new Table('test');
        $table->setAction(TableAction::ALTER);
        $table->renameColumn('courses', 'lessons');

        $expectedSql = 'ALTER TABLE `test` RENAME COLUMN `courses` TO `lessons`;';

        $this->assertEquals($expectedSql, $table->getSql());
    }

    public function testDropColumn(): void
    {
        $table = new Table('test');
        $table->setAction(TableAction::ALTER);
        $table->dropColumn('courses');

        $expectedSql = 'ALTER TABLE `test` DROP COLUMN `courses`;';

        $this->assertEquals($expectedSql, $table->getSql());
    }

    public function testAddIndex(): void
    {
        $table = new Table('test');
        $table->setAction(TableAction::ALTER);
        $table->addIndex('name', Key::INDEX);

        $expectedSql = 'ALTER TABLE `test` ADD INDEX (`name`);';

        $this->assertEquals($expectedSql, $table->getSql());
    }

    /**
     * @dataProvider indexTypeProvider
     */
    public function testAddIndexOfEveryKey(Key $key, string $expectedSql): void
    {
        $table = new Table('test');
        $table->setAction(TableAction::ALTER);
        $table->addIndex('name', $key, 'idx_name');

        $this->assertEquals($expectedSql, $table->getSql());
    }

    /**
     * @return array<string, array{0: Key, 1: string}>
     */
    public static function indexTypeProvider(): array
    {
        return [
            'primary' => [Key::PRIMARY, 'ALTER TABLE `test` ADD PRIMARY KEY (`name`);'],
            'index' => [Key::INDEX, 'ALTER TABLE `test` ADD INDEX `idx_name` (`name`);'],
            'unique' => [Key::UNIQUE, 'ALTER TABLE `test` ADD UNIQUE `idx_name` (`name`);'],
            'fulltext' => [Key::FULLTEXT, 'ALTER TABLE `test` ADD FULLTEXT `idx_name` (`name`);'],
            'spatial' => [Key::SPATIAL, 'ALTER TABLE `test` ADD SPATIAL `idx_name` (`name`);'],
        ];
    }

    public function testAddIndexRejectsTheIndexTypeAsText(): void
    {
        $table = new Table('test');
        $table->setAction(TableAction::ALTER);

        $this->expectException(TypeError::class);

        $table->addIndex('name', 'unique', 'idx_name');
    }

    public function testAddIndexWithName(): void
    {
        $table = new Table('test');
        $table->setAction(TableAction::ALTER);
        $table->addIndex('name', Key::UNIQUE, 'idx_name');

        $expectedSql = 'ALTER TABLE `test` ADD UNIQUE `idx_name` (`name`);';

        $this->assertEquals($expectedSql, $table->getSql());
    }

    public function testAddingMultipleIndexes(): void
    {
        $table = new Table('test');
        $table->setAction(TableAction::ALTER);
        $table->addIndex('email', Key::UNIQUE, 'idx_email');
        $table->addIndex('username', Key::INDEX, 'idx_username');

        $expectedSql = 'ALTER TABLE `test` ADD INDEX `idx_username` (`username`), ADD UNIQUE `idx_email` (`email`);';

        $this->assertEquals($expectedSql, $table->getSql());
    }

    public function testDropIndex(): void
    {
        $table = new Table('test');
        $table->setAction(TableAction::ALTER);
        $table->dropIndex('idx_name');

        $expectedSql = 'ALTER TABLE `test` DROP INDEX `idx_name`;';

        $this->assertEquals($expectedSql, $table->getSql());
    }

    public function testAfterColumn(): void
    {
        $table = new Table('test');
        $table->setAction(TableAction::ALTER);
        $table->addColumn('profile_id', Type::INT, 11)->after('id');

        $expectedSql = 'ALTER TABLE `test` ADD COLUMN `profile_id` INT(11) NOT NULL AFTER `id`;';

        $this->assertEquals($expectedSql, $table->getSql());
    }
}
