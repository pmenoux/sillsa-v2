<?php
// sill-admin/layout.php — Admin shell: topbar horizontal + main content
// Variables expected: $page (string), $action (string), $pageFile (string)

$pageTitles = [
    'dashboard'    => 'Tableau de bord',
    'kpi'          => 'KPIs',
    'pages'        => 'Pages',
    'publications' => 'Publications',
    'settings'     => 'Paramètres',
    'menu'         => 'Menu',
];

$pageTitle = $pageTitles[$page] ?? ucfirst($page);
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SILL Admin — <?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="assets/admin.css">
    <script src="https://cdn.ckeditor.com/4.25.1-lts/standard/ckeditor.js"></script>
</head>
<body class="admin-layout">

<header class="admin-topbar">
    <div class="topbar-brand">
        <strong>SILL Admin</strong>
    </div>
    <nav class="topbar-nav">
        <a href="?page=dashboard"<?= $page === 'dashboard' ? ' class="active"' : '' ?>>Tableau de bord</a>
        <a href="?page=kpi"<?= $page === 'kpi' ? ' class="active"' : '' ?>>KPIs</a>
        <a href="?page=pages"<?= $page === 'pages' ? ' class="active"' : '' ?>>Pages</a>
        <a href="?page=publications"<?= $page === 'publications' ? ' class="active"' : '' ?>>Publications</a>
        <a href="?page=settings"<?= $page === 'settings' ? ' class="active"' : '' ?>>Paramètres</a>
        <a href="?page=menu"<?= $page === 'menu' ? ' class="active"' : '' ?>>Menu</a>
    </nav>
    <div class="topbar-user">
        <span class="topbar-username"><?= e($_SESSION['admin_username'] ?? '') ?></span>
        <a href="?page=logout" class="topbar-logout">Déconnexion</a>
    </div>
</header>

<main class="admin-main">
    <?php if ($flash): ?>
        <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>
    <?php require $pageFile; ?>
</main>

</body>
</html>
