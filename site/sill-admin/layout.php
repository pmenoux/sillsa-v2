<?php
// sill-admin/layout.php — Admin shell: topbar horizontal + main content
// Variables expected: $page (string), $action (string), $pageFile (string)

$pageTitles = [
    'dashboard'    => 'Tableau de bord',
    'kpi'          => 'KPIs',
    'pages'        => 'Pages',
    'timeline'     => 'Actualités',
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
    <script>
    // Fallback: if CKEditor CDN blocked, try jsDelivr mirror
    if (typeof CKEDITOR === 'undefined') {
        document.write('<scr'+'ipt src="https://cdn.jsdelivr.net/npm/ckeditor4@4.25.1/ckeditor.js"><\/scr'+'ipt>');
    }
    </script>
    <script>
    // Final fallback: warn if still not loaded
    window.addEventListener('DOMContentLoaded', function() {
        if (typeof CKEDITOR === 'undefined') {
            document.querySelectorAll('textarea[id="content"]').forEach(function(ta) {
                var warn = document.createElement('div');
                warn.style.cssText = 'background:#FFF3CD;border:1px solid #FFD700;padding:8px 12px;margin-bottom:8px;font-size:13px;border-radius:4px;';
                warn.innerHTML = '<strong>Editeur visuel non disponible</strong> — Le CDN CKEditor est bloque par votre navigateur. Desactivez le bloqueur de pub sur cette page.';
                ta.parentNode.insertBefore(warn, ta);
                ta.rows = 20;
            });
        }
    });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
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
        <a href="?page=timeline"<?= $page === 'timeline' ? ' class="active"' : '' ?>>Actualités</a>
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
