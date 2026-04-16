<?php
// sill-admin/layout.php — Admin shell: topbar horizontal + main content
// Variables expected: $page (string), $action (string), $pageFile (string)

$pageTitles = [
    'dashboard'    => 'Tableau de bord',
    'kpi'          => 'KPIs',
    'repartition'  => 'Répartition locative',
    'en-bref'      => 'En bref — AMAS',
    'pages'        => 'Pages',
    'immeubles'    => 'Immeubles',
    'timeline'     => 'Actualités',
    'publications' => 'Publications',
    'newsletter'   => 'Newsletter',
    'settings'     => 'Paramètres',
    'menu'         => 'Menu',
    'stats'        => 'Statistiques',
    'guide'        => 'Guide',
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/7.6.1/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
    // Fallback: if cdnjs blocked, try jsDelivr mirror
    if (typeof tinymce === 'undefined') {
        document.write('<scr'+'ipt src="https://cdn.jsdelivr.net/npm/tinymce@7.6.1/tinymce.min.js"><\/scr'+'ipt>');
    }
    </script>
    <script>
    // Reusable TinyMCE init helper
    function initTinyMCE(selector, height) {
        if (typeof tinymce === 'undefined') {
            document.querySelectorAll(selector).forEach(function(ta) {
                var warn = document.createElement('div');
                warn.style.cssText = 'background:#FFF3CD;border:1px solid #FFD700;padding:8px 12px;margin-bottom:8px;font-size:13px;border-radius:4px;';
                warn.innerHTML = '<strong>Editeur visuel non disponible</strong> — Le CDN TinyMCE est bloqué par votre navigateur. Désactivez le bloqueur de pub sur cette page.';
                ta.parentNode.insertBefore(warn, ta);
                ta.rows = 20;
            });
            return;
        }
        tinymce.init({
            selector: selector,
            language: 'fr_FR',
            height: height || 350,
            menubar: false,
            plugins: 'lists link image table code fullscreen',
            toolbar: 'bold italic underline strikethrough | removeformat | bullist numlist blockquote | link unlink | image table hr | blocks | fullscreen code',
            content_css: false,
            promotion: false,
            branding: false,
            license_key: 'gpl'
        });
    }
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
        <a href="?page=repartition"<?= $page === 'repartition' ? ' class="active"' : '' ?>>Répartition</a>
        <a href="?page=en-bref"<?= $page === 'en-bref' ? ' class="active"' : '' ?>>En bref</a>
        <a href="?page=pages"<?= $page === 'pages' ? ' class="active"' : '' ?>>Pages</a>
        <a href="?page=immeubles"<?= $page === 'immeubles' ? ' class="active"' : '' ?>>Immeubles</a>
        <a href="<?= SITE_URL ?>/quartiers" target="_blank" title="Page publique (contenu statique)">Quartiers ↗</a>
        <a href="?page=timeline"<?= $page === 'timeline' ? ' class="active"' : '' ?>>Actualités</a>
        <a href="?page=publications"<?= $page === 'publications' ? ' class="active"' : '' ?>>Publications</a>
        <a href="?page=settings"<?= $page === 'settings' ? ' class="active"' : '' ?>>Paramètres</a>
        <a href="?page=menu"<?= $page === 'menu' ? ' class="active"' : '' ?>>Menu</a>
        <a href="?page=stats"<?= $page === 'stats' ? ' class="active"' : '' ?>>Stats</a>
        <a href="?page=guide"<?= $page === 'guide' ? ' class="active"' : '' ?> style="color: var(--admin-accent)">Guide</a>
    </nav>
    <div class="topbar-user">
        <span class="topbar-username"><?= e($_SESSION['admin_username'] ?? '') ?></span>
        <span class="topbar-role role-<?= e($_SESSION['admin_role'] ?? 'editor') ?>"><?= e($_SESSION['admin_role'] ?? 'editor') ?></span>
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
