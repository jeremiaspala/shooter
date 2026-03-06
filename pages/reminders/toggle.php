<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin();

$db = getDB();

// Handle toggle action
if (isset($_POST['action']) && $_POST['action'] === 'toggle') {
    $id = (int)$_POST['id'];
    $reminder = $db->fetchOne("SELECT is_active FROM reminders WHERE id = ?", [$id]);
    if ($reminder) {
        $newStatus = $reminder['is_active'] ? 0 : 1;
        $db->query("UPDATE reminders SET is_active = ? WHERE id = ?", [$newStatus, $id]);
    }
    echo json_encode(['success' => true]);
    exit;
}
