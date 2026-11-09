<?php
declare(strict_types=1);

use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

return static function (RouteBuilder $routes): void {
    $routes->setRouteClass(DashedRoute::class);
    $routes->scope('/', static function (RouteBuilder $routes): void {
        $routes->connect('/', ['controller' => 'Dashboard', 'action' => 'index'])->setMethods(['GET']);
        $routes->connect('/users/login', ['controller' => 'Users', 'action' => 'login'])->setMethods(['GET', 'POST']);
        $routes->connect('/users/logout', ['controller' => 'Users', 'action' => 'logout'])->setMethods(['POST']);
        $routes->connect('/exercises/by-muscle-group', ['controller' => 'Exercises', 'action' => 'byMuscleGroup'])->setMethods(['GET']);
        $routes->connect('/users/athletes-by-archetype/{archetypeId}', ['controller' => 'Users', 'action' => 'athletesByArchetype'])
            ->setPass(['archetypeId'])->setPatterns(['archetypeId' => '\d+'])->setMethods(['GET']);
        $routes->connect('/routine-exercises/by-routine/{routineId}', ['controller' => 'RoutineExercises', 'action' => 'byRoutine'])
            ->setPass(['routineId'])->setPatterns(['routineId' => '\d+'])->setMethods(['GET']);
        foreach ([
            'roles' => 'Roles', 'archetypes' => 'Archetypes', 'users' => 'Users', 'objectives' => 'Objectives',
            'exercises' => 'Exercises', 'routines' => 'Routines', 'routine-exercises' => 'RoutineExercises', 'workout-logs' => 'WorkoutLogs',
        ] as $path => $controller) {
            $routes->connect('/' . $path, ['controller' => $controller, 'action' => 'index'])->setMethods(['GET']);
            $routes->connect('/' . $path . '/add', ['controller' => $controller, 'action' => 'add'])->setMethods(['GET', 'POST']);
            $routes->connect('/' . $path . '/view/{id}', ['controller' => $controller, 'action' => 'view'])->setPass(['id'])->setPatterns(['id' => '\d+'])->setMethods(['GET']);
            $routes->connect('/' . $path . '/edit/{id}', ['controller' => $controller, 'action' => 'edit'])->setPass(['id'])->setPatterns(['id' => '\d+'])->setMethods(['GET', 'POST', 'PUT', 'PATCH']);
            $routes->connect('/' . $path . '/delete/{id}', ['controller' => $controller, 'action' => 'delete'])->setPass(['id'])->setPatterns(['id' => '\d+'])->setMethods(['POST', 'DELETE']);
        }
    });
};
