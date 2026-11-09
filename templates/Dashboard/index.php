<?php $this->assign('title', 'Dashboard'); ?>
<div class="page-heading"><div><div class="eyebrow">TU CENTRO DE PLANIFICACIÓN</div><h1>El progreso empieza aquí.</h1><p>Una visión clara de las personas, la carga y el rendimiento.</p></div><?= $this->Html->link('+ Crear rutina', ['controller' => 'Routines', 'action' => 'add'], ['class' => 'button button-primary']) ?></div>
<section class="stat-grid" aria-label="Indicadores del sistema">
    <?php foreach ([['athletes', 'Atletas registrados', 'Personas en progresión', 'Users', '◎'], ['active', 'Rutinas activas', 'Bloques en entrenamiento', 'Routines', '↗'], ['logs', 'Logs de entrenamiento', 'Registros de esfuerzo real', 'WorkoutLogs', '≋'], ['best', 'Mejores prácticas', 'Aprendizaje de bloques previos', 'Routines', '✧']] as [$key, $label, $description, $target, $icon]): ?>
        <a class="card stat-card" href="<?= $this->Url->build(['controller' => $target, 'action' => 'index']) ?>"><div class="stat-top"><span><?= h($label) ?></span><span class="stat-icon" aria-hidden="true"><?= h($icon) ?></span></div><strong class="stat-value"><?= (int)$stats[$key] ?></strong><span class="stat-caption"><?= h($description) ?></span></a>
    <?php endforeach ?>
</section>
<div class="dashboard-grid">
<section class="card dashboard-routines"><div class="card-heading"><div><h2>Rutinas recientes</h2><p>El estado actual de cada bloque de trabajo.</p></div><?= $this->Html->link('Ver todas →', ['controller' => 'Routines', 'action' => 'index'], ['class' => 'action-link']) ?></div>
    <div class="table-scroll"><table><thead><tr><th>Rutina / atleta</th><th>Objetivo</th><th>Estado</th><th>Efectividad</th></tr></thead><tbody>
        <?php foreach ($recentRoutines as $routine): ?><tr><td><?= $this->Html->link($routine->title, ['controller' => 'Routines', 'action' => 'view', $routine->id], ['class' => 'strong-link']) ?><small class="table-subtext"><?= h($routine->athlete->firstName . ' ' . $routine->athlete->lastName) ?></small></td><td><?= h($routine->objective->name) ?></td><td><?= $this->Admin->status($routine->status) ?></td><td><strong class="tabular"><?= h(number_format((float)$routine->effectivenessScore, 1, ',', '.')) ?>%</strong></td></tr><?php endforeach ?>
        <?php if (!count($recentRoutines)): ?><tr><td colspan="4" class="empty-state">Crea tu primera rutina para comenzar a planificar.</td></tr><?php endif ?>
    </tbody></table></div>
</section>
<section class="card status-card"><div class="card-heading"><div><h2>Estado de los bloques</h2><p>Distribución de las rutinas.</p></div></div><div class="status-list">
    <?php $total = max(1, array_sum(array_column($statuses->toArray(), 'total'))); ?>
    <?php foreach ($statuses as $row): ?><div class="status-row"><div><?= $this->Admin->status($row->status) ?><strong><?= (int)$row->total ?></strong></div><meter min="0" max="<?= $total ?>" value="<?= (int)$row->total ?>" aria-label="<?= h($row->status) ?>"></meter></div><?php endforeach ?>
    <?php if (!count($statuses)): ?><p class="muted">Todavía no hay rutinas registradas.</p><?php endif ?>
    <div class="insight-note"><strong>La recuperación también es progreso.</strong><p>Revisa los bloques en descarga antes de ajustar su próxima carga.</p></div>
</div></section>
<section class="card activity-card"><div class="card-heading"><div><h2>Últimos entrenamientos</h2><p>Señales recientes de esfuerzo y respuesta.</p></div><?= $this->Html->link('Ver logs →', ['controller' => 'WorkoutLogs', 'action' => 'index'], ['class' => 'action-link']) ?></div>
    <div class="activity-list"><?php foreach ($recentLogs as $log): ?><a class="activity-item" href="<?= $this->Url->build(['controller' => 'WorkoutLogs', 'action' => 'view', $log->id]) ?>"><span class="activity-icon" aria-hidden="true">↗</span><span class="activity-content"><strong><?= h($log->routine_exercise->exercise->name) ?></strong><small><?= h($log->routine_exercise->routine->athlete->firstName . ' ' . $log->routine_exercise->routine->athlete->lastName) ?> · <?= (int)$log->setsCompleted ?> × <?= (int)$log->repsAchieved ?> · <?= h($log->weightUsed) ?> kg</small></span><span class="rpe-chip">RPE <?= h($log->rpe) ?></span></a><?php endforeach ?><?php if (!count($recentLogs)): ?><p class="empty-state">Los entrenamientos registrados aparecerán aquí.</p><?php endif ?></div>
</section>
<section class="planning-card"><span class="eyebrow">LA BASE DEL SIGUIENTE AVANCE</span><h2>Configura con criterio.<br>Planifica con confianza.</h2><p>Arquetipos, objetivos y coeficientes coherentes dan contexto a cada decisión del Core.</p><div class="planning-links"><?= $this->Html->link('Revisar arquetipos →', ['controller' => 'Archetypes', 'action' => 'index']) ?><?= $this->Html->link('Explorar ejercicios →', ['controller' => 'Exercises', 'action' => 'index']) ?></div><span class="planning-decoration" aria-hidden="true">↗</span></section>
</div>
