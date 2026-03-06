<?php
session_start();
date_default_timezone_set('America/Argentina/Buenos_Aires');
require_once 'config/database.php';
require_once 'includes/functions.php';

$error = '';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Por favor ingrese usuario y contraseña';
    } else {
        $db = getDB();
        $user = $db->fetchOne("SELECT * FROM users WHERE username = ?", [$username]);
        
        if ($user && verifyPassword($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            
            header('Location: dashboard');
            exit;
        } else {
            $error = 'Usuario o contraseña incorrectos';
        }
    }
}

// Get app name from config
$appName = getAppName();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo htmlspecialchars($appName); ?></title>
    <link rel="stylesheet" href="/shooter/assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h1><?php echo htmlspecialchars($appName); ?></h1>
            
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="username">Usuario</label>
                    <input type="text" id="username" name="username" class="form-control" required autofocus>
                </div>
                <div class="form-group">
                    <label for="password">Contrase&ntilde;a</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Iniciar Sesi&oacute;n</button>
            </form>
            
            <div style="margin-top: 24px; text-align: center; color: var(--text-secondary); font-size: 13px;">
            </div>
        </div>
    </div>
</body>
</html>
