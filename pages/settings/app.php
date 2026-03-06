<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = getDB();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $app_name = trim($_POST['app_name'] ?? 'Shooter');
    
    if (!empty($app_name)) {
        $db->query("
            INSERT INTO app_config (config_key, config_value) 
            VALUES ('app_name', ?)
            ON DUPLICATE KEY UPDATE config_value = ?
        ", [$app_name, $app_name]);
        
        $_SESSION['toast'] = ['type' => 'success', 'message' => 'Configuración guardada correctamente'];
        
        // Clear static cache
        // Note: In production, you might want to clear any caching mechanism
    }
}

// Get current app name
$appConfig = $db->fetchOne("SELECT config_value FROM app_config WHERE config_key = 'app_name'");
$currentAppName = $appConfig ? $appConfig['config_value'] : 'Shooter';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Configuración General</h3>
    </div>
    
    <form method="POST">
        <div class="form-group">
            <label for="app_name">Nombre de la Aplicación</label>
            <input type="text" id="app_name" name="app_name" class="form-control" value="<?php echo htmlspecialchars($currentAppName); ?>" required>
            <small style="color: var(--text-secondary);">Este nombre aparecerá en el encabezado y título de la aplicación</small>
        </div>
        
        <button type="submit" class="btn btn-primary">Guardar Configuración</button>
    </form>
</div>
