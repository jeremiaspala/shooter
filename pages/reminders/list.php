<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = getDB();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'toggle':
            $id = (int)$_POST['id'];
            $reminder = $db->fetchOne("SELECT is_active FROM reminders WHERE id = ?", [$id]);
            if ($reminder) {
                $newStatus = $reminder['is_active'] ? 0 : 1;
                $db->query("UPDATE reminders SET is_active = ? WHERE id = ?", [$newStatus, $id]);
                $_SESSION['toast'] = ['type' => 'success', 'message' => 'Estado actualizado correctamente'];
            }
            break;
    }
    header('Location: reminders');
    exit;
}

// Pagination
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Get reminders
$reminders = $db->fetchAll("
    SELECT r.*, u.username as created_by_name 
    FROM reminders r
    LEFT JOIN users u ON r.created_by = u.id
    ORDER BY r.created_at DESC
    LIMIT ? OFFSET ?
", [$perPage, $offset]);

$totalReminders = $db->fetchOne("SELECT COUNT(*) as total FROM reminders")['total'];
$totalPages = ceil($totalReminders / $perPage);
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Lista de Recordatorios</h3>
        <?php if ($_SESSION['user_role'] === 'admin'): ?>
        <a href="/shooter/api/reminder_create.php" class="btn btn-primary">+ Nuevo Recordatorio</a>
        <?php endif; ?>
    </div>
    
    <?php if (empty($reminders)): ?>
        <div class="empty-state">
            <div class="icon">📧</div>
            <p>No hay recordatorios configurados</p>
            <?php if ($_SESSION['user_role'] === 'admin'): ?>
            <a href="/shooter/api/reminder_create.php" class="btn btn-primary" style="margin-top: 16px;">Crear Primer Recordatorio</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Asunto</th>
                        <th>Próxima Ejecución</th>
                        <th>Repetición</th>
                        <th>Ejecuciones</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reminders as $reminder): ?>
                    <tr>
                        <td><?php echo $reminder['id']; ?></td>
                        <td>
                            <a href="/shooter/api/reminder_view.php?id=<?php echo $reminder['id']; ?>">
                                <?php echo htmlspecialchars($reminder['subject']); ?>
                            </a>
                        </td>
                        <td><?php echo formatDateTime($reminder['next_execution']); ?></td>
                        <td>
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
                        </td>
                        <td><?php echo $reminder['execution_count']; ?></td>
                        <td>
                            <span class="status-badge <?php echo $reminder['is_active'] ? 'active' : 'inactive'; ?>">
                                <?php echo $reminder['is_active'] ? 'Activo' : 'Inactivo'; ?>
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="/shooter/api/reminder_view.php?id=<?php echo $reminder['id']; ?>" class="btn btn-secondary btn-sm">Ver</a>
                                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                <a href="/shooter/api/reminder_edit.php?id=<?php echo $reminder['id']; ?>" class="btn btn-secondary btn-sm">Editar</a>
                                <a href="/shooter/api/reminder_delete.php?id=<?php echo $reminder['id']; ?>" class="btn btn-danger btn-sm delete-confirm">Eliminar</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="/shooter/reminders?p=<?php echo $i; ?>" class="<?php echo $i === $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
