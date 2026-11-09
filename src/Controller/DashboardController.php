<?php
declare(strict_types=1);

namespace App\Controller;

class DashboardController extends AppController
{
    public function index(): void
    {
        $this->request->allowMethod(['get']);
        $routines = $this->fetchTable('Routines');
        $stats = [
            'athletes' => $this->fetchTable('Users')->find()->innerJoinWith('Roles', static fn($query) => $query->where(['Roles.code' => 'ATHLETE']))->count(),
            'active' => $routines->find()->where(['status' => 'Activa'])->count(),
            'logs' => $this->fetchTable('WorkoutLogs')->find()->count(),
            'best' => $routines->find()->where(['isBestPractice' => true])->count(),
        ];
        $statusQuery = $routines->find();
        $statuses = $statusQuery->select(['status', 'total' => $statusQuery->func()->count('*')])->groupBy('status')->all();
        $recentRoutines = $routines->find()->contain(['Athletes', 'Objectives'])->orderBy(['Routines.updatedAt' => 'DESC', 'Routines.id' => 'DESC'])->limit(5)->all();
        $recentLogs = $this->fetchTable('WorkoutLogs')->find()->contain(['RoutineExercises.Exercises', 'RoutineExercises.Routines.Athletes'])->orderBy(['WorkoutLogs.loggedAt' => 'DESC', 'WorkoutLogs.id' => 'DESC'])->limit(5)->all();
        $this->set(compact('stats', 'statuses', 'recentRoutines', 'recentLogs'));
    }
}
