<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\ObjectivesTable;
use App\Test\Support\TestDatabase;
use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class CoreValidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        TestDatabase::reset();
    }

    public static function sensitiveCases(): iterable
    {
        foreach (['maxFatigueThreshold' => ['0', '-1', '10000.01', '0.001', 'abc', 'INF', '1e2', []], 'rpeLimit' => ['4.9', '9.6', '8.55'], 'incPercentage' => ['0.49', '50.01'], 'stagnationPenalty' => ['0', '51'], 'deloadPercentage' => ['-1', '100']] as $field => $values) {
            foreach ($values as $index => $value) {
                yield "Archetypes {$field} {$index}" => ['Archetypes', $field, $value];
            }
        }
        foreach (['targetRepMin' => ['0', '51', '2.5', []], 'targetRepMax' => ['0', '51', '3.0'], 'minSuccessRate' => ['49.99', '100.01', '80.001']] as $field => $values) {
            foreach ($values as $index => $value) {
                yield "Objectives {$field} {$index}" => ['Objectives', $field, $value];
            }
        }
        foreach (['fatigueCoefficient' => ['0.10', '5.00', '0', '-1', '1.555', 'NaN', []], 'muscleGroup' => ['Cuello', 'pecho', [], '']] as $field => $values) {
            foreach ($values as $index => $value) {
                yield "Exercises {$field} {$index}" => ['Exercises', $field, $value];
            }
        }
        foreach (['rpe' => ['1.0', '10.0', '0', '11', '7.55', []], 'setsCompleted' => ['0', '-1', '2.5'], 'repsAchieved' => ['-1', '1.1'], 'weightUsed' => ['-1', '10000.00', '0.001'], 'routineExerciseId' => ['0', '-1', 'abc', []]] as $field => $values) {
            foreach ($values as $index => $value) {
                yield "WorkoutLogs {$field} {$index}" => ['WorkoutLogs', $field, $value];
            }
        }
        foreach (['currentWeight' => ['-1', '10000.00', '1.234'], 'projectedWeight' => ['-1', '10000.00'], 'targetSets' => ['0', '-1'], 'targetReps' => ['0', '2.3']] as $field => $values) {
            foreach ($values as $index => $value) {
                yield "RoutineExercises {$field} {$index}" => ['RoutineExercises', $field, $value];
            }
        }
        foreach (['email' => ['invalido', []], 'password' => ['corta', '', str_repeat('á', 37)], 'birthDate' => ['2026-01-01', '2100-01-01', '2001-02-29', 'texto', []]] as $field => $values) {
            foreach ($values as $index => $value) {
                yield "Users {$field} {$index}" => ['Users', $field, $value];
            }
        }
    }

    #[DataProvider('sensitiveCases')]
    public function testRejectsUnsafeInputs(string $model, string $field, mixed $value): void
    {
        $table = TableRegistry::getTableLocator()->get($model);
        $entity = $table->newEntity(array_replace(TestDatabase::valid($model), [$field => $value]));
        $this->assertNotEmpty($entity->getError($field), $model . '.' . $field);
    }

    public static function models(): iterable
    {
        foreach (['Roles', 'Archetypes', 'Users', 'Objectives', 'Exercises', 'Routines', 'RoutineExercises', 'WorkoutLogs'] as $model) {
            yield $model => [$model];
        }
    }

    #[DataProvider('models')]
    public function testCreatesEachModelWithValidData(string $model): void
    {
        $table = TableRegistry::getTableLocator()->get($model);
        $entity = $table->newEntity(TestDatabase::valid($model));
        $this->assertSame([], $entity->getErrors());
        $this->assertNotFalse($table->save($entity), json_encode($entity->getErrors()));
        $this->assertGreaterThan(0, $entity->id);
    }

    public function testRejectsIncoherentRepetitionRangeAndPartialUpdate(): void
    {
        $table = TableRegistry::getTableLocator()->get('Objectives');
        $entity = $table->newEntity(array_replace(TestDatabase::valid('Objectives'), ['targetRepMin' => 12, 'targetRepMax' => 8]));
        $this->assertContains(ObjectivesTable::REP_ERROR, $entity->getError('targetRepMax'));
        $this->assertFalse($table->save($entity));
        $existing = $table->patchEntity($table->get(2), ['targetRepMin' => 20]);
        $this->assertFalse($table->save($existing));
        $this->assertContains(ObjectivesTable::REP_ERROR, $existing->getError('targetRepMax'));
    }

    public function testAcceptsMathematicalBoundariesAndZeroPerformance(): void
    {
        $data = array_replace(TestDatabase::valid('Archetypes'), ['incPercentage' => '0.50', 'stagnationPenalty' => '50.00', 'maxFatigueThreshold' => '10000.00', 'rpeLimit' => '9.5']);
        $this->assertSame([], TableRegistry::getTableLocator()->get('Archetypes')->newEntity($data)->getErrors());
        $data = array_replace(TestDatabase::valid('Objectives'), ['targetRepMin' => '1', 'targetRepMax' => '50', 'minSuccessRate' => '100.00']);
        $this->assertSame([], TableRegistry::getTableLocator()->get('Objectives')->newEntity($data)->getErrors());
        $data = array_replace(TestDatabase::valid('WorkoutLogs'), ['repsAchieved' => '0', 'weightUsed' => '0.00', 'rpe' => '9.9']);
        $table = TableRegistry::getTableLocator()->get('WorkoutLogs');
        $this->assertNotFalse($table->save($table->newEntity($data)));
    }

    public function testMinimumAgeOnExactBirthday(): void
    {
        $table = TableRegistry::getTableLocator()->get('Users');
        $cutoff = Date::today()->subYears(12);
        $data = array_replace(TestDatabase::valid('Users'), ['birthDate' => $cutoff->format('Y-m-d')]);
        $this->assertSame([], $table->newEntity($data)->getError('birthDate'));
        $data['birthDate'] = $cutoff->addDays(1)->format('Y-m-d');
        $this->assertNotEmpty($table->newEntity($data)->getError('birthDate'));
    }

    public function testHashesPasswordAndPreservesItWhenEditingWithBlankPassword(): void
    {
        $table = TableRegistry::getTableLocator()->get('Users');
        $data = TestDatabase::valid('Users');
        $user = $table->newEntity($data);
        $this->assertTrue((new DefaultPasswordHasher())->check($data['password'], $user->password));
        $this->assertNotFalse($table->save($user));
        $hash = $user->password;
        $user = $table->patchEntity($user, ['password' => '', 'firstName' => 'Actualizado']);
        $this->assertNotFalse($table->save($user));
        $this->assertSame($hash, $table->get($user->id)->password);
        $this->assertArrayNotHasKey('password', $user->toArray());
    }

    public function testRejectsDuplicatesAndMissingForeignKeys(): void
    {
        foreach (['Roles' => ['code' => 'ADMIN'], 'Archetypes' => ['name' => 'Adaptación inicial'], 'Users' => ['email' => 'admin@atletalab.test'], 'Exercises' => ['code' => 'PRESS_BANCA']] as $model => $override) {
            $table = TableRegistry::getTableLocator()->get($model);
            $entity = $table->newEntity(array_replace(TestDatabase::valid($model), $override));
            $this->assertFalse($table->save($entity));
            $this->assertNotEmpty($entity->getErrors());
        }
        $table = TableRegistry::getTableLocator()->get('Users');
        $entity = $table->newEntity(array_replace(TestDatabase::valid('Users'), ['roleId' => 999, 'archetypeId' => 999]));
        $this->assertFalse($table->save($entity));
        $this->assertNotEmpty($entity->getError('roleId'));
        $this->assertNotEmpty($entity->getError('archetypeId'));
    }

    public function testNullableArchetypeIsAllowed(): void
    {
        $table = TableRegistry::getTableLocator()->get('Users');
        $entity = $table->newEntity(array_replace(TestDatabase::valid('Users'), ['archetypeId' => '']));
        $this->assertNotFalse($table->save($entity), json_encode($entity->getErrors()));
        $this->assertNull($table->get($entity->id)->archetypeId);
    }

    public function testRejectsSamePersonAndWrongRoutineRoles(): void
    {
        $table = TableRegistry::getTableLocator()->get('Routines');
        $entity = $table->newEntity(array_replace(TestDatabase::valid('Routines'), ['athleteId' => 4, 'trainerId' => 4]));
        $this->assertFalse($table->save($entity));
        $this->assertNotEmpty($entity->getError('trainerId'));
        $entity = $table->newEntity(array_replace(TestDatabase::valid('Routines'), ['athleteId' => 2, 'trainerId' => 4]));
        $this->assertFalse($table->save($entity));
        $this->assertNotEmpty($entity->getError('athleteId'));
    }

    public function testRestrictsReferencedCatalogDeletionAndCascadesRoutineDeletion(): void
    {
        $roles = TableRegistry::getTableLocator()->get('Roles');
        $this->assertFalse($roles->delete($roles->get(3)));
        $routines = TableRegistry::getTableLocator()->get('Routines');
        $this->assertTrue($routines->delete($routines->get(1)));
        $this->assertFalse(TableRegistry::getTableLocator()->get('RoutineExercises')->exists(['routineId' => 1]));
        $this->assertFalse(TableRegistry::getTableLocator()->get('WorkoutLogs')->exists(['routineExerciseId IN' => [1, 2, 3]]));
        $this->assertTrue(TableRegistry::getTableLocator()->get('WorkoutLogs')->exists(['routineExerciseId' => 4]));
    }

    public function testDisplayLabelsAndProtectedAttributes(): void
    {
        $users = TableRegistry::getTableLocator()->get('Users');
        $this->assertStringContainsString('Lucía Mendoza · lucia@atletalab.test', $users->get(4)->displayName);
        $assignments = TableRegistry::getTableLocator()->get('RoutineExercises');
        $label = $assignments->get(1, contain: ['Routines', 'Exercises'])->displayName;
        $this->assertStringContainsString('Base de fuerza', $label);
        $this->assertStringContainsString('Press de banca', $label);
        $entity = $users->newEntity(array_replace(TestDatabase::valid('Users'), ['id' => 900, 'createdAt' => '2000-01-01', 'role' => ['id' => 1]]));
        $this->assertNull($entity->get('id'));
        $this->assertNull($entity->get('createdAt'));
        $this->assertNull($entity->get('role'));
    }

    public function testTimestampsAreStoredInUtcAndReadInApplicationTimezone(): void
    {
        $table = TableRegistry::getTableLocator()->get('WorkoutLogs');
        $log = $table->newEntity(TestDatabase::valid('WorkoutLogs'));
        $this->assertNotFalse($table->save($log));
        $raw = ConnectionManager::get('test')->execute('SELECT loggedAt FROM workout_logs WHERE id = :id', ['id' => $log->id])->fetch('assoc');
        $this->assertLessThanOrEqual(2, abs(strtotime($raw['loggedAt'] . ' UTC') - time()));
        $read = $table->get($log->id);
        $this->assertSame(DateTime::now()->format('Y-m-d H'), $read->loggedAt->format('Y-m-d H'));
    }
}
