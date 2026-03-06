<?php
/**
 * Reminder Create Endpoint
 */

session_start();
date_default_timezone_set('America/Argentina/Buenos_Aires');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $content = $_POST['content'] ?? '';
    $recipients = $_POST['recipients'] ?? [];
    $next_execution = $_POST['next_execution'] ?? '';
    $repeat_type = $_POST['repeat_type'] ?? 'none';
    $repeat_interval = (int)($_POST['repeat_interval'] ?? 1);
    $repeat_unit = $_POST['repeat_unit'] ?? 'days';
    $repeat_days = isset($_POST['repeat_days']) ? json_encode($_POST['repeat_days']) : null;
    $repeat_month_days = isset($_POST['repeat_month_days']) ? json_encode($_POST['repeat_month_days']) : null;
    $repeat_end_type = $_POST['repeat_end_type'] ?? 'never';
    $repeat_count = !empty($_POST['repeat_count']) ? (int)$_POST['repeat_count'] : null;
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    
    // Validation
    $errors = [];
    if (empty($subject)) $errors[] = 'El asunto es requerido';
    if (empty($content)) $errors[] = 'El contenido es requerido';
    if (empty($recipients)) $errors[] = 'Al menos un destinatario es requerido';
    if (empty($next_execution)) $errors[] = 'La fecha de ejecución es requerida';
    
    if (empty($errors)) {
        $recipientsJson = json_encode(array_filter(array_map('trim', $recipients)));
        
        $db->query("
            INSERT INTO reminders (subject, content, recipients, next_execution, repeat_type, repeat_interval, repeat_unit, repeat_days, repeat_month_days, repeat_end_type, repeat_count, end_date, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ", [
            $subject, $content, $recipientsJson, $next_execution, 
            $repeat_type, $repeat_interval, $repeat_unit, $repeat_days, 
            $repeat_month_days, $repeat_end_type, $repeat_count, $end_date,
            $_SESSION['user_id']
        ]);
        
        $_SESSION['toast'] = ['type' => 'success', 'message' => 'Recordatorio creado correctamente'];
        header('Location: /shooter/reminders');
        exit;
    }
}

// If GET request, show form
$pageTitle = 'Crear Recordatorio';
include __DIR__ . '/../includes/header.php';

