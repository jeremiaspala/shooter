<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = getDB();
$id = (int)$_GET['id'] ?? 0;

// Deactivate all configs first
$db->query("UPDATE smtp_config SET is_active = 0");

// Activate selected config
$db->query("UPDATE smtp_config SET is_active = 1 WHERE id = ?", [$id]);

$_SESSION['toast'] = ['type' => 'success', 'message' => 'Configuración SMTP activada'];
header('Location: settings/smtp');
exit;
