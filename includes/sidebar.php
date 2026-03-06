<aside class="sidebar">
    <div class="logo">
        <h1><?php echo htmlspecialchars(getAppName()); ?></h1>
    </div>
    <nav class="nav-menu">
        <a href="/shooter/dashboard" class="nav-item">
            <span class="icon">📊</span>
            Dashboard
        </a>
        <a href="/shooter/reminders" class="nav-item">
            <span class="icon">📧</span>
            Recordatorios
        </a>
        <?php if ($_SESSION['user_role'] === 'admin'): ?>
        <a href="/shooter/users" class="nav-item">
            <span class="icon">👥</span>
            Usuarios
        </a>
        <a href="/shooter/settings/smtp" class="nav-item">
            <span class="icon">⚙️</span>
            Configuración SMTP
        </a>
        <a href="/shooter/settings" class="nav-item">
            <span class="icon">🔧</span>
            Configuración General
        </a>
        <?php endif; ?>
    </nav>
</aside>
