<?php
declare(strict_types=1);

namespace App\Test\Support;

use Cake\Cache\Cache;
use Cake\Database\Driver\Sqlite;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;

final class TestDatabase
{
    public static function reset(): void
    {
        $connection = ConnectionManager::get('test');
        if (!$connection->getDriver() instanceof Sqlite || $connection->config()['database'] !== ':memory:') {
            throw new \RuntimeException('La suite requiere exclusivamente SQLite en memoria.');
        }
        TableRegistry::getTableLocator()->clear();
        $connection->execute('PRAGMA foreign_keys = ON');
        foreach (['workout_logs', 'routine_exercises', 'routines', 'users', 'exercises', 'objectives', 'archetypes', 'roles'] as $table) {
            $connection->execute('DROP TABLE IF EXISTS ' . $table);
        }
        foreach (explode(';', file_get_contents(TESTS . 'schema.sql')) as $statement) {
            if (trim($statement) !== '') {
                $connection->execute($statement);
            }
        }
        // Reutiliza exactamente los INSERT del archivo entregado para MySQL.
        $sql = file_get_contents(CONFIG . 'schema/atletalab.sql');
        preg_match_all('/INSERT INTO\s+.*?;/su', $sql, $inserts);
        foreach ($inserts[0] as $statement) {
            $connection->execute($statement);
        }
        Cache::clear('login');
    }

    public static function valid(string $model): array
    {
        return match ($model) {
            'Roles' => ['code' => 'TEST_ROLE', 'name' => 'Rol de prueba', 'description' => 'Descripción'],
            'Archetypes' => ['code' => 'TEST_ARCH', 'name' => 'Arquetipo de prueba', 'incPercentage' => '2.50', 'stagnationPenalty' => '5.00', 'deloadPercentage' => '20.00', 'maxFatigueThreshold' => '140.00', 'rpeLimit' => '8.5'],
            'Users' => ['email' => 'prueba@atletalab.test', 'firstName' => 'Ana', 'lastName' => 'Prueba', 'password' => 'Prueba-segura-2026', 'birthDate' => '2000-01-01', 'roleId' => '3', 'archetypeId' => '1'],
            'Objectives' => ['code' => 'TEST_OBJ', 'name' => 'Objetivo de prueba', 'targetRepMin' => '8', 'targetRepMax' => '12', 'minSuccessRate' => '80.00'],
            'Exercises' => ['code' => 'TEST_EX', 'name' => 'Ejercicio de prueba', 'muscleGroup' => 'Pecho', 'fatigueCoefficient' => '1.50'],
            'Routines' => ['code' => 'TEST_ROUT', 'title' => 'Rutina de prueba', 'status' => 'Activa', 'athleteId' => '4', 'trainerId' => '2', 'objectiveId' => '2', 'effectivenessScore' => '0.00', 'isBestPractice' => '0', 'archetypeFilter' => '1'],
            'RoutineExercises' => ['targetSets' => '3', 'targetReps' => '10', 'currentWeight' => '30.00', 'projectedWeight' => '30.75', 'routineId' => '1', 'exerciseId' => '1', 'muscleGroupFilter' => 'Pecho'],
            'WorkoutLogs' => ['setsCompleted' => '3', 'repsAchieved' => '10', 'weightUsed' => '30.00', 'rpe' => '7.5', 'routineExerciseId' => '1', 'routineFilter' => '1'],
        };
    }
}
