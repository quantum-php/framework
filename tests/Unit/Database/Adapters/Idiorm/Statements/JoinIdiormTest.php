<?php

namespace Quantum\Tests\Unit\Database\Adapters\Idiorm\Statements;

use Quantum\Tests\Unit\Database\Adapters\Idiorm\IdiormDbalTestCase;
use Quantum\Tests\_root\shared\Models\TestUserProfessionModel;
use Quantum\Tests\_root\shared\Models\TestUserMeetingModel;
use Quantum\Tests\_root\shared\Models\TestUserEventModel;
use Quantum\Tests\_root\shared\Models\TestProfileModel;
use Quantum\Tests\_root\shared\Models\TestTicketModel;
use Quantum\Tests\_root\shared\Models\TestNotesModel;
use Quantum\Tests\_root\shared\Models\TestEventModel;
use Quantum\Tests\_root\shared\Models\TestUserModel;
use Quantum\Database\Adapters\Idiorm\IdiormDbal;
use Quantum\Model\Exceptions\ModelException;
use Quantum\Database\Enums\Relation;
use Quantum\Model\Factories\ModelFactory;
use Quantum\Model\ModelCollection;
use Quantum\Model\DbModel;

class BrokenProfileMissingTypeModel extends DbModel
{
    public string $table = 'profiles';

    public function relations(): array
    {
        return [
            TestUserModel::class => [
                // 'type' intentionally missing
                'foreign_key' => 'user_id',
                'local_key' => 'id',
            ],
        ];
    }
}

class BrokenProfileUnsupportedRelationModel extends DbModel
{
    public string $table = 'profiles';

    public function relations(): array
    {
        return [
            TestUserModel::class => [
                'type' => 'SIDEWAYS',
                'foreign_key' => 'user_id',
                'local_key' => 'id',
            ],
        ];
    }
}

class BrokenProfileBelongsToManyRelationModel extends DbModel
{
    public string $table = 'profiles';

    public function relations(): array
    {
        return [
            TestUserModel::class => [
                'type' => Relation::BELONGS_TO_MANY,
                'foreign_key' => 'user_id',
                'local_key' => 'id',
            ],
        ];
    }
}

class JoinIdiormTest extends IdiormDbalTestCase
{
    public function testIdiormJoinAndInnerJoin(): void
    {
        $userModel = new IdiormDbal('users');

        $events = $userModel->join('user_events', ['user_events.user_id', '=', 'users.id'])->get();

        $this->assertCount(6, $events);

        $this->assertEquals(1, $events[0]->prop('event_id'));

        $userModel = new IdiormDbal('users');

        $events = $userModel->innerJoin('user_events', ['user_events.user_id', '=', 'users.id'])->get();

        $this->assertCount(6, $events);

        $this->assertEquals(1, $events[0]->prop('event_id'));
    }

    public function testIdiormMultipleJoins(): void
    {
        $userModel = new IdiormDbal('users');

        $result = $userModel->select('users.*', 'events.*')
            ->join('user_events', ['user_events.user_id', '=', 'users.id'])
            ->join('events', ['user_events.event_id', '=', 'events.id'])
            ->get();

        $this->assertCount(6, $result);

        $this->assertEquals('Dance', $result[0]->prop('title'));
    }

    public function testIdiormJoinWithCondition(): void
    {
        $userModel = new IdiormDbal('users');

        $result = $userModel->select('users.*', 'events.*')
            ->join('user_events', ['user_events.user_id', '=', 'users.id'])
            ->join('events', ['user_events.event_id', '=', 'events.id'])
            ->criteria('events.started_at', '>=', '2020-01-01')
            ->get();

        $this->assertCount(3, $result);
    }