// Get SMTP configs for dropdown
$smtpConfigs = $db->fetchAll("SELECT * FROM smtp_config WHERE is_active = 1");
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Crear Nuevo Recordatorio</h3>
        <a href="/shooter/reminders" class="btn btn-secondary btn-sm">Volver</a>
    </div>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <?php if (empty($smtpConfigs)): ?>
        <div class="alert alert-warning">
            <p>No hay configuraciones SMTP activas. Por favor configure SMTP primero en la sección de Configuración.</p>
            <a href="/shooter/settings/smtp" class="btn btn-primary btn-sm" style="margin-top: 10px;">Ir a Configuración SMTP</a>
        </div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="grid-2">
            <div class="form-group">
                <label for="subject">Asunto del Email *</label>
                <input type="text" id="subject" name="subject" class="form-control" value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="next_execution">Primera Ejecución *</label>
                <input type="datetime-local" id="next_execution" name="next_execution" class="form-control" value="<?php echo htmlspecialchars($_POST['next_execution'] ?? ''); ?>" required>
            </div>
        </div>
        
        <div class="form-group">
            <label>Destinatarios *</label>
            <div id="recipients-container" class="recipient-inputs">
                <?php 
                $recipientValues = $_POST['recipients'] ?? [''];
                foreach ($recipientValues as $email): 
                ?>
                <div class="recipient-row">
                    <input type="email" name="recipients[]" class="form-control" placeholder="email@ejemplo.com" value="<?php echo htmlspecialchars($email); ?>" required>
                    <button type="button" class="btn btn-danger btn-sm remove-recipient">✕</button>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="add-recipient" class="btn btn-secondary btn-sm add-recipient-btn" style="margin-top: 8px;">+ Agregar Destinatario</button>
        </div>
        
        <div class="form-group">
            <label for="content-editor">Contenido (HTML) *</label>
            <textarea id="content-editor" name="content"><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="repeat_type">Repetición</label>
            <select id="repeat_type" name="repeat_type" class="form-control">
                <option value="none" <?php echo ($_POST['repeat_type'] ?? 'none') === 'none' ? 'selected' : ''; ?>>No repetir (una vez)</option>
                <option value="daily" <?php echo ($_POST['repeat_type'] ?? '') === 'daily' ? 'selected' : ''; ?>>Diario</option>
                <option value="weekly" <?php echo ($_POST['repeat_type'] ?? '') === 'weekly' ? 'selected' : ''; ?>>Semanal</option>
                <option value="monthly" <?php echo ($_POST['repeat_type'] ?? '') === 'monthly' ? 'selected' : ''; ?>>Mensual</option>
                <option value="yearly" <?php echo ($_POST['repeat_type'] ?? '') === 'yearly' ? 'selected' : ''; ?>>Anual</option>
                <option value="custom" <?php echo ($_POST['repeat_type'] ?? '') === 'custom' ? 'selected' : ''; ?>>Personalizado</option>
            </select>
        </div>
        
        <div class="repeat-options <?php echo ($_POST['repeat_type'] ?? 'none') !== 'none' ? 'show' : ''; ?>">
            <div id="weekly-options" style="display: <?php echo ($_POST['repeat_type'] ?? '') === 'weekly' ? 'block' : 'none'; ?>;">
                <div class="form-group">
                    <label>Días de la semana</label>
                    <div class="repeat-days">
                        <?php 
                        $days = [
                            ['value' => 1, 'label' => 'Lunes'],
                            ['value' => 2, 'label' => 'Martes'],
                            ['value' => 3, 'label' => 'Miércoles'],
                            ['value' => 4, 'label' => 'Jueves'],
                            ['value' => 5, 'label' => 'Viernes'],
                            ['value' => 6, 'label' => 'Sábado'],
                            ['value' => 7, 'label' => 'Domingo']
                        ];
                        $selectedDays = $_POST['repeat_days'] ?? [];
                        foreach ($days as $day): 
                        ?>
                        <label class="day-checkbox">
                            <input type="checkbox" name="repeat_days[]" value="<?php echo $day['value']; ?>" <?php echo in_array($day['value'], $selectedDays) ? 'checked' : ''; ?>>
                            <?php echo $day['label']; ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div id="monthly-options" style="display: <?php echo ($_POST['repeat_type'] ?? '') === 'monthly' ? 'block' : 'none'; ?>;">
                <div class="form-group">
                    <label>Días del mes</label>
                    <div class="repeat-days">
                        <?php for ($d = 1; $d <= 31; $d++): ?>
                        <label class="day-checkbox">
                            <input type="checkbox" name="repeat_month_days[]" value="<?php echo $d; ?>" <?php echo isset($_POST['repeat_month_days']) && in_array($d, $_POST['repeat_month_days']) ? 'checked' : ''; ?>>
                            <?php echo $d; ?>
                        </label>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
            
            <div id="custom-options" style="display: <?php echo ($_POST['repeat_type'] ?? '') === 'custom' ? 'block' : 'none'; ?>;">
                <div class="grid-2">
                    <div class="form-group">
                        <label for="repeat_interval">Intervalo</label>
                        <input type="number" id="repeat_interval" name="repeat_interval" class="form-control" value="<?php echo htmlspecialchars($_POST['repeat_interval'] ?? '1'); ?>" min="1">
                    </div>
                    
                    <div class="form-group">
                        <label for="repeat_unit">Unidad</label>
                        <select id="repeat_unit" name="repeat_unit" class="form-control">
                            <option value="minutes" <?php echo ($_POST['repeat_unit'] ?? '') === 'minutes' ? 'selected' : ''; ?>>Minutos</option>
                            <option value="hours" <?php echo ($_POST['repeat_unit'] ?? '') === 'hours' ? 'selected' : ''; ?>>Horas</option>
                            <option value="days" <?php echo ($_POST['repeat_unit'] ?? 'days') === 'days' ? 'selected' : ''; ?>>Días</option>
                            <option value="weeks" <?php echo ($_POST['repeat_unit'] ?? '') === 'weeks' ? 'selected' : ''; ?>>Semanas</option>
                            <option value="months" <?php echo ($_POST['repeat_unit'] ?? '') === 'months' ? 'selected' : ''; ?>>Meses</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="repeat_end_type">Condición de fin</label>
                <select id="repeat_end_type" name="repeat_end_type" class="form-control">
                    <option value="never" <?php echo ($_POST['repeat_end_type'] ?? 'never') === 'never' ? 'selected' : ''; ?>>Nunca</option>
                    <option value="after" <?php echo ($_POST['repeat_end_type'] ?? '') === 'after' ? 'selected' : ''; ?>>Después de X ejecuciones</option>
                    <option value="on_date" <?php echo ($_POST['repeat_end_type'] ?? '') === 'on_date' ? 'selected' : ''; ?>>En fecha específica</option>
                </select>
            </div>
            
            <div id="end-after-options" style="display: <?php echo ($_POST['repeat_end_type'] ?? '') === 'after' ? 'block' : 'none'; ?>;">
                <div class="form-group">
                    <label for="repeat_count">Número de ejecuciones</label>
                    <input type="number" id="repeat_count" name="repeat_count" class="form-control" value="<?php echo htmlspecialchars($_POST['repeat_count'] ?? '10'); ?>" min="1">
                </div>
            </div>
            
            <div id="end-date-options" style="display: <?php echo ($_POST['repeat_end_type'] ?? '') === 'on_date' ? 'block' : 'none'; ?>;">
                <div class="form-group">
                    <label for="end_date">Fecha final</label>
                    <input type="datetime-local" id="end_date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($_POST['end_date'] ?? ''); ?>">
                </div>
            </div>
        </div>
        
        <div style="margin-top: 24px; display: flex; gap: 12px;">
            <button type="submit" class="btn btn-primary">Crear Recordatorio</button>
            <a href="/shooter/reminders" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<script>
document.getElementById('repeat_type').addEventListener('change', function() {
    const value = this.value;
    document.getElementById('weekly-options').style.display = value === 'weekly' ? 'block' : 'none';
    document.getElementById('monthly-options').style.display = value === 'monthly' ? 'block' : 'none';
    document.getElementById('custom-options').style.display = value === 'custom' ? 'block' : 'none';
});

document.getElementById('repeat_end_type').addEventListener('change', function() {
    const value = this.value;
    document.getElementById('end-after-options').style.display = value === 'after' ? 'block' : 'none';
    document.getElementById('end-date-options').style.display = value === 'on_date' ? 'block' : 'none';
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
