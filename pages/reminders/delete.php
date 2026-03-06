<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin();

$db = getDB();
$id = (int)$_GET['id'] ?? 0;

if ($id > 0) {
    $db->query("DELETE FROM reminders WHERE id = ?", [$id]);
    $_SESSION['toast'] = ['type' => 'success', 'message' => 'Recordatorio eliminado correctamente'];
}

header('Location: reminders');
exit;
