<?php
/**
 * SMTP Toggle Endpoint
 */

session_start();
date_default_timezone_set('America/Argentina/Buenos_Aires');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'No autorizado'];
    header('Location: /shooter/settings/smtp');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $db = getDB();
    
    // First deactivate all
    $db->query("UPDATE smtp_config SET is_active = 0");
    // Then activate the selected one
    $db->query("UPDATE smtp_config SET is_active = 1 WHERE id = ?", [$id]);
    
    $_SESSION['toast'] = ['type' => 'success', 'message' => 'Configuración SMTP activada'];
}

header('Location: /shooter/settings/smtp');
exit;
