<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = getDB();

// Pagination
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Get users
$users = $db->fetchAll("
    SELECT * FROM users
    ORDER BY created_at DESC
    LIMIT ? OFFSET ?
", [$perPage, $offset]);

$totalUsers = $db->fetchOne("SELECT COUNT(*) as total FROM users")['total'];
$totalPages = ceil($totalUsers / $perPage);
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Lista de Usuarios</h3>
        <a href="/shooter/api/user_create.php" class="btn btn-primary">+ Nuevo Usuario</a>
    </div>
    
    <?php if (empty($users)): ?>
        <div class="empty-state">
            <div class="icon">👥</div>
            <p>No hay usuarios</p>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Fecha de Creación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <span class="role-badge <?php echo $user['role']; ?>">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </td>
                        <td><?php echo formatDateTime($user['created_at']); ?></td>
                        <td>
                            <div class="actions">
                                <a href="/shooter/api/user_edit.php?id=<?php echo $user['id']; ?>" class="btn btn-secondary btn-sm">Editar</a>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <a href="/shooter/api/user_delete.php?id=<?php echo $user['id']; ?>" class="btn btn-danger btn-sm delete-confirm">Eliminar</a>
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
                <a href="/shooter/users?p=<?php echo $i; ?>" class="<?php echo $i === $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
