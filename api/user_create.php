<?php
/**
 * User Create Endpoint
 */

session_start();
date_default_timezone_set('America/Argentina/Buenos_Aires');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'visor';
    
    $errors = [];
    if (empty($username)) $errors[] = 'El usuario es requerido';
    if (empty($email)) $errors[] = 'El email es requerido';
    if (empty($password)) $errors[] = 'La contraseña es requerida';
    if (strlen($password) < 6) $errors[] = 'La contraseña debe tener al menos 6 caracteres';
    
    $existingUser = $db->fetchOne("SELECT id FROM users WHERE username = ?", [$username]);
    if ($existingUser) $errors[] = 'El usuario ya existe';
    
    if (empty($errors)) {
        $hashedPassword = hashPassword($password);
        
        $db->query("
            INSERT INTO users (username, email, password, role)
            VALUES (?, ?, ?, ?)
        ", [$username, $email, $hashedPassword, $role]);
        
        $_SESSION['toast'] = ['type' => 'success', 'message' => 'Usuario creado correctamente'];
        header('Location: /shooter/users');
        exit;
    }
}

$pageTitle = 'Crear Usuario';
include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Crear Nuevo Usuario</h3>
        <a href="/shooter/users" class="btn btn-secondary btn-sm">Volver</a>
    </div>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label for="username">Usuario *</label>
            <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="password">Contraseña *</label>
            <input type="password" id="password" name="password" class="form-control" required>
            <small style="color: var(--text-secondary);">Mínimo 6 caracteres</small>
        </div>
        
        <div class="form-group">
            <label for="role">Rol</label>
            <select id="role" name="role" class="form-control">
                <option value="visor" <?php echo ($_POST['role'] ?? '') === 'visor' ? 'selected' : ''; ?>>Visor (solo lectura)</option>
                <option value="admin" <?php echo ($_POST['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Admin (acceso completo)</option>
            </select>
        </div>
        
        <div style="margin-top: 24px; display: flex; gap: 12px;">
            <button type="submit" class="btn btn-primary">Crear Usuario</button>
            <a href="/shooter/users" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
