<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

$testEmail = $_POST['test_email'] ?? '';

if (empty($testEmail)) {
    echo json_encode(['success' => false, 'message' => 'Email requerido']);
    exit;
}

$smtpConfig = getActiveSMTP();

if (!$smtpConfig) {
    echo json_encode(['success' => false, 'message' => 'No hay configuración SMTP activa']);
    exit;
}

$result = sendEmail(
    $testEmail,
    'Test - Sistema de Recordatorios',
    '<h1>¡Hola!</h1><p>Este es un email de prueba desde el Sistema de Recordatorios.</p><p>Si recibes este mensaje, la configuración SMTP está funcionando correctamente.</p>',
    $smtpConfig
);

if ($result['success']) {
    echo json_encode(['success' => true, 'message' => 'Email enviado']);
} else {
    echo json_encode(['success' => false, 'message' => $result['error']]);
}