    /** Right join can not be tested this time because the sqlite does not support it */
    public function testIdiormLeftJoinAndRightJoin(): void
    {
        $userModel = new IdiormDbal('user_events');

        $events = $userModel->innerJoin('events', ['user_events.event_id', '=', 'events.id'])->get();

        $this->assertCount(6, $events);

        $userModel = new IdiormDbal('user_events');

        $events = $userModel->leftJoin('events', ['user_events.event_id', '=', 'events.id'])->get();

        $this->assertCount(8, $events);

        $this->assertNull($events[count($events) - 1]->prop('id'));
    }

    public function testIdiormJoinToHasOne(): void
    {
        $userModel = ModelFactory::get(TestUserModel::class);
        $profileModel = ModelFactory::get(TestProfileModel::class);

        $users = $userModel
            ->joinTo($profileModel)
            ->get();

        $this->assertInstanceOf(ModelCollection::class, $users);

        $this->assertCount(2, $users);

        $user = $users->first()->asArray();

        $this->assertArrayHasKey('email', $user);

        $this->assertArrayHasKey('firstname', $user);

        $this->assertArrayHasKey('lastname', $user);

        $this->assertArrayHasKey('age', $user);

        $this->assertArrayHasKey('country', $user);

        $expectedQuery = 'SELECT * 
                            FROM `users` 
                                JOIN `profiles` ON `profiles`.`user_id` = `users`.`id`';

        $expectedQuery = preg_replace('/[\s\t]+/', ' ', preg_replace('/' . PHP_EOL . '+/', '', $expectedQuery));

        $this->assertEquals($expectedQuery, IdiormDbal::lastQuery());
    }

    public function testIdiormJoinToBelongsTo(): void
    {
        $userModel = ModelFactory::get(TestUserModel::class);
        $profileModel = ModelFactory::get(TestProfileModel::class);

        $profiles = $profileModel
            ->joinTo($userModel)
            ->get();

        $this->assertInstanceOf(ModelCollection::class, $profiles);

        $user = $profiles->first()->asArray();

        $this->assertArrayHasKey('email', $user);

        $this->assertArrayHasKey('firstname', $user);

        $this->assertArrayHasKey('lastname', $user);

        $this->assertArrayHasKey('age', $user);

        $this->assertArrayHasKey('country', $user);

        $expectedQuery = 'SELECT * 
                            FROM `profiles` 
                                JOIN `users` ON `users`.`id` = `profiles`.`user_id`';

        $expectedQuery = preg_replace('/[\s\t]+/', ' ', preg_replace('/' . PHP_EOL . '+/', '', $expectedQuery));

        $this->assertEquals($expectedQuery, IdiormDbal::lastQuery());

        $this->assertIsArray($user);
    }

    public function testIdiormJoinToFromSameTable(): void
    {
        $userModel = ModelFactory::get(TestUserModel::class);
        $userProfessionModel = ModelFactory::get(TestUserProfessionModel::class);
        $userMeetings = ModelFactory::get(TestUserMeetingModel::class);

        $users = $userModel
            ->joinTo($userProfessionModel, false)
            ->joinTo($userMeetings)
            ->get();

        $this->assertInstanceOf(ModelCollection::class, $users);

        $user = $users->first()->asArray();

        $this->assertArrayHasKey('email', $user);

        $this->assertArrayHasKey('user_id', $user);

        $this->assertArrayHasKey('title', $user);

        $this->assertArrayHasKey('start_date', $user);

        $this->assertInstanceOf(ModelCollection::class, $users);

        $expectedQuery = 'SELECT * 
                        FROM `users` 
                            JOIN `user_professions` ON `user_professions`.`user_id` = `users`.`id` 
                            JOIN `user_meetings` ON `user_meetings`.`user_id` = `users`.`id`';

        $expectedQuery = preg_replace('/[\s\t]+/', ' ', preg_replace('/' . PHP_EOL . '+/', '', $expectedQuery));

        $this->assertEquals($expectedQuery, IdiormDbal::lastQuery());
    }

