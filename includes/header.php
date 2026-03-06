<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Shooter'; ?> - <?php echo htmlspecialchars(getAppName()); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/shooter/assets/css/style.css">
</head>
<body>
    <div class="app-container">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <div class="main-content">
            <header class="top-header">
                <h2><?php echo $pageTitle ?? 'Dashboard'; ?></h2>
                <div class="user-info">
                    <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <span class="role-badge <?php echo $_SESSION['user_role']; ?>"><?php echo ucfirst($_SESSION['user_role']); ?></span>
                    <a href="/shooter/logout" class="logout-btn">Logout</a>
                </div>
            </header>
            <main class="content">
