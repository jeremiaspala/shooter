<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = getDB();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    $smtp_host = trim($_POST['smtp_host'] ?? '');
    $smtp_port = (int)($_POST['smtp_port'] ?? 587);
    $smtp_username = trim($_POST['smtp_username'] ?? '');
    $smtp_password = $_POST['smtp_password'] ?? '';
    $smtp_encryption = $_POST['smtp_encryption'] ?? 'tls';
    $smtp_from_name = trim($_POST['smtp_from_name'] ?? '');
    $smtp_from_email = trim($_POST['smtp_from_email'] ?? '');
    
    // Validation
    $errors = [];
    if (empty($smtp_host)) $errors[] = 'El servidor SMTP es requerido';
    if (empty($smtp_username)) $errors[] = 'El usuario SMTP es requerido';
    if (empty($smtp_from_name)) $errors[] = 'El nombre del remitente es requerido';
    if (empty($smtp_from_email)) $errors[] = 'El email del remitente es requerido';
    
    if (empty($errors)) {
        // Check if this is an edit
        $id = (int)$_POST['id'] ?? 0;
        
        if ($id > 0) {
            if (!empty($smtp_password)) {
                $db->query("
                    UPDATE smtp_config SET
                        smtp_host = ?, smtp_port = ?, smtp_username = ?, smtp_password = ?,
                        smtp_encryption = ?, smtp_from_name = ?, smtp_from_email = ?
                    WHERE id = ?
                ", [$smtp_host, $smtp_port, $smtp_username, $smtp_password, $smtp_encryption, $smtp_from_name, $smtp_from_email, $id]);
            } else {
                $db->query("
                    UPDATE smtp_config SET
                        smtp_host = ?, smtp_port = ?, smtp_username = ?,
                        smtp_encryption = ?, smtp_from_name = ?, smtp_from_email = ?
                    WHERE id = ?
                ", [$smtp_host, $smtp_port, $smtp_username, $smtp_encryption, $smtp_from_name, $smtp_from_email, $id]);
            }
            $_SESSION['toast'] = ['type' => 'success', 'message' => 'Configuración SMTP actualizada'];
        } else {
            $db->query("
                INSERT INTO smtp_config (smtp_host, smtp_port, smtp_username, smtp_password, smtp_encryption, smtp_from_name, smtp_from_email)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ", [$smtp_host, $smtp_port, $smtp_username, $smtp_password, $smtp_encryption, $smtp_from_name, $smtp_from_email]);
            $_SESSION['toast'] = ['type' => 'success', 'message' => 'Configuración SMTP creada'];
        }
        
        header('Location: settings/smtp');
        exit;
    }
}

// Get SMTP configs
$configs = $db->fetchAll("SELECT * FROM smtp_config ORDER BY created_at DESC");

// Handle toggle and delete actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    if ($_GET['action'] === 'toggle') {
        // First deactivate all
        $db->query("UPDATE smtp_config SET is_active = 0");
        // Then activate the selected one
        $db->query("UPDATE smtp_config SET is_active = 1 WHERE id = ?", [$id]);
        $_SESSION['toast'] = ['type' => 'success', 'message' => 'Configuración SMTP activada'];
        header('Location: settings/smtp');
        exit;
    }
    
    if ($_GET['action'] === 'delete') {
        $db->query("DELETE FROM smtp_config WHERE id = ?", [$id]);
        $_SESSION['toast'] = ['type' => 'success', 'message' => 'Configuración SMTP eliminada'];
        header('Location: settings/smtp');
        exit;
    }
}

