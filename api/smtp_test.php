<?php
/**
 * AJAX SMTP Test Endpoint
 */

header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

session_start();
date_default_timezone_set('America/Argentina/Buenos_Aires');

try {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../includes/functions.php';
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'No autorizado']);
        exit;
    }
    
    // Get email from request
    $test_email = $_POST['test_email'] ?? '';
    
    if (empty($test_email)) {
        echo json_encode(['success' => false, 'message' => 'Email de prueba requerido']);
        exit;
    }
    
    // Get active SMTP config
    $db = getDB();
    $smtp = $db->fetchOne("SELECT * FROM smtp_config WHERE is_active = 1 LIMIT 1");
    
    if (!$smtp) {
        echo json_encode(['success' => false, 'message' => 'No hay configuración SMTP activa']);
        exit;
    }
    
    // Try to send test email
    require_once __DIR__ . '/../vendor/autoload.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    // Server settings
    $mail->isSMTP();
    $mail->Host = $smtp['smtp_host'];
    $mail->Port = (int)$smtp['smtp_port'];
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
