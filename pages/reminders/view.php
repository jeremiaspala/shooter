<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = getDB();
$id = (int)$_GET['id'] ?? 0;

$reminder = $db->fetchOne("
    SELECT r.*, u.username as created_by_name 
    FROM reminders r
    LEFT JOIN users u ON r.created_by = u.id
    WHERE r.id = ?
", [$id]);

if (!$reminder) {
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'Recordatorio no encontrado'];
    header('Location: reminders');
    exit;
}

$recipients = json_decode($reminder['recipients'], true) ?? [];

// Get logs
$logs = $db->fetchAll("
    SELECT * FROM reminder_logs 
    WHERE reminder_id = ? 
    ORDER BY executed_at DESC 
    LIMIT 20
", [$id]);

$repeatLabels = [
    'none' => 'Una vez',
    'daily' => 'Diario',
    'weekly' => 'Semanal',
    'monthly' => 'Mensual',
    'yearly' => 'Anual',
    'custom' => 'Personalizado'
];
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Detalles del Recordatorio</h3>
        <div style="display: flex; gap: 8px;">
            <?php if ($_SESSION['user_role'] === 'admin'): ?>
            <a href="reminders?action=edit&id=<?php echo $id; ?>" class="btn btn-secondary btn-sm">Editar</a>
            <a href="reminders?action=delete&id=<?php echo $id; ?>" class="btn btn-danger btn-sm delete-confirm">Eliminar</a>
            <?php endif; ?>
            <a href="reminders" class="btn btn-secondary btn-sm">Volver</a>
        </div>
    </div>
    
    <div class="grid-2">
        <div>
            <h4 style="margin-bottom: 16px; color: var(--text-secondary); font-size: 13px; text-transform: uppercase;">Información General</h4>
            
            <div style="margin-bottom: 16px;">
                <strong>Asunto:</strong>
                <p><?php echo htmlspecialchars($reminder['subject']); ?></p>
            </div>
            
            <div style="margin-bottom: 16px;">
                <strong>Estado:</strong>
                <p>
                    <span class="status-badge <?php echo $reminder['is_active'] ? 'active' : 'inactive'; ?>">
                        <?php echo $reminder['is_active'] ? 'Activo' : 'Inactivo'; ?>
                    </span>
                </p>
            </div>
            
            <div style="margin-bottom: 16px;">
                <strong>Repetición:</strong>
                <p><?php echo $repeatLabels[$reminder['repeat_type']] ?? $reminder['repeat_type']; ?></p>
            </div>
            
            <div style="margin-bottom: 16px;">
                <strong>Próxima Ejecución:</strong>
                <p><?php echo formatDateTime($reminder['next_execution']); ?></p>
            </div>
            
            <div style="margin-bottom: 16px;">
                <strong>Ejecuciones Realizadas:</strong>
                <p><?php echo $reminder['execution_count']; ?></p>
            </div>
            
            <div style="margin-bottom: 16px;">
                <strong>Creado por:</strong>
                <p><?php echo htmlspecialchars($reminder['created_by_name'] ?? 'N/A'); ?></p>
            </div>
            
            <div style="margin-bottom: 16px;">
                <strong>Fecha de Creación:</strong>
                <p><?php echo formatDateTime($reminder['created_at']); ?></p>
            </div>
        </div>
        
        <div>
            <h4 style="margin-bottom: 16px; color: var(--text-secondary); font-size: 13px; text-transform: uppercase;">Destinatarios</h4>
            
            <ul style="list-style: none; padding: 0;">
                <?php foreach ($recipients as $email): ?>
                <li style="padding: 8px 0; border-bottom: 1px solid var(--border);">
                    <?php echo htmlspecialchars($email); ?>
                </li>
                <?php endforeach; ?>
            </ul>
            
            <?php if ($reminder['repeat_type'] !== 'none'): ?>
            <h4 style="margin: 24px 0 16px; color: var(--text-secondary); font-size: 13px; text-transform: uppercase;">Configuración de Repetición</h4>
            
            <?php if ($reminder['repeat_type'] === 'custom'): ?>
            <p>Intervalo: cada <?php echo $reminder['repeat_interval']; ?> <?php echo $reminder['repeat_unit']; ?></p>
            <?php endif; ?>
            
            <p>Fin: 
                <?php 
                switch ($reminder['repeat_end_type']) {
                    case 'never': echo 'Nunca'; break;
                    case 'after': echo 'Después de ' . $reminder['repeat_count'] . ' ejecuciones'; break;
                    case 'on_date': echo 'El ' . formatDateTime($reminder['end_date']); break;
                }
                ?>
            </p>
            <?php endif; ?>
        </div>
    </div>
    
    <div style="margin-top: 24px;">
        <h4 style="margin-bottom: 16px; color: var(--text-secondary); font-size: 13px; text-transform: uppercase;">Contenido</h4>
        <div style="background: var(--background); padding: 20px; border-radius: 8px; border: 1px solid var(--border);">
            <?php echo $reminder['content']; ?>
        </div>
    </div>
    
    <div style="margin-top: 24px;">
        <h4 style="margin-bottom: 16px; color: var(--text-secondary); font-size: 13px; text-transform: uppercase;">Historial de Ejecuciones</h4>
        
        <?php if (empty($logs)): ?>
            <p>No hay ejecuciones registradas</p>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Destinatarios</th>
                            <th>Mensaje de Error</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo formatDateTime($log['executed_at']); ?></td>
                            <td>
                                <span class="status-badge <?php echo $log['status']; ?>">
                                    <?php echo $log['status'] === 'success' ? 'Éxito' : 'Fallido'; ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                $logRecipients = json_decode($log['recipients'], true) ?? [];
                                echo htmlspecialchars(implode(', ', $logRecipients));
                                ?>
                            </td>
                            <td><?php echo $log['error_message'] ? htmlspecialchars($log['error_message']) : '-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
