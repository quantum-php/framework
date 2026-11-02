<?php

namespace Quantum\Tests\Unit\Database\Adapters\Sleekdb\Statements;

use Quantum\Tests\Unit\Database\Adapters\Sleekdb\SleekDbalTestCase;
use Quantum\Tests\_root\shared\Models\TestUserProfessionModel;
use Quantum\Tests\_root\shared\Models\TestUserMeetingModel;
use Quantum\Tests\_root\shared\Models\TestUserEventModel;
use Quantum\Tests\_root\shared\Models\TestProfileModel;
use Quantum\Tests\_root\shared\Models\TestTicketModel;
use Quantum\Tests\_root\shared\Models\TestEventModel;
use Quantum\Tests\_root\shared\Models\TestNotesModel;
use Quantum\Tests\_root\shared\Models\TestUserModel;
use Quantum\Model\Exceptions\ModelException;
use Quantum\Model\Factories\ModelFactory;
use Quantum\Database\Enums\Relation;
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

class JoinSleekTest extends SleekDbalTestCase
{
    public function testSleekJoinToHasOne(): void
    {
        $userModel = ModelFactory::get(TestUserModel::class);
        $profileModel = ModelFactory::get(TestProfileModel::class);

        $users = $userModel
            ->joinTo($profileModel)
            ->get();

        $this->assertInstanceOf(ModelCollection::class, $users);

        $profile = $users->first()->prop('profiles')[0];

        $this->assertIsArray($profile);

        $this->assertArrayHasKey('firstname', $profile);

        $this->assertArrayHasKey('lastname', $profile);

        $this->assertArrayHasKey('age', $profile);

        $this->assertArrayHasKey('country', $profile);
    }

    public function testSleekJoinToBelongsTo(): void
    {
        $userModel = ModelFactory::get(TestUserModel::class);
        $profileModel = ModelFactory::get(TestProfileModel::class);

        $profiles = $profileModel
            ->joinTo($userModel)
            ->get();

        $this->assertInstanceOf(ModelCollection::class, $profiles);

        $user = $profiles->first()->prop('users')[0];

        $this->assertIsArray($user);

        $this->assertArrayHasKey('email', $user);

        $this->assertArrayHasKey('password', $user);

        $this->assertEquals(
            $profiles->first()->user_id,
            $user['id']
        );
    }

    public function testSleekSameLevelJoinToHasMany(): void
    {
        $userModel = ModelFactory::get(TestUserModel::class);

        $userProfessionModel = ModelFactory::get(TestUserProfessionModel::class);

        $userMeetings = ModelFactory::get(TestUserMeetingModel::class);

        $users = $userModel
            ->joinTo($userProfessionModel, false)
            ->joinTo($userMeetings)
            ->get();

        $this->assertInstanceOf(ModelCollection::class, $users);

        $this->assertIsArray($users->first()->prop('user_professions'));

        $this->assertIsArray($users->first()->prop('user_meetings'));
    }

    public function testSleekNestedLevelJoinToHasMany(): void
    {
        $userModel = ModelFactory::get(TestUserModel::class);

        $meetingModel = ModelFactory::get(TestUserMeetingModel::class);

        $ticketModel = ModelFactory::get(TestTicketModel::class);

        $noteModel = ModelFactory::get(TestNotesModel::class);

        $users = $userModel
            ->joinTo($meetingModel)
            ->joinTo($ticketModel)
            ->joinTo($noteModel)
            ->get();

        $this->assertInstanceOf(ModelCollection::class, $users);

        $this->assertCount(2, $users);

        $this->assertIsArray($users->first()->prop('user_meetings')[0]);

        $this->assertArrayHasKey('tickets', $users->first()->prop('user_meetings')[0]);

        $this->assertIsArray($users->first()->prop('user_meetings')[0]['tickets'][0]);

        $this->assertArrayHasKey('notes', $users->first()->prop('user_meetings')[0]['tickets'][0]);
    }

    public function testSleekJoiningViaPivotModel(): void
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

        $this->assertArrayHasKey('user_events', $userRecord);

