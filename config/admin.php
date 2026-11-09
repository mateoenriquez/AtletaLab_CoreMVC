<?php
declare(strict_types=1);

use App\Model\Table\ExercisesTable;
use App\Model\Table\RoutinesTable;

$text = static fn(string $label, string $hint = ''): array => ['label' => $label, 'type' => 'text', 'hint' => $hint];
$number = static fn(string $label, string $step = '1', string $hint = ''): array => ['label' => $label, 'type' => 'number', 'step' => $step, 'hint' => $hint];
$select = static fn(string $label, string $path, string $controller): array => ['label' => $label, 'type' => 'select', 'path' => $path, 'relatedController' => $controller];
$code = $text('Código', 'Identificador único en mayúsculas, sin espacios.');

return [
    'Roles' => [
        'title' => 'Roles', 'singular' => 'rol', 'description' => 'Define las responsabilidades de cada persona en AtletaLab.',
        'contain' => [], 'search' => ['code', 'name'], 'index' => ['code', 'name', 'description'],
        'fields' => ['code' => $code, 'name' => $text('Nombre'), 'description' => ['label' => 'Descripción', 'type' => 'textarea', 'optional' => true]],
    ],
    'Archetypes' => [
        'title' => 'Arquetipos', 'singular' => 'arquetipo', 'description' => 'Calibra la progresión, la fatiga tolerable y la recuperación.',
        'contain' => [], 'search' => ['code', 'name'], 'index' => ['code', 'name', 'incPercentage', 'maxFatigueThreshold', 'rpeLimit'],
        'fields' => [
            'code' => $code, 'name' => $text('Nombre'),
            'incPercentage' => $number('Incremento de carga (%)', '0.01', 'Entre 0.50 y 50.00.'),
            'stagnationPenalty' => $number('Penalización por estancamiento (%)', '0.01', 'Entre 0.50 y 50.00.'),
            'deloadPercentage' => $number('Reducción en descarga (%)', '0.01', 'Entre 0.50 y 50.00.'),
            'maxFatigueThreshold' => $number('Umbral máximo de fatiga', '0.01', 'Mayor que 0 y hasta 10000. Protege el denominador del ratio de fatiga.'),
            'rpeLimit' => $number('RPE límite', '0.1', 'Entre 5.0 y 9.5, con un decimal.'),
        ],
    ],
    'Users' => [
        'title' => 'Usuarios', 'singular' => 'usuario', 'description' => 'Administra atletas, entrenadores y acceso al panel.',
        'contain' => ['Roles', 'Archetypes'], 'search' => ['firstName', 'lastName', 'email'],
        'index' => ['firstName', 'lastName', 'email', 'roleId', 'archetypeId'],
        'fields' => [
            'firstName' => $text('Nombres'), 'lastName' => $text('Apellidos'),
            'email' => ['label' => 'Correo electrónico', 'type' => 'email'],
            'password' => ['label' => 'Contraseña', 'type' => 'password', 'hint' => 'Al menos 12 caracteres y hasta 72 bytes. En edición, déjala vacía para conservarla.'],
            'birthDate' => ['label' => 'Fecha de nacimiento', 'type' => 'date', 'hint' => 'Edad mínima de 12 años.'],
            'roleId' => $select('Rol', 'role.name', 'Roles'),
            'archetypeId' => $select('Arquetipo de progresión', 'archetype.name', 'Archetypes') + ['optional' => true],
        ],
    ],
    'Objectives' => [
        'title' => 'Objetivos', 'singular' => 'objetivo', 'description' => 'Establece rangos coherentes y el éxito mínimo de cada bloque.',
        'contain' => [], 'search' => ['code', 'name'], 'index' => ['code', 'name', 'targetRepMin', 'targetRepMax', 'minSuccessRate'],
        'fields' => [
            'code' => $code, 'name' => $text('Nombre'),
            'targetRepMin' => $number('Repeticiones mínimas', '1', 'Entre 1 y 50.'),
            'targetRepMax' => $number('Repeticiones máximas', '1', 'Entre 1 y 50. Debe ser mayor o igual al mínimo.'),
            'minSuccessRate' => $number('Tasa mínima de éxito (%)', '0.01', 'Entre 50.00 y 100.00.'),
        ],
    ],
    'Exercises' => [
        'title' => 'Ejercicios', 'singular' => 'ejercicio', 'description' => 'El catálogo biomecánico que da contexto a cada entrenamiento.',
        'contain' => [], 'search' => ['code', 'name', 'muscleGroup'], 'index' => ['code', 'name', 'muscleGroup', 'fatigueCoefficient'],
        'fields' => [
            'code' => $code, 'name' => $text('Nombre'),
            'muscleGroup' => ['label' => 'Grupo muscular', 'type' => 'select', 'options' => array_combine(ExercisesTable::MUSCLE_GROUPS, ExercisesTable::MUSCLE_GROUPS)],
            'fatigueCoefficient' => $number('Coeficiente de fatiga', '0.01', 'Mayor que 0.10 y menor que 5.00. IF = RPE × series × coeficiente.'),
            'description' => ['label' => 'Descripción', 'type' => 'textarea', 'optional' => true],
        ],
    ],
    'Routines' => [
        'title' => 'Rutinas', 'singular' => 'rutina', 'description' => 'Conecta personas, objetivos y bloques de progresión.',
        'contain' => ['Athletes.Archetypes', 'Trainers', 'Objectives'], 'search' => ['code', 'title'],
        'index' => ['code', 'title', 'status', 'athleteId', 'effectivenessScore', 'isBestPractice'],
        'deleteWarning' => 'Se eliminarán también sus ejercicios asignados y todos sus logs de entrenamiento.',
        'fields' => [
            'code' => $code, 'title' => $text('Título'), 'description' => ['label' => 'Descripción', 'type' => 'textarea', 'optional' => true],
            'status' => ['label' => 'Estado', 'type' => 'select', 'options' => array_combine(RoutinesTable::STATUSES, RoutinesTable::STATUSES), 'default' => 'Activa'],
            'archetypeFilter' => ['label' => 'Arquetipo de progresión', 'type' => 'select', 'transient' => true, 'hint' => 'Primero selecciona un arquetipo para consultar sus atletas.'],
            'athleteId' => $select('Atleta', 'athlete.displayName', 'Users'),
            'trainerId' => $select('Entrenador', 'trainer.displayName', 'Users'),
            'objectiveId' => $select('Objetivo', 'objective.name', 'Objectives'),
            'effectivenessScore' => $number('Efectividad (%)', '0.01', 'Entre 0.00 y 100.00.') + ['default' => '0.00'],
            'isBestPractice' => ['label' => 'Marcar como mejor práctica histórica', 'type' => 'checkbox'],
        ],
    ],
    'RoutineExercises' => [
        'title' => 'Ejercicios de rutina', 'singular' => 'asignación', 'description' => 'Define las series, repeticiones y cargas de cada ejercicio.',
        'contain' => ['Routines', 'Exercises'], 'search' => [],
        'index' => ['routineId', 'exerciseId', 'targetSets', 'targetReps', 'currentWeight', 'projectedWeight'],
        'deleteWarning' => 'Se eliminarán también todos los logs de esta asignación.',
        'fields' => [
            'routineId' => $select('Rutina', 'routine.title', 'Routines'),
            'muscleGroupFilter' => ['label' => 'Grupo muscular', 'type' => 'select', 'options' => array_combine(ExercisesTable::MUSCLE_GROUPS, ExercisesTable::MUSCLE_GROUPS), 'transient' => true, 'hint' => 'Filtra el catálogo de ejercicios por grupo muscular.'],
            'exerciseId' => $select('Ejercicio', 'exercise.name', 'Exercises'),
            'targetSets' => $number('Series meta'), 'targetReps' => $number('Repeticiones por serie'),
            'currentWeight' => $number('Carga actual (kg)', '0.01', 'Desde 0.00 hasta 9999.99 kg.'),
            'projectedWeight' => $number('Carga proyectada (kg)', '0.01', 'Desde 0.00 hasta 9999.99 kg.'),
        ],
    ],
    'WorkoutLogs' => [
        'title' => 'Logs de entrenamiento', 'singular' => 'log', 'description' => 'Registra la respuesta real que alimenta la evaluación de fatiga.',
        'contain' => ['RoutineExercises.Routines', 'RoutineExercises.Exercises'], 'search' => [],
        'index' => ['routineExerciseId', 'setsCompleted', 'repsAchieved', 'weightUsed', 'rpe', 'loggedAt'],
        'fields' => [
            'routineFilter' => ['label' => 'Rutina', 'type' => 'select', 'transient' => true, 'hint' => 'Selecciona una rutina para consultar sus ejercicios asignados.'],
            'routineExerciseId' => $select('Ejercicio asignado', 'routine_exercise.displayName', 'RoutineExercises'),
            'setsCompleted' => $number('Series completadas', '1', 'Debe ser mayor que cero.'),
            'repsAchieved' => $number('Repeticiones logradas por serie', '1', 'Se admite cero si no se logró completar la repetición.'),
            'weightUsed' => $number('Carga utilizada (kg)', '0.01', 'Se admite cero para ejercicios sin carga externa.'),
            'rpe' => $number('Esfuerzo percibido (RPE)', '0.1', 'Mayor que 1.0 y menor que 10.0; valores admitidos de 1.1 a 9.9.'),
        ],
    ],
];
