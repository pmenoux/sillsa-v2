<?php
// index.php — SILL SA Router
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

$route = trim($_GET['route'] ?? '', '/');
$segments = $route ? explode('/', $route) : [];
$page = $segments[0] ?? 'accueil';
$slug = $segments[1] ?? null;

// API routes (AJAX for map panel)
if ($page === 'api' && ($segments[1] ?? '') === 'immeuble' && ($segments[2] ?? '')) {
    header('Content-Type: text/html; charset=utf-8');
    $im = queryOne('SELECT * FROM sill_immeubles WHERE slug = ? AND is_active = 1', [$segments[2]]);
    if (!$im) {
        http_response_code(404);
        echo '<p>Immeuble non trouvé.</p>';
        exit;
    }
    ?>
    <button class="panel-close" aria-label="Fermer">&times;</button>
    <?php if ($im['image_id']): ?>
        <img src="<?= mediaUrl((int)$im['image_id']) ?>" alt="<?= e($im['nom']) ?>" loading="lazy">
    <?php endif; ?>
    <h2><?= e($im['nom']) ?></h2>
    <p class="panel-address"><?= e($im['adresse'] ?? '') ?></p>
    <div class="panel-stats">
        <span><strong><?= (int)$im['nb_logements'] ?></strong> logements</span>
        <span>Livré en <strong><?= e($im['annee_livraison'] ?? '') ?></strong></span>
        <?php if ($im['label_energie']): ?>
            <span class="label-energie"><?= e($im['label_energie']) ?></span>
        <?php endif; ?>
    </div>
    <?php if ($im['chapeau']): ?>
        <p><?= e($im['chapeau']) ?></p>
    <?php endif; ?>
    <a href="<?= SITE_URL ?>/portefeuille/<?= e($im['slug']) ?>" class="btn-link">Voir la fiche complète &rarr;</a>
    <?php
    exit;
}

// Route map
$routes = [
    'accueil'                => 'templates/accueil.php',
    ''                       => 'templates/accueil.php',
    'la-societe'             => 'templates/la-societe.php',
    'conseil-administration' => 'templates/ca.php',
    'organisation'           => 'templates/page.php',
    'environnement'          => 'templates/page.php',
    'aspects-societaux'      => 'templates/page.php',
    'contexte'               => 'templates/contexte.php',
    'marche'                 => 'templates/contexte.php',
    'portefeuille'           => 'templates/portefeuille.php',
    'chronologie'            => 'templates/chronologie.php',
    'location'               => 'templates/location.php',
    'publications'           => 'templates/publications.php',
];

// 301 redirect: /chronologie -> /#chronologie (timeline is on homepage)
if ($page === 'chronologie') {
    header('Location: ' . SITE_URL . '/#chronologie', true, 301);
    exit;
}

// Portefeuille detail route
if ($page === 'portefeuille' && $slug) {
    $template = 'templates/immeuble.php';
} else {
    $template = $routes[$page] ?? null;
}

if (!$template || !file_exists(__DIR__ . '/' . $template)) {
    http_response_code(404);
    $template = 'templates/404.php';
}

// Page data for templates
$pageData = [
    'route' => $page,
    'slug' => $slug,
    'segments' => $segments,
];

require __DIR__ . '/includes/header.php';
require __DIR__ . '/' . $template;
require __DIR__ . '/includes/footer.php';
