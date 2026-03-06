<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('America/Argentina/Buenos_Aires');

$message = '';
$error = '';
$debug = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = $_POST['db_host'] ?? 'localhost';
    $db_name = trim($_POST['db_name'] ?? '');
    $db_user = trim($_POST['db_user'] ?? '');
    $db_pass = $_POST['db_pass'] ?? '';
    
    $debug[] = "Received: host=$db_host, db=$db_name, user=$db_user";
    
    if (empty($db_name)) {
        $error = 'El nombre de la base de datos es requerido';
    } elseif (empty($db_user)) {
        $error = 'El usuario de la base de datos es requerido';
    } else {
        // Test connection first
        try {
            $test_pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
            $test_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $debug[] = "MySQL connection: OK";
        } catch (PDOException $e) {
            $error = 'No se pudo conectar al servidor MySQL: ' . $e->getMessage();
        }
        
        if (!$error) {
            $config_path = __DIR__ . '/config/database.php';
            $debug[] = "Config path: $config_path";
            $debug[] = "Config writable: " . (is_writable(dirname($config_path)) ? 'YES' : 'NO');
            
            $config_content = "<?php
// Database Configuration
define('DB_HOST', '$db_host');
define('DB_NAME', '$db_name');
define('DB_USER', '$db_user');
define('DB_PASS', '$db_pass');
define('DB_CHARSET', 'utf8mb4');

class Database {
    private \$pdo;
    
    public function __construct() {
        \$dsn = \"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME . \";charset=\" . DB_CHARSET;
        \$options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        try {
            \$this->pdo = new PDO(\$dsn, DB_USER, DB_PASS, \$options);
        } catch (PDOException \$e) {
            die(\"Database connection failed: \" . \$e->getMessage());
        }
    }
    
    public function getConnection() { return \$this->pdo; }
    public function query(\$sql, \$params = []) {
        \$stmt = \$this->pdo->prepare(\$sql);
        \$stmt->execute(\$params);
        return \$stmt;
    }
    public function fetchAll(\$sql, \$params = []) {
        return \$this->query(\$sql, \$params)->fetchAll();
    }
    public function fetchOne(\$sql, \$params = []) {
        return \$this->query(\$sql, \$params)->fetch();
    }
    public function lastInsertId() { return \$this->pdo->lastInsertId(); }
}

function getDB() {
    static \$db = null;
    if (\$db === null) { \$db = new Database(); }
    return \$db;
}
";
            
            $result = file_put_contents($config_path, $config_content);
            $debug[] = "file_put_contents result: " . ($result !== false ? "OK ($result bytes)" : "FAILED");
            
            if ($result === false) {
                $error = "ERROR: No se pudo escribir el archivo de configuración. Verifica los permisos.";
            } else {
                // Continue with database creation
                try {
                    $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    $pdo->exec("USE `$db_name`");
                    
                    // Create tables
                    $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
                        `id` INT AUTO_INCREMENT PRIMARY KEY,
                        `username` VARCHAR(50) NOT NULL UNIQUE,
                        `email` VARCHAR(100) NOT NULL,
                        `password` VARCHAR(255) NOT NULL,
                        `role` ENUM('admin','visor') NOT NULL DEFAULT 'visor',
                        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                    
                    $pdo->exec("CREATE TABLE IF NOT EXISTS `smtp_config` (
                        `id` INT AUTO_INCREMENT PRIMARY KEY,
                        `smtp_host` VARCHAR(255) NOT NULL,
                        `smtp_port` INT NOT NULL DEFAULT 587,
                        `smtp_username` VARCHAR(255) NOT NULL,
                        `smtp_password` VARCHAR(255) NOT NULL,
                        `smtp_encryption` ENUM('ssl','tls','none') NOT NULL DEFAULT 'tls',
                        `smtp_from_name` VARCHAR(100) NOT NULL,
                        `smtp_from_email` VARCHAR(100) NOT NULL,
                        `is_active` BOOLEAN DEFAULT FALSE,
                        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                    
                    $pdo->exec("CREATE TABLE IF NOT EXISTS `reminders` (
                        `id` INT AUTO_INCREMENT PRIMARY KEY,
                        `subject` VARCHAR(255) NOT NULL,
                        `content` LONGTEXT NOT NULL,
                        `recipients` TEXT NOT NULL,
                        `next_execution` DATETIME NOT NULL,
                        `repeat_type` ENUM('none','daily','weekly','monthly','yearly','custom') NOT NULL DEFAULT 'none',
                        `repeat_interval` INT DEFAULT 1,
                        `repeat_unit` ENUM('minutes','hours','days','weeks','months') DEFAULT 'days',
                        `repeat_days` JSON,
                        `repeat_month_days` JSON,
                        `repeat_end_type` ENUM('never','after','on_date') NOT NULL DEFAULT 'never',
                        `repeat_count` INT,
                        `end_date` DATETIME,
                        `execution_count` INT DEFAULT 0,
                        `is_active` BOOLEAN DEFAULT TRUE,
                        `created_by` INT,
                        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                    
                    $pdo->exec("CREATE TABLE IF NOT EXISTS `reminder_logs` (
                        `id` INT AUTO_INCREMENT PRIMARY KEY,
                        `reminder_id` INT NOT NULL,
                        `executed_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                        `status` ENUM('success','failed') NOT NULL,
                        `error_message` TEXT,
                        `recipients` TEXT,
                        FOREIGN KEY (`reminder_id`) REFERENCES `reminders`(`id`) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                    
                    $pdo->exec("CREATE TABLE IF NOT EXISTS `app_config` (
                        `id` INT AUTO_INCREMENT PRIMARY KEY,
                        `config_key` VARCHAR(50) NOT NULL UNIQUE,
                        `config_value` TEXT,
                        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                    
                    // Insert default data
                    $pdo->exec("INSERT IGNORE INTO `app_config` (`config_key`, `config_value`) VALUES ('app_name', 'Shooter')");
                    
                    $admin_pass = password_hash('admin123', PASSWORD_DEFAULT);
                    $pdo->exec("INSERT IGNORE INTO `users` (`username`, `email`, `password`, `role`) VALUES ('admin', 'admin@example.com', '$admin_pass', 'admin')");
                    
                    $visor_pass = password_hash('visor123', PASSWORD_DEFAULT);
                    $pdo->exec("INSERT IGNORE INTO `users` (`username`, `email`, `password`, `role`) VALUES ('visor', 'visor@example.com', '$visor_pass', 'visor')");
                    
                    $debug[] = "Database created successfully!";
                    
                    // Cron job setup instructions
                    $cron_instructions = "
                    <br><br>
                    <strong>⚠️ Configurar Cron Job:</strong><br>
                    El servidor necesita un cron job para enviar los recordatorios automáticamente.<br><br>
                    <pre style='background:#1e293b;color:#e2e8f0;padding:12px;border-radius:6px;font-size:12px;'>
# Opción 1: Crontab del usuario www-data
sudo crontab -u www-data -l 2>/dev/null
echo \"* * * * * php /var/www/html/shooter/cron/send_reminders.php >> /var/log/shooter_reminders.log 2>&1\" | sudo crontab -u www-data -

# O crear archivo en /etc/cron.d/
sudo tee /etc/cron.d/shooter > /dev/null << 'EOF'
* * * * * www-data php /var/www/html/shooter/cron/send_reminders.php >> /var/log/shooter_reminders.log 2>&1
EOF
sudo chmod 644 /etc/cron.d/shooter

# Verificar que el cron esté activo
sudo systemctl status cron</pre>";
                    
                    $message = "Installation completed!<br>
                        <a href='login'>Click here to login</a><br><br>
                        <strong>Admin:</strong> admin / admin123<br>
                        <strong>Visor:</strong> visor / visor123" . $cron_instructions;
                    
                } catch (PDOException $e) {
                    $error = "Database error: " . $e->getMessage();
                    $debug[] = "DB Error: " . $e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install - Shooter</title>
    <link rel="stylesheet" href="/shooter/assets/css/style.css">
    <style>
        body { font-family: system-ui, sans-serif; background: #f0f4f8; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; margin: 0; }
        .install-container { background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); padding: 40px; width: 100%; max-width: 450px; }
        h1 { color: #1e293b; margin: 0 0 8px; }
        .subtitle { color: #64748b; margin-bottom: 24px; }
        .form-group { margin-bottom: 16px; }
        label { display: block; color: #374151; margin-bottom: 6px; font-weight: 500; }
        input { width: 100%; padding: 10px 12px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px; box-sizing: border-box; }
        input:focus { outline: none; border-color: #3b82f6; }
        .btn { background: #3b82f6; color: white; border: none; padding: 12px 20px; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; width: 100%; }
        .btn:hover { background: #2563eb; }
        .message { padding: 14px; border-radius: 6px; margin-bottom: 16px; font-size: 14px; }
        .success { background: #d1fae5; color: #065f46; border: 1px solid #34d399; }
        .error { background: #fee2e2; color: #991b1b; border: 1px solid #f87171; }
        .debug { background: #fef3c7; color: #92400e; border: 1px solid #f59e0b; white-space: pre-wrap; font-size: 12px; }
    </style>
</head>
<body>
    <div class="install-container">
        <h1>Install Shooter</h1>
        <p class="subtitle">Email Reminder System</p>
        
        <?php if ($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($debug)): ?>
            <div class="message debug"><?php echo implode("\n", $debug); ?></div>
        <?php endif; ?>
        
        <?php if (!$message): ?>
        <form method="POST">
            <div class="form-group">
                <label>Database Host</label>
                <input type="text" name="db_host" value="localhost" required>
            </div>
            <div class="form-group">
                <label>Database Name</label>
                <input type="text" name="db_name" placeholder="shooter_db" required>
            </div>
            <div class="form-group">
                <label>Database User</label>
                <input type="text" name="db_user" required>
            </div>
            <div class="form-group">
                <label>Database Password</label>
                <input type="password" name="db_pass">
            </div>
            <button type="submit" class="btn">Install</button>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>
