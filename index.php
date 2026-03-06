<?php
session_start();
date_default_timezone_set('America/Argentina/Buenos_Aires');
require_once 'config/database.php';
require_once 'includes/functions.php';

requireLogin();

$appName = getAppName();

// Parse the clean URL
$request_uri = $_SERVER['REQUEST_URI'];
$request_uri = str_replace('/shooter/', '', $request_uri);
$request_uri = trim($request_uri, '/');

// Get page and action from URL
$parts = explode('/', $request_uri);
$page = $parts[0] ?? 'dashboard';
$action = $parts[1] ?? 'list';

// Handle query strings for actions like ?action=edit&id=1
if (isset($_GET['action'])) $action = $_GET['action'];

$pageTitle = 'Dashboard';

// Route to different pages
switch ($page) {
    case '':
    case 'dashboard':
        $pageTitle = 'Dashboard';
        include 'includes/header.php';
        include 'pages/dashboard.php';
        break;
        
    case 'reminders':
        $pageTitle = 'Recordatorios';
        
        switch ($action) {
            case 'list':
            case '':
                include 'includes/header.php';
                include 'pages/reminders/list.php';
                break;
            case 'create':
                requireAdmin();
                $pageTitle = 'Crear Recordatorio';
                include 'includes/header.php';
                include 'pages/reminders/create.php';
                break;
            case 'edit':
                requireAdmin();
                $pageTitle = 'Editar Recordatorio';
                include 'includes/header.php';
                include 'pages/reminders/edit.php';
                break;
            case 'view':
                $pageTitle = 'Ver Detalles';
                include 'includes/header.php';
                include 'pages/reminders/view.php';
                break;
            case 'delete':
                requireAdmin();
                include 'pages/reminders/delete.php';
                break;
            case 'toggle':
                requireAdmin();
                include 'pages/reminders/toggle.php';
                break;
            default:
                include 'includes/header.php';
                include 'pages/reminders/list.php';
        }
        break;
        
    case 'users':
        requireAdmin();
        $pageTitle = 'Usuarios';
        
        switch ($action) {
            case 'list':
            case '':
                include 'includes/header.php';
                include 'pages/users/list.php';
                break;
            case 'create':
                $pageTitle = 'Crear Usuario';
                include 'includes/header.php';
                include 'pages/users/create.php';
                break;
            case 'edit':
                $pageTitle = 'Editar Usuario';
                include 'includes/header.php';
                include 'pages/users/edit.php';
                break;
            case 'delete':
                include 'pages/users/delete.php';
                break;
            default:
                include 'includes/header.php';
                include 'pages/users/list.php';
        }
        break;
        
    case 'settings':
        // Check if it's settings/smtp
        if (isset($parts[1]) && $parts[1] === 'smtp') {
            requireAdmin();
            $pageTitle = 'Configuración SMTP';
            include 'includes/header.php';
            include 'pages/settings/smtp.php';
        } else {
            requireAdmin();
            $pageTitle = 'Configuración General';
            include 'includes/header.php';
            include 'pages/settings/app.php';
        }
        break;
        
    default:
        $pageTitle = 'Dashboard';
        include 'includes/header.php';
        include 'pages/dashboard.php';
}

include 'includes/footer.php';
