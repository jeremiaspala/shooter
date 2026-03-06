<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin();

$db = getDB();
$id = (int)$_GET['id'] ?? 0;

// Prevent self-delete
if ($id == $_SESSION['user_id']) {
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'No puedes eliminar tu propio usuario'];
    header('Location: users');
    exit;
}

if ($id > 0) {
    $db->query("DELETE FROM users WHERE id = ?", [$id]);
    $_SESSION['toast'] = ['type' => 'success', 'message' => 'Usuario eliminado correctamente'];
}

header('Location: users');
exit;