    public function testIdiormJoinToWithTableSwitch(): void
    {
        $userModel = ModelFactory::get(TestUserModel::class);
        $meetingModel = ModelFactory::get(TestUserMeetingModel::class);
        $ticketModel = ModelFactory::get(TestTicketModel::class);
        $noteModel = ModelFactory::get(TestNotesModel::class);

        $users = $userModel
            ->joinTo($meetingModel)
            ->joinTo($ticketModel)
            ->joinTo($noteModel)
            ->groupBy('users.id')
            ->get();

        $this->assertInstanceOf(ModelCollection::class, $users);

        $this->assertCount(2, $users);

        $user = $users->first()->asArray();

        $this->assertArrayHasKey('email', $user);

        $this->assertArrayHasKey('user_id', $user);

        $this->assertArrayHasKey('title', $user);

        $this->assertArrayHasKey('start_date', $user);

        $this->assertArrayHasKey('meeting_id', $user);

        $this->assertArrayHasKey('type', $user);

        $this->assertArrayHasKey('ticket_id', $user);

        $this->assertArrayHasKey('note', $user);

        $expectedQuery = 'SELECT * 
                    FROM `users` 
                        JOIN `user_meetings` ON `user_meetings`.`user_id` = `users`.`id` 
                        JOIN `tickets` ON `tickets`.`meeting_id` = `user_meetings`.`id`
                        JOIN `notes` ON `notes`.`ticket_id` = `tickets`.`id`
                    GROUP BY `users`.`id`';

        $expectedQuery = preg_replace('/[\s\t]+/', ' ', preg_replace('/' . PHP_EOL . '+/', '', $expectedQuery));

        $this->assertEquals($expectedQuery, IdiormDbal::lastQuery());
    }

    public function testIdiormJoiningViaPivotModel(): void
    {
        $userModel = ModelFactory::get(TestUserModel::class);
        $userEventModel = ModelFactory::get(TestUserEventModel::class);
        $eventModel = ModelFactory::get(TestEventModel::class);

        $users = $userModel
            ->joinTo($userEventModel)
            ->joinTo($eventModel)
            ->get();

        $this->assertNotEmpty($users);

        $user = $users->first();

        $this->assertInstanceOf(TestUserModel::class, $user);

        $userRecord = $users->first()->asArray();

        $this->assertArrayHasKey('email', $userRecord);

        $this->assertArrayHasKey('user_id', $userRecord);

        $this->assertArrayHasKey('event_id', $userRecord);

        $this->assertArrayHasKey('confirmed', $userRecord);

        $this->assertArrayHasKey('title', $userRecord);

        $this->assertArrayHasKey('country', $userRecord);

        $this->assertArrayHasKey('started_at', $userRecord);

        $expectedQuery = 'SELECT * 
                            FROM `users`
                                JOIN `user_events` ON `user_events`.`user_id` = `users`.`id` 
                                JOIN `events` ON `events`.`id` = `user_events`.`event_id`';

        $expectedQuery = preg_replace('/[\s\t]+/', ' ', preg_replace('/' . PHP_EOL . '+/', '', $expectedQuery));

        $this->assertEquals($expectedQuery, IdiormDbal::lastQuery());
    }

