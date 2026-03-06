<?php
/**
 * Reminder View Endpoint
 */

session_start();
date_default_timezone_set('America/Argentina/Buenos_Aires');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$db = getDB();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'ID de recordatorio inválido'];
    header('Location: /shooter/reminders');
    exit;
}

$reminder = $db->fetchOne("SELECT r.*, u.username as created_by_name FROM reminders r LEFT JOIN users u ON r.created_by = u.id WHERE r.id = ?", [$id]);

if (!$reminder) {
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'Recordatorio no encontrado'];
    header('Location: /shooter/reminders');
    exit;
}

$recipients = json_decode($reminder['recipients'], true);

$pageTitle = 'Ver Recordatorio';
include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Detalles del Recordatorio</h3>
        <div>
            <?php if ($_SESSION['user_role'] === 'admin'): ?>
            <a href="/shooter/api/reminder_edit.php?id=<?php echo $id; ?>" class="btn btn-secondary btn-sm">Editar</a>
            <?php endif; ?>
            <a href="/shooter/reminders" class="btn btn-secondary btn-sm">Volver</a>
        </div>
    </div>
    
    <div class="grid-2">
        <div>
            <div class="form-group">
                <label>Asunto</label>
                <p><?php echo htmlspecialchars($reminder['subject']); ?></p>
            </div>
            
            <div class="form-group">
                <label>Próxima Ejecución</label>
                <p><?php echo formatDateTime($reminder['next_execution']); ?></p>
            </div>
            
            <div class="form-group">
                <label>Repetición</label>
                <p>
                    <?php 
                    $repeatLabels = [
                        'none' => 'Una vez',
                        'daily' => 'Diario',
                        'weekly' => 'Semanal',
                        'monthly' => 'Mensual',
                        'yearly' => 'Anual',
                        'custom' => 'Personalizado'
                    ];
                    echo $repeatLabels[$reminder['repeat_type']] ?? $reminder['repeat_type'];
                    ?>
                </p>
            </div>
            
            <div class="form-group">
                <label>Estado</label>
                <p>
                    <span class="status-badge <?php echo $reminder['is_active'] ? 'active' : 'inactive'; ?>">
                        <?php echo $reminder['is_active'] ? 'Activo' : 'Inactivo'; ?>
                    </span>
                </p>
            </div>
        </div>
        
        <div>
            <div class="form-group">
                <label>Destinatarios</label>
                <ul>
                    <?php foreach ($recipients as $r): ?>
                    <li><?php echo htmlspecialchars($r); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div class="form-group">
                <label>Creado por</label>
                <p><?php echo htmlspecialchars($reminder['created_by_name'] ?? 'Sistema'); ?></p>
            </div>
            
            <div class="form-group">
                <label>Ejecuciones realizadas</label>
                <p><?php echo $reminder['execution_count']; ?></p>
            </div>
        </div>
    </div>
    
    <div class="form-group">
        <label>Contenido</label>
        <div style="border: 1px solid var(--border); border-radius: 8px; padding: 16px; background: #f8fafc;">
            <?php echo $reminder['content']; ?>
        </div>
    </div>
    
    <?php if ($_SESSION['user_role'] === 'admin'): ?>
    <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border);">
        <a href="/shooter/api/reminder_delete.php?id=<?php echo $id; ?>" class="btn btn-danger delete-confirm">Eliminar Recordatorio</a>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
