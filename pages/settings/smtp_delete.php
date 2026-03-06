<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = getDB();
$id = (int)$_GET['id'] ?? 0;

if ($id > 0) {
    $db->query("DELETE FROM smtp_config WHERE id = ?", [$id]);
    $_SESSION['toast'] = ['type' => 'success', 'message' => 'Configuración SMTP eliminada'];
}

header('Location: settings/smtp');
exit;