    public function testIdiormJoinToWithCriteria(): void
    {
        $userModel = ModelFactory::get(TestUserModel::class);
        $profileModel = ModelFactory::get(TestProfileModel::class);
        $userProfessionModel = ModelFactory::get(TestUserProfessionModel::class);
        $meetingModel = ModelFactory::get(TestUserMeetingModel::class);
        $ticketModel = ModelFactory::get(TestTicketModel::class);

        $users = $userModel
            ->joinTo($profileModel, false)
            ->joinTo($userProfessionModel, false)
            ->joinTo($meetingModel)
            ->joinTo($ticketModel)
            ->criteria('email', 'LIKE', '%jane%')
            ->groupBy('users.id')
            ->get();

        $this->assertInstanceOf(ModelCollection::class, $users);

        $this->assertCount(1, $users);

        $expectedQuery = "SELECT * 
                            FROM `users` 
                                JOIN `profiles` ON `profiles`.`user_id` = `users`.`id` 
                                JOIN `user_professions` ON `user_professions`.`user_id` = `users`.`id` 
                                JOIN `user_meetings` ON `user_meetings`.`user_id` = `users`.`id` 
                                JOIN `tickets` ON `tickets`.`meeting_id` = `user_meetings`.`id` 
                            WHERE `users`.`email` 
                                      LIKE '%jane%' 
                            GROUP BY `users`.`id`";

        $expectedQuery = preg_replace('/[\s\t]+/', ' ', preg_replace('/' . PHP_EOL . '+/', '', $expectedQuery));

        $this->assertEquals($expectedQuery, IdiormDbal::lastQuery());
    }

    public function testIdiormSelectFieldsAtJoin(): void
    {
        $userModel = ModelFactory::get(TestUserModel::class);
        $profileModel = ModelFactory::get(TestProfileModel::class);
        $userProfessionModel = ModelFactory::get(TestUserProfessionModel::class);

        $users = $userModel
            ->joinTo($profileModel, false)
            ->joinTo($userProfessionModel)
            ->select(
                'firstname',
                'lastname',
                'age',
                'country',
                ['user_professions.title' => 'profession']
            )
            ->orderBy('age', 'desc')
            ->get();

        $this->assertInstanceOf(ModelCollection::class, $users);

        $this->assertEquals('Writer', $users->first()->prop('profession'));

        $expectedQuery = 'SELECT 
                            `firstname`, 
                            `lastname`, 
                            `age`, 
                            `country`, 
                            `user_professions`.`title` AS `profession` 
                        FROM `users` 
                            JOIN `profiles` ON `profiles`.`user_id` = `users`.`id` 
                            JOIN `user_professions` ON `user_professions`.`user_id` = `users`.`id` 
                        ORDER BY `age` DESC';

        $expectedQuery = preg_replace('/[\s\t]+/', ' ', preg_replace('/' . PHP_EOL . '+/', '', $expectedQuery));

        $this->assertEquals($expectedQuery, IdiormDbal::lastQuery());
    }

    public function testIdiormThrowsExceptionForWrongRelation(): void
    {
        $this->expectException(ModelException::class);

        $this->expectExceptionMessage('The model `' . TestEventModel::class . '` does not define relation with `' . TestNotesModel::class . '`');

        $eventModel = ModelFactory::get(TestEventModel::class);

        $ticketModel = ModelFactory::get(TestNotesModel::class);

        $eventModel->joinTo($ticketModel)->get();
    }

    public function testIdiormJoinThrowsExceptionWhenRelationKeysMissing(): void
    {
        $this->expectException(ModelException::class);
        $this->expectExceptionMessage('Relation type is missing for model `' . BrokenProfileMissingTypeModel::class . '`');

        ModelFactory::get(BrokenProfileMissingTypeModel::class)
            ->joinTo(ModelFactory::get(TestUserModel::class))
            ->get();
    }

    public function testIdiormJoinThrowsExceptionForBelongsToManyRelationType(): void
    {
        $this->expectException(ModelException::class);
        $this->expectExceptionMessage('Relation type `belongsToMany` is not supported');

        ModelFactory::get(BrokenProfileBelongsToManyRelationModel::class)
            ->joinTo(ModelFactory::get(TestUserModel::class))
            ->get();
    }

    public function testIdiormJoinThrowsExceptionForUnsupportedRelationType(): void
    {
        $this->expectException(ModelException::class);
        $this->expectExceptionMessage('Relation type `SIDEWAYS` is not supported');

        ModelFactory::get(BrokenProfileUnsupportedRelationModel::class)
            ->joinTo(ModelFactory::get(TestUserModel::class))
            ->get();
    }
}
