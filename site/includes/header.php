<?php
// includes/header.php
$menuItems = getMenu();
$currentRoute = $pageData['route'] ?? '';

// Page-specific meta
$siteName = 'SILL SA — Société Immobilière Lausannoise pour le Logement';
$metaTitle = $siteName;
$metaDesc = setting('meta_description') ?? 'La SILL SA gère un portefeuille de logements d\'utilité publique à Lausanne. Société anonyme de droit privé, actionnaire unique : Ville de Lausanne.';
$ogImage = SITE_URL . '/media/hero-accueil.jpg';

// Per-page meta from DB
if ($currentRoute && $currentRoute !== 'accueil') {
    $pageInfo = getPage($currentRoute);
    if ($pageInfo) {
        $metaTitle = e($pageInfo['meta_title'] ?? $pageInfo['title']) . ' — SILL SA';
        $metaDesc = e($pageInfo['meta_desc'] ?? $metaDesc);
    }
}

// Building detail page: override meta from immeuble data
if (!empty($pageData['slug']) && ($pageData['route'] ?? '') === 'portefeuille') {
    $immSeo = queryOne('SELECT nom, adresse, nb_logements, quartier FROM sill_immeubles WHERE slug = ? AND is_active = 1', [$pageData['slug']]);
    if ($immSeo) {
        $metaTitle = e($immSeo['nom']) . ' — Portefeuille SILL SA';
        $metaDesc = e($immSeo['nom']) . ', ' . e($immSeo['adresse']) . ' — ' . (int)$immSeo['nb_logements'] . ' logements. Portefeuille immobilier SILL SA, Lausanne.';
        // Try building cover as og:image
        $immCover = immeubleCoverUrl($pageData['slug']);
        if (!str_contains($immCover, 'placeholder')) {
            $ogImage = $immCover;
        }
    }
}

// Canonical URL
$canonicalUrl = SITE_URL . '/';
if ($currentRoute && $currentRoute !== 'accueil') {
    $canonicalUrl = SITE_URL . '/' . $currentRoute;
}
if (!empty($pageData['slug']) && ($pageData['route'] ?? '') === 'portefeuille') {
    $canonicalUrl = SITE_URL . '/portefeuille/' . $pageData['slug'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= $metaTitle ?></title>
    <meta name="description" content="<?= $metaDesc ?>">
    <meta name="robots" content="index, follow">
    <meta name="theme-color" content="#FF0000">
    <meta name="author" content="SILL SA">
    <link rel="canonical" href="<?= $canonicalUrl ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?= SITE_URL ?>/assets/img/logo_sill.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= SITE_URL ?>/assets/img/logo_sill.png">
    <link rel="apple-touch-icon" href="<?= SITE_URL ?>/assets/img/logo_sill.png">

    <!-- Open Graph -->
    <meta property="og:title" content="<?= $metaTitle ?>">
    <meta property="og:description" content="<?= $metaDesc ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= $canonicalUrl ?>">
    <meta property="og:image" content="<?= $ogImage ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:site_name" content="SILL SA">
    <meta property="og:locale" content="fr_CH">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= $metaTitle ?>">
    <meta name="twitter:description" content="<?= $metaDesc ?>">
    <meta name="twitter:image" content="<?= $ogImage ?>">

    <!-- Stylesheet -->
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
</head>
<body>

<header class="site-header">
    <div class="container">
        <a href="<?= SITE_URL ?>/" class="logo">
            <img src="<?= SITE_URL ?>/assets/img/logo_sill_2026.svg" alt="SILL SA — Société Immobilière Lausanne-Littoral" height="70">
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

