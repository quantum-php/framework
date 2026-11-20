<?php

namespace Quantum\Tests\Unit\Database\Adapters\Idiorm\Statements;

use Quantum\Tests\Unit\Database\Adapters\Idiorm\IdiormDbalTestCase;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use Quantum\Database\Adapters\Idiorm\IdiormDbal;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class QueryIdiormTest extends IdiormDbalTestCase
{
    public function testIdiormExecute(): void
    {
        $eventModel = new IdiormDbal('events');

        $event = $eventModel->findOne(1);

        $this->assertEquals('Dance', $event->prop('title'));

        $eventModel->execute('UPDATE events SET title=:title WHERE id=:id', ['title' => 'Singing', 'id' => 1]);

        $event = $eventModel->findOne(1);

        $this->assertEquals('Singing', $event->prop('title'));
    }

    public function testIdiormQuery(): void
    {
        $events = IdiormDbal::query(
            'SELECT * FROM events WHERE started_at BETWEEN :date_from AND :date_to ORDER BY started_at DESC',
            [
                'date_from' => '2035-02-14 10:15:12',
                'date_to' => '2045-02-14 10:15:12',
            ]
        );

        $this->assertEquals('Film', $events[0]['title']);

        $this->assertEquals('Ireland', $events[0]['country']);

        $this->assertEquals('2040-02-14 10:15:12', $events[0]['started_at']);
    }

    /** Works only if debug set to true */
    public function testIdiormLastQuery(): void
    {
        IdiormDbal::connect(['driver' => 'sqlite', 'database' => ':memory:']);

        $eventModel = new IdiormDbal('events');

        $eventModel->criteria('country', '=', 'Ireland')->get();

        $this->assertEquals("SELECT * FROM `events` WHERE `country` = 'Ireland'", IdiormDbal::lastQuery());
    }

    public function testIdiormLastStatement(): void
    {
        IdiormDbal::connect(['driver' => 'sqlite', 'database' => ':memory:']);

        $eventModel = new IdiormDbal('events');

        $eventModel->criteria('country', '=', 'Ireland')->get();

        $this->assertEquals('SELECT * FROM `events` WHERE `country` = ?', $eventModel::lastStatement()->queryString);
    }

    public function testIdiormQueryLog(): void
    {
        $eventModel = new IdiormDbal('events');

        $eventModel->criteria('country', '=', 'Ireland')->get();

        $userModel = new IdiormDbal('users');

        $userModel->get();

        $this->assertIsArray($eventModel::queryLog());

        $this->assertGreaterThan(0, count($eventModel::queryLog()));
    }
}
