<?php
// includes/header.php
$menuItems = getMenu();
$currentRoute = $pageData['route'] ?? '';

// Page-specific meta
$metaTitle = 'SILL SA — Société Immobilière Lausannoise pour le Logement';
$metaDesc = setting('meta_description') ?? '';

if ($currentRoute && $currentRoute !== 'accueil') {
    $pageInfo = getPage($currentRoute);
    if ($pageInfo) {
        $metaTitle = e($pageInfo['meta_title'] ?? $pageInfo['title']) . ' — SILL SA';
        $metaDesc = e($pageInfo['meta_desc'] ?? '');
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $metaTitle ?></title>
    <meta name="description" content="<?= $metaDesc ?>">
    <meta name="theme-color" content="#FF0000">

    <!-- Open Graph -->
    <meta property="og:title" content="<?= $metaTitle ?>">
    <meta property="og:description" content="<?= $metaDesc ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= SITE_URL . '/' . $currentRoute ?>">

    <!-- Fonts: Inter + Lato (variable, swap) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">

    <!-- Stylesheet -->
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>

<header class="site-header">
    <div class="container">
        <a href="<?= SITE_URL ?>/" class="logo">
            <img src="<?= SITE_URL ?>/assets/img/logo_sill.svg" alt="SILL SA" width="120" height="40">
        </a>

        <nav class="main-nav" aria-label="Navigation principale">
            <button class="nav-toggle" aria-label="Menu" aria-expanded="false">
                <span></span><span></span><span></span>
            </button>
            <ul class="nav-list">
                <?php foreach ($menuItems as $item): ?>
                    <?php if (!empty($item['children'])): ?>
                        <li class="has-dropdown">
                            <a href="<?= SITE_URL ?>/<?= e($item['target_value']) ?>"<?= isActive($item['target_value']) ?>>
                                <?= e($item['label']) ?>
                            </a>
                            <ul class="dropdown">
                                <?php foreach ($item['children'] as $child): ?>
                                    <li>
                                        <a href="<?= SITE_URL ?>/<?= e($child['target_value']) ?>"<?= isActive($child['target_value']) ?>>
                                            <?= e($child['label']) ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li>
                            <a href="<?= SITE_URL ?>/<?= e($item['target_value']) ?>"<?= isActive($item['target_value']) ?>>
                                <?= e($item['label']) ?>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </nav>
    </div>
</header>

<main>