        $pivotRecord = $user->prop('user_events')[0];

        $this->assertIsArray($pivotRecord);

        $this->assertArrayHasKey('user_id', $pivotRecord);

        $this->assertArrayHasKey('event_id', $pivotRecord);

        $this->assertArrayHasKey('confirmed', $pivotRecord);

        $this->assertArrayHasKey('events', $pivotRecord);

        $eventRecord = $pivotRecord['events'][0];

        $this->assertIsArray($eventRecord);

        $this->assertArrayHasKey('title', $eventRecord);

        $this->assertArrayHasKey('country', $eventRecord);

        $this->assertArrayHasKey('started_at', $eventRecord);
    }

    public function testSleekMixedLevelJoinToWithCriteria(): void
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
            ->get();

        $this->assertInstanceOf(ModelCollection::class, $users);

        $this->assertCount(1, $users);

        $this->assertEquals('Jane', $users->first()->prop('profiles')[0]['firstname']);

        $this->assertIsArray($users->first()->prop('user_professions'));

        $this->assertIsArray($users->first()->prop('user_meetings'));

        $this->assertEquals('Marketing', $users->first()->prop('user_meetings')[0]['title']);

        $this->assertArrayHasKey('tickets', $users->first()->prop('user_meetings')[0]);

        $this->assertIsArray($users->first()->prop('user_meetings')[0]['tickets']);
    }

    public function testSleekSelectFieldsAtJoin(): void
    {
        $userModel = ModelFactory::get(TestUserModel::class);
        $userProfessionModel = ModelFactory::get(TestUserProfessionModel::class);

        $users = $userModel->joinTo($userProfessionModel)
            ->select('firstname', 'lastname', 'age', 'country', ['user_professions.title' => 'profession'])
            ->orderBy('age', 'desc')
            ->get();

        $this->assertEquals('Writer', $users->first()->profession);
    }

    public function testSleekJoinThrowsExceptionForWrongRelation(): void
    {
        $this->expectException(ModelException::class);

        $this->expectExceptionMessage('The model `' . TestEventModel::class . '` does not define relation with `' . TestNotesModel::class . '`');

        $eventModel = ModelFactory::get(TestEventModel::class);

        $noteModel = ModelFactory::get(TestNotesModel::class);

        $eventModel->joinTo($noteModel)->get();
    }

    public function testSleekJoinThrowsExceptionForMissingForeignKey(): void
    {
        $this->expectException(ModelException::class);
        $this->expectExceptionMessage('Foreign key `user_id` is missing in model `' . TestProfileModel::class . '`');

        $profileModel = ModelFactory::get(TestProfileModel::class);

        $profileModel->create();
        $profileModel->firstname = 'Test';
        $profileModel->lastname = 'User';
        $profileModel->age = 30;
        $profileModel->country = 'Testland';
        $profileModel->user_id = null;
        $profileModel->save();

        $profileModel->criteria('firstname', '=', 'Test')
            ->joinTo(ModelFactory::get(TestUserModel::class))
            ->get();
    }

    public function testSleekJoinThrowsExceptionWhenRelationKeysMissing(): void
    {
        $this->expectException(ModelException::class);
        $this->expectExceptionMessage('Relation type is missing for model `' . BrokenProfileMissingTypeModel::class . '`');

        ModelFactory::get(BrokenProfileMissingTypeModel::class)
            ->joinTo(ModelFactory::get(TestUserModel::class))
            ->get();
    }

    public function testSleekJoinThrowsExceptionForBelongsToManyRelationType(): void
    {
        $this->expectException(ModelException::class);
        $this->expectExceptionMessage('Relation type `belongsToMany` is not supported');

        ModelFactory::get(BrokenProfileBelongsToManyRelationModel::class)
            ->joinTo(ModelFactory::get(TestUserModel::class))
            ->get();
    }

    public function testSleekJoinThrowsExceptionForUnsupportedRelationType(): void
    {
        $this->expectException(ModelException::class);
        $this->expectExceptionMessage('Relation type `SIDEWAYS` is not supported');

        ModelFactory::get(BrokenProfileUnsupportedRelationModel::class)
            ->joinTo(ModelFactory::get(TestUserModel::class))
            ->get();
    }

    public function testSleekRelatedCriteriaFiltersJoinedLevel(): void
    {
        $userModel = ModelFactory::get(TestUserModel::class);
        $profileModel = ModelFactory::get(TestProfileModel::class);

        $users = $userModel
            ->joinTo($profileModel)
            ->criteria('profiles.firstname', '=', 'Jane')
            ->get();

        $this->assertCount(1, $users);
        $this->assertEquals('jane@test.com', $users->first()->email);
        $this->assertEquals('Jane', $users->first()->prop('profiles')[0]['firstname']);
    }

    public function testSleekDeepRelatedCriteriaFiltersNestedJoinLevel(): void
    {
        $userModel = ModelFactory::get(TestUserModel::class);
        $meetingModel = ModelFactory::get(TestUserMeetingModel::class);
        $ticketModel = ModelFactory::get(TestTicketModel::class);

        $users = $userModel
            ->joinTo($meetingModel)
            ->joinTo($ticketModel)
            ->criteria('user_meetings.tickets.type', '=', 'regular')
            ->get();

        $this->assertCount(1, $users);
        $this->assertEquals('john@test.com', $users->first()->email);
    }

    public function testSleekRootAndRelatedCriteriaWithAnd(): void
    {
        $userModel = ModelFactory::get(TestUserModel::class);
        $profileModel = ModelFactory::get(TestProfileModel::class);

        $users = $userModel
            ->joinTo($profileModel)
            ->criteria('email', 'LIKE', '%jane%')
            ->criteria('profiles.country', '=', 'England')
            ->get();

        $this->assertCount(1, $users);
        $this->assertEquals('jane@test.com', $users->first()->email);
    }

    public function testSleekRelatedCriteriaRejectsOrCombination(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('SleekDB related-model criterias do not support OR combinations');

        $userModel = ModelFactory::get(TestUserModel::class);
        $profileModel = ModelFactory::get(TestProfileModel::class);

        $userModel
            ->joinTo($profileModel)
            ->criterias(
                ['profiles.firstname', '=', 'Jane'],
                [['email', 'LIKE', '%john%'], ['email', 'LIKE', '%jane%']]
            )
            ->get();
    }

    public function testSleekInvalidRelatedCriteriaPathReturnsNoMatches(): void
    {
        $userModel = ModelFactory::get(TestUserModel::class);
        $profileModel = ModelFactory::get(TestProfileModel::class);

        $users = $userModel
            ->joinTo($profileModel)
            ->criteria('unknown_relation.firstname', '=', 'Jane')
            ->get();

        $this->assertInstanceOf(ModelCollection::class, $users);
        $this->assertCount(0, $users);

        $usersWithoutBadCriterion = ModelFactory::get(TestUserModel::class)->get();
        $this->assertGreaterThan(0, $usersWithoutBadCriterion->count());
    }

    public function testSleekRelatedCriteriaWithRootSelectKeepsMatchedRow(): void
    {
        $userModel = ModelFactory::get(TestUserModel::class);
        $profileModel = ModelFactory::get(TestProfileModel::class);

        $users = $userModel
            ->joinTo($profileModel)
            ->criteria('profiles.firstname', '=', 'Jane')
            ->select('email')
            ->get();

        $this->assertGreaterThan(0, $users->count());
        $this->assertIsString($users->first()->email);
        $this->assertNull($users->first()->prop('profiles'));
    }

    public function testSleekRelatedCriteriaWithAliasedSelectKeepsMatchedRow(): void
    {
        $userModel = ModelFactory::get(TestUserModel::class);
        $profileModel = ModelFactory::get(TestProfileModel::class);

        $users = $userModel
            ->joinTo($profileModel)
            ->criteria('profiles.firstname', '=', 'Jane')
            ->select(['email' => 'user_email'])
            ->get();

        $this->assertGreaterThan(0, $users->count());
        $this->assertIsString($users->first()->user_email);
        $this->assertNull($users->first()->prop('profiles'));
    }
}
