<?php
// sitemap.php — Dynamic XML Sitemap for SILL SA
// Accessed via /sitemap.xml (rewritten by .htaccess)

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex');

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

  <!-- Homepage -->
  <url>
    <loc><?= SITE_URL ?>/</loc>
    <changefreq>weekly</changefreq>
    <priority>1.0</priority>
  </url>

  <!-- Static pages -->
<?php
$staticPages = [
    ['slug' => 'la-societe',            'priority' => '0.8', 'freq' => 'monthly'],
    ['slug' => 'conseil-administration', 'priority' => '0.7', 'freq' => 'monthly'],
    ['slug' => 'organisation',           'priority' => '0.6', 'freq' => 'monthly'],
    ['slug' => 'environnement',          'priority' => '0.6', 'freq' => 'monthly'],
    ['slug' => 'aspects-societaux',      'priority' => '0.6', 'freq' => 'monthly'],
    ['slug' => 'portefeuille',           'priority' => '0.9', 'freq' => 'weekly'],
    ['slug' => 'publications',           'priority' => '0.7', 'freq' => 'monthly'],
    ['slug' => 'location',              'priority' => '0.7', 'freq' => 'weekly'],
    ['slug' => 'contexte',             'priority' => '0.7', 'freq' => 'monthly'],
    ['slug' => 'bonnes-pratiques',     'priority' => '0.5', 'freq' => 'yearly'],
];
foreach ($staticPages as $p): ?>
  <url>
    <loc><?= SITE_URL ?>/<?= $p['slug'] ?></loc>
    <changefreq><?= $p['freq'] ?></changefreq>
    <priority><?= $p['priority'] ?></priority>
  </url>
<?php endforeach; ?>

  <!-- Building detail pages (dynamic from sill_immeubles) -->
<?php
$immeubles = query('SELECT slug, updated_at FROM sill_immeubles WHERE is_active = 1 ORDER BY sort_order');
foreach ($immeubles as $im): ?>
  <url>
    <loc><?= SITE_URL ?>/portefeuille/<?= e($im['slug']) ?></loc>
    <lastmod><?= date('Y-m-d', strtotime($im['updated_at'])) ?></lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.7</priority>
  </url>
<?php endforeach; ?>

</urlset>
