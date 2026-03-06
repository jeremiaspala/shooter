<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$db = getDB();

// Get statistics
$totalReminders = $db->fetchOne("SELECT COUNT(*) as total FROM reminders")['total'];
$totalExecutions = $db->fetchOne("SELECT COUNT(*) as total FROM reminder_logs")['total'];
$successExecutions = $db->fetchOne("SELECT COUNT(*) as total FROM reminder_logs WHERE status = 'success'")['total'];
$failedExecutions = $db->fetchOne("SELECT COUNT(*) as total FROM reminder_logs WHERE status = 'failed'")['total'];

// Upcoming reminders (next 7 days)
$upcomingReminders = $db->fetchAll("
    SELECT * FROM reminders 
    WHERE is_active = 1 
    AND next_execution BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
    ORDER BY next_execution ASC
    LIMIT 10
");

// Recent executions
$recentExecutions = $db->fetchAll("
    SELECT rl.*, r.subject 
    FROM reminder_logs rl
    LEFT JOIN reminders r ON rl.reminder_id = r.id
    ORDER BY rl.executed_at DESC
    LIMIT 10
");

// Success rate
$successRate = $totalExecutions > 0 ? round(($successExecutions / $totalExecutions) * 100, 1) : 0;

// Get executions per day for last 30 days
$executionsPerDay = $db->fetchAll("
    SELECT DATE(executed_at) as date, COUNT(*) as count, 
           SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success
    FROM reminder_logs
    WHERE executed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(executed_at)
    ORDER BY date ASC
");
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">📧</div>
        <div class="stat-info">
            <h3><?php echo $totalReminders; ?></h3>
            <p>Total Recordatorios</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon green">✓</div>
        <div class="stat-info">
            <h3><?php echo $totalExecutions; ?></h3>
            <p>Total Ejecuciones</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon orange">📅</div>
        <div class="stat-info">
            <h3><?php echo count($upcomingReminders); ?></h3>
            <p>Próximos (7 días)</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon purple">%</div>
        <div class="stat-info">
            <h3><?php echo $successRate; ?>%</h3>
            <p>Tasa de Éxito</p>
        </div>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Próximos Recordatorios</h3>
            <a href="reminders" class="btn btn-secondary btn-sm">Ver Todos</a>
        </div>
        
        <?php if (empty($upcomingReminders)): ?>
            <div class="empty-state">
                <p>No hay recordatorios programados</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Asunto</th>
                            <th>Próxima Ejecución</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upcomingReminders as $reminder): ?>
                        <tr>
                            <td>
                                <a href="reminders?action=view&id=<?php echo $reminder['id']; ?>">
                                    <?php echo htmlspecialchars($reminder['subject']); ?>
                                </a>
                            </td>
                            <td><?php echo formatDateTime($reminder['next_execution']); ?></td>
                            <td>
                                <span class="status-badge <?php echo $reminder['is_active'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $reminder['is_active'] ? 'Activo' : 'Inactivo'; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Ejecuciones Recientes</h3>
        </div>
        
        <?php if (empty($recentExecutions)): ?>
            <div class="empty-state">
                <p>No hay ejecuciones registradas</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Asunto</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentExecutions as $log): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($log['subject'] ?? 'N/A'); ?></td>
                            <td><?php echo formatDateTime($log['executed_at']); ?></td>
                            <td>
                                <span class="status-badge <?php echo $log['status']; ?>">
                                    <?php echo $log['status'] === 'success' ? 'Éxito' : 'Fallido'; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Estadísticas de Ejecución (Últimos 30 días)</h3>
    </div>
    
    <?php if (empty($executionsPerDay)): ?>
        <div class="empty-state">
            <p>No hay datos de ejecución</p>
        </div>
    <?php else: ?>
        <div style="height: 300px; display: flex; align-items: flex-end; gap: 4px; padding: 20px 0;">
            <?php 
            $maxCount = max(array_column($executionsPerDay, 'count')) ?: 1;
            foreach ($executionsPerDay as $day): 
                $height = ($day['count'] / $maxCount) * 100;
            ?>
            <div style="flex: 1; display: flex; flex-direction: column; align-items: center; gap: 4px;">
                <div style="width: 100%; background: linear-gradient(to top, #2563eb, #667eea); border-radius: 4px 4px 0 0; height: <?php echo $height; ?>%; min-height: 2px;" title="<?php echo $day['date'] . ': ' . $day['count'] . ' ejecuciones'; ?>"></div>
                <span style="font-size: 10px; color: #64748b; transform: rotate(-45deg); white-space: nowrap;">
                    <?php echo date('d/m', strtotime($day['date'])); ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
        <div style="display: flex; gap: 20px; justify-content: center; margin-top: 10px;">
            <span style="display: flex; align-items: center; gap: 6px;">
                <span style="width: 12px; height: 12px; background: #2563eb; border-radius: 2px;"></span>
                Total: <?php echo array_sum(array_column($executionsPerDay, 'count')); ?>
            </span>
            <span style="display: flex; align-items: center; gap: 6px;">
                <span style="width: 12px; height: 12px; background: #22c55e; border-radius: 2px;"></span>
                Exitosas: <?php echo array_sum(array_column($executionsPerDay, 'success')); ?>
            </span>
        </div>
    <?php endif; ?>
</div>