// Handle AJAX test SMTP request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'test') {
    header('Content-Type: application/json');
    
    $test_email = $_POST['test_email'] ?? '';
    
    if (empty($test_email)) {
        echo json_encode(['success' => false, 'message' => 'Email de prueba requerido']);
        exit;
    }
    
    // Get active SMTP config
    $smtp = $db->fetchOne("SELECT * FROM smtp_config WHERE is_active = 1");
    
    if (!$smtp) {
        echo json_encode(['success' => false, 'message' => 'No hay configuración SMTP activa']);
        exit;
    }
    
    // Try to send test email
    try {
        require_once __DIR__ . '/../../vendor/autoload.php';
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = $smtp['smtp_host'];
        $mail->Port = $smtp['smtp_port'];
        $mail->SMTPSecure = $smtp['smtp_encryption'] === 'ssl' ? 'ssl' : ($smtp['smtp_encryption'] === 'tls' ? 'tls' : '');
        $mail->SMTPAuth = true;
        $mail->Username = $smtp['smtp_username'];
        $mail->Password = $smtp['smtp_password'];
        
        // Recipients
        $mail->setFrom($smtp['smtp_from_email'], $smtp['smtp_from_name']);
        $mail->addAddress($test_email);
        
        // Content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'Test - Shooter Email Reminder System';
        $mail->Body = 'Este es un email de prueba del sistema Shooter.<br><br>Si recibes este mensaje, la configuración SMTP está funcionando correctamente.';
        $mail->AltBody = strip_tags('Este es un email de prueba del sistema Shooter.');
        
        if ($mail->send()) {
            echo json_encode(['success' => true, 'message' => 'Email de prueba enviado correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al enviar: ' . $mail->ErrorInfo]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// Get edit config if exists
$editConfig = null;
if (isset($_GET['edit'])) {
    $editConfig = $db->fetchOne("SELECT * FROM smtp_config WHERE id = ?", [(int)$_GET['edit']]);
}
?>

<div class="grid-2">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?php echo $editConfig ? 'Editar Configuración' : 'Nueva Configuración SMTP'; ?></h3>
            <?php if ($editConfig): ?>
            <a href="settings/smtp" class="btn btn-secondary btn-sm">Cancelar</a>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="action" value="save">
            <?php if ($editConfig): ?>
            <input type="hidden" name="id" value="<?php echo $editConfig['id']; ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label for="smtp_host">Servidor SMTP *</label>
                <input type="text" id="smtp_host" name="smtp_host" class="form-control" value="<?php echo htmlspecialchars($editConfig['smtp_host'] ?? $_POST['smtp_host'] ?? ''); ?>" placeholder="smtp.gmail.com" required>
            </div>
            
            <div class="grid-2">
                <div class="form-group">
                    <label for="smtp_port">Puerto *</label>
                    <input type="number" id="smtp_port" name="smtp_port" class="form-control" value="<?php echo htmlspecialchars($editConfig['smtp_port'] ?? $_POST['smtp_port'] ?? '587'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="smtp_encryption">Cifrado</label>
                    <select id="smtp_encryption" name="smtp_encryption" class="form-control">
                        <option value="tls" <?php echo ($editConfig['smtp_encryption'] ?? $_POST['smtp_encryption'] ?? '') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                        <option value="ssl" <?php echo ($editConfig['smtp_encryption'] ?? $_POST['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                        <option value="none" <?php echo ($editConfig['smtp_encryption'] ?? $_POST['smtp_encryption'] ?? '') === 'none' ? 'selected' : ''; ?>>Ninguno</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="smtp_username">Usuario SMTP *</label>
                <input type="text" id="smtp_username" name="smtp_username" class="form-control" value="<?php echo htmlspecialchars($editConfig['smtp_username'] ?? $_POST['smtp_username'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="smtp_password">Contraseña SMTP <?php echo $editConfig ? '(dejar en blanco para mantener)' : '*'; ?></label>
                <input type="password" id="smtp_password" name="smtp_password" class="form-control" <?php echo $editConfig ? '' : 'required'; ?>>
            </div>
            
            <div class="form-group">
                <label for="smtp_from_name">Nombre del Remitente *</label>
                <input type="text" id="smtp_from_name" name="smtp_from_name" class="form-control" value="<?php echo htmlspecialchars($editConfig['smtp_from_name'] ?? $_POST['smtp_from_name'] ?? ''); ?>" placeholder="Sistema de Recordatorios" required>
            </div>
            
            <div class="form-group">
                <label for="smtp_from_email">Email del Remitente *</label>
                <input type="email" id="smtp_from_email" name="smtp_from_email" class="form-control" value="<?php echo htmlspecialchars($editConfig['smtp_from_email'] ?? $_POST['smtp_from_email'] ?? ''); ?>" required>
            </div>
            
            <button type="submit" class="btn btn-primary"><?php echo $editConfig ? 'Guardar Cambios' : 'Crear Configuración'; ?></button>
        </form>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Configuraciones Guardadas</h3>
        </div>
        
        <?php if (empty($configs)): ?>
            <div class="empty-state">
                <p>No hay configuraciones guardadas</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Servidor</th>
                            <th>Remitente</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($configs as $config): ?>
                        <tr>
                            <td>
                                <?php echo htmlspecialchars($config['smtp_host']); ?>:<?php echo $config['smtp_port']; ?>
                                <br><small><?php echo strtoupper($config['smtp_encryption']); ?></small>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($config['smtp_from_name']); ?>
                                <br><small><?php echo htmlspecialchars($config['smtp_from_email']); ?></small>
                            </td>
                            <td>
                                <?php if ($config['is_active']): ?>
                                <span class="status-badge active">Activa</span>
                                <?php else: ?>
                                <span class="status-badge inactive">Inactiva</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="actions">
                                    <a href="settings/smtp?edit=<?php echo $config['id']; ?>" class="btn btn-secondary btn-sm">Editar</a>
                                    <?php if (!$config['is_active']): ?>
                                    <a href="/shooter/api/smtp_toggle.php?id=<?php echo $config['id']; ?>" class="btn btn-success btn-sm">Activar</a>
                                    <?php endif; ?>
                                    <a href="settings/smtp?action=delete&id=<?php echo $config['id']; ?>" class="btn btn-danger btn-sm delete-confirm">Eliminar</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <!-- Test SMTP Section -->
        <div style="margin-top: 32px; border-top: 1px solid var(--border); padding-top: 24px;">
            <h4 style="margin-bottom: 16px;">Probar Configuración SMTP</h4>
            
            <div class="form-group">
                <label for="test-email">Email de prueba</label>
                <div style="display: flex; gap: 8px;">
                    <input type="email" id="test-email" class="form-control" placeholder="email@ejemplo.com">
                    <button type="button" id="test-smtp" class="btn btn-secondary">Enviar Prueba</button>
                </div>
                <div id="smtp-test-result" class="smtp-test-result"></div>
            </div>
        </div>
    </div>
</div>
