<?php
/**
 * Reminder Delete Endpoint
 */

session_start();
date_default_timezone_set('America/Argentina/Buenos_Aires');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $db = getDB();
    $db->query("DELETE FROM reminders WHERE id = ?", [$id]);
    $_SESSION['toast'] = ['type' => 'success', 'message' => 'Recordatorio eliminado'];
}

header('Location: /shooter/reminders');
exit;
