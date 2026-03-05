# sillsa.ch v2 — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Build sillsa.ch v2 — an elegant Swiss Design PHP site replacing WordPress, deployed on 26.sillsa.ch (Infomaniak).

**Architecture:** PHP 8.2 natif + MariaDB 10.6. Single-file router with .htaccess rewrite. Templates PHP, CSS Grid, Vanilla JS. No framework. Carte SVG interactive Lausanne. Chart.js pour KPI. Hierarchie typographique nombre d'or (1.618).

**Tech Stack:** PHP 8.2 (PDO), MariaDB 10.6, HTML5/CSS3 (CSS Grid), Vanilla JS, Chart.js 4.x, Google Fonts (Inter + Lato), SVG carte Lausanne.

**Design doc:** `docs/plans/2026-03-05-sillsa-v2-design.md`

**Existing assets:**
- `Datas actuelles/files/schema_sillsa_v2.sql` — 11 tables, 255 lines
- `Datas actuelles/files/migration_data.sql` — All WordPress data INSERTs
- `download (2)/web/wordpress/wp-content/uploads/` — 1362 media files
- Hosting: 26.sillsa.ch on Infomaniak, PHP 8.2, path `/sites/26.sillsa.ch`

---

## Task 1: Project scaffold + config

**Files:**
- Create: `site/index.php`
- Create: `site/config.php`
- Create: `site/.htaccess`
- Create: `site/robots.txt`

**Step 1: Create `.htaccess` with URL rewrite**

```apache
RewriteEngine On
RewriteBase /

# Do not rewrite real files or directories
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

# Route everything through index.php
RewriteRule ^(.*)$ index.php?route=$1 [QSA,L]

# Security headers
Header set X-Content-Type-Options "nosniff"
Header set X-Frame-Options "SAMEORIGIN"
Header set X-XSS-Protection "1; mode=block"

# Cache static assets
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpeg "access plus 1 month"
    ExpiresByType image/webp "access plus 1 month"
    ExpiresByType image/avif "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType image/svg+xml "access plus 1 month"
    ExpiresByType text/css "access plus 1 week"
    ExpiresByType application/javascript "access plus 1 week"
    ExpiresByType application/pdf "access plus 1 month"
</IfModule>

# Gzip compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/css application/javascript image/svg+xml
</IfModule>
```

**Step 2: Create `config.php`**

```php
<?php
// config.php — SILL SA sillsa.ch v2
// BDD credentials: fill in after creating MariaDB on Infomaniak

define('DB_HOST', 'localhost');          // Infomaniak: check actual host
define('DB_NAME', 'sillsa_v2');         // To be created
define('DB_USER', 'sillsa_admin');      // To be created
define('DB_PASS', '');                  // To be set
define('DB_CHARSET', 'utf8mb4');

define('SITE_URL', 'https://26.sillsa.ch');
define('SITE_NAME', 'SILL SA');
define('SITE_TAGLINE', 'Societe Immobiliere Lausannoise pour le Logement SA');

define('UPLOADS_DIR', __DIR__ . '/uploads');
define('UPLOADS_URL', SITE_URL . '/uploads');

// PDO connection
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}
```

**Step 3: Create `index.php` — Router**

```php
<?php
// index.php — SILL SA Router
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

$route = trim($_GET['route'] ?? '', '/');
$segments = $route ? explode('/', $route) : [];
$page = $segments[0] ?? 'accueil';
$slug = $segments[1] ?? null;

// Route map
$routes = [
    'accueil'                => 'templates/accueil.php',
    ''                       => 'templates/accueil.php',
    'la-societe'             => 'templates/page.php',
    'conseil-administration' => 'templates/ca.php',
    'organisation'           => 'templates/page.php',
    'environnement'          => 'templates/page.php',
    'aspects-societaux'      => 'templates/page.php',
    'portefeuille'           => 'templates/portefeuille.php',
    'chronologie'            => 'templates/chronologie.php',
    'location'               => 'templates/location.php',
    'publications'           => 'templates/publications.php',
];

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
```

**Step 4: Create `robots.txt`**

```
User-agent: *
Allow: /
Sitemap: https://26.sillsa.ch/sitemap.xml
```

**Step 5: Verify structure**

```
site/
├── index.php
├── config.php
├── .htaccess
└── robots.txt
```

---

## Task 2: Includes — functions, header, footer

**Files:**
- Create: `site/includes/functions.php`
- Create: `site/includes/header.php`
- Create: `site/includes/footer.php`

**Step 1: Create `functions.php`**

```php
<?php
// includes/functions.php — Helper functions

/**
 * Execute a prepared query and return all results.
 */
function query(string $sql, array $params = []): array {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Execute a prepared query and return first result.
 */
function queryOne(string $sql, array $params = []): ?array {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Get a site setting value.
 */
function setting(string $key): ?string {
    $row = queryOne('SELECT setting_value FROM sill_settings WHERE setting_key = ?', [$key]);
    return $row ? $row['setting_value'] : null;
}

/**
 * Get menu items (with children).
 */
function getMenu(): array {
    $items = query('SELECT * FROM sill_menu WHERE is_active = 1 ORDER BY sort_order');
    $menu = [];
    $children = [];
    foreach ($items as $item) {
        if ($item['parent_id'] == 0) {
            $menu[$item['id']] = $item;
            $menu[$item['id']]['children'] = [];
        } else {
            $children[] = $item;
        }
    }
    foreach ($children as $child) {
        if (isset($menu[$child['parent_id']])) {
            $menu[$child['parent_id']]['children'][] = $child;
        }
    }
    return array_values($menu);
}

/**
 * Get page data by slug.
 */
function getPage(string $slug): ?array {
    return queryOne('SELECT * FROM sill_pages WHERE slug = ? AND is_active = 1', [$slug]);
}

/**
 * Get media URL by ID.
 */
function mediaUrl(int $id): string {
    $media = queryOne('SELECT filepath FROM sill_medias WHERE id = ?', [$id]);
    if (!$media) return UPLOADS_URL . '/placeholder.jpg';
    // Convert WP path to local
    $path = str_replace('/wp-content/uploads/', '/uploads/', $media['filepath']);
    return SITE_URL . $path;
}

/**
 * Sanitize output.
 */
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Check if current route matches.
 */
function isActive(string $route): string {
    global $pageData;
    return ($pageData['route'] === $route) ? ' class="active"' : '';
}
```

**Step 2: Create `header.php`**

```php
<?php
// includes/header.php
$menuItems = getMenu();
$currentRoute = $pageData['route'] ?? '';

// Page-specific meta
$metaTitle = 'SILL SA';
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

    <!-- Fonts: Inter + Lato -->
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
```

**Step 3: Create `footer.php`**

```php
<?php // includes/footer.php ?>
</main>

<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-col">
                <h3>SILL SA</h3>
                <p><?= e(setting('site_tagline') ?? '') ?></p>
            </div>
            <div class="footer-col">
                <h3>Contact</h3>
                <p><?= e(setting('contact_address') ?? '') ?></p>
                <p><a href="mailto:<?= e(setting('contact_email') ?? '') ?>"><?= e(setting('contact_email') ?? '') ?></a></p>
            </div>
            <div class="footer-col">
                <h3>Navigation</h3>
                <ul>
                    <li><a href="<?= SITE_URL ?>/la-societe">La Societe</a></li>
                    <li><a href="<?= SITE_URL ?>/portefeuille">Portefeuille</a></li>
                    <li><a href="<?= SITE_URL ?>/chronologie">Chronologie</a></li>
                    <li><a href="<?= SITE_URL ?>/publications">Publications</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> SILL SA. Tous droits reserves.</p>
        </div>
    </div>
</footer>

<script src="<?= SITE_URL ?>/assets/js/main.js" defer></script>
</body>
</html>
```

---

## Task 3: CSS — Swiss Design + Golden Ratio typography

**Files:**
- Create: `site/assets/css/style.css`

**Step 1: Write complete CSS**

This is the core visual identity. Golden ratio scale (1.618):
- Base: 14.67px (11pt)
- Chapeau: 23.73px
- H2: 38.4px
- H1: 62.1px

Colors: `#FFFFFF` / `#FAFAF8` (warm bg) / `#1A1A1A` (text) / `#FF0000` (accent rouge suisse)

Grid: 12 columns, max-width 1200px.

The CSS must include:
- Reset/normalize
- CSS custom properties (design tokens)
- Typography scale
- Grid system
- Header/nav (fixed, white, red active indicator)
- Mobile hamburger nav
- Hero section
- KPI counter cards
- Timeline vertical (left/right alternating, mobile stacked)
- Portefeuille grid + SVG carte overlay
- Footer
- Utility classes
- Animations (fade-in on scroll)
- Print styles

Full CSS file: ~400-500 lines. Write the complete file in one step.

---

## Task 4: JavaScript — main.js

**Files:**
- Create: `site/assets/js/main.js`

**Step 1: Write main.js**

Features:
1. **Mobile nav toggle** — hamburger menu open/close
2. **Scroll reveal** — IntersectionObserver, `.reveal` elements fade in
3. **KPI counter animation** — numbers count up when scrolled into view
4. **SVG carte interaction** — hover tooltips, click to panel
5. **Dropdown nav** — hover/click for sub-menus
6. **Smooth scroll** — for anchor links

No dependencies except Chart.js (loaded separately on pages that need it).

```javascript
// main.js — SILL SA sillsa.ch v2
document.addEventListener('DOMContentLoaded', () => {

    // 1. Mobile nav toggle
    const toggle = document.querySelector('.nav-toggle');
    const navList = document.querySelector('.nav-list');
    if (toggle && navList) {
        toggle.addEventListener('click', () => {
            const expanded = toggle.getAttribute('aria-expanded') === 'true';
            toggle.setAttribute('aria-expanded', !expanded);
            navList.classList.toggle('is-open');
        });
    }

    // 2. Scroll reveal (IntersectionObserver)
    const revealEls = document.querySelectorAll('.reveal');
    if (revealEls.length > 0) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('revealed');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.15, rootMargin: '0px 0px -50px 0px' });
        revealEls.forEach(el => observer.observe(el));
    }

    // 3. KPI counter animation
    const counters = document.querySelectorAll('[data-count]');
    if (counters.length > 0) {
        const counterObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCounter(entry.target);
                    counterObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });
        counters.forEach(el => counterObserver.observe(el));
    }

    function animateCounter(el) {
        const target = parseFloat(el.dataset.count);
        const decimals = (el.dataset.decimals) ? parseInt(el.dataset.decimals) : 0;
        const duration = 1500;
        const start = performance.now();

        function update(now) {
            const progress = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3); // ease-out cubic
            const current = target * eased;
            el.textContent = current.toFixed(decimals);
            if (progress < 1) requestAnimationFrame(update);
        }
        requestAnimationFrame(update);
    }

    // 4. SVG carte — tooltip + panel
    const mapPoints = document.querySelectorAll('.map-point');
    const infoPanel = document.querySelector('.map-info-panel');
    mapPoints.forEach(point => {
        point.addEventListener('mouseenter', (e) => {
            const tooltip = point.querySelector('.map-tooltip');
            if (tooltip) tooltip.classList.add('visible');
        });
        point.addEventListener('mouseleave', (e) => {
            const tooltip = point.querySelector('.map-tooltip');
            if (tooltip) tooltip.classList.remove('visible');
        });
        point.addEventListener('click', () => {
            const slug = point.dataset.slug;
            if (slug && infoPanel) {
                loadImmeublePanel(slug);
            }
        });
    });

    async function loadImmeublePanel(slug) {
        if (!infoPanel) return;
        infoPanel.classList.add('loading');
        try {
            const resp = await fetch(`/api/immeuble/${slug}`);
            if (!resp.ok) throw new Error('Not found');
            const html = await resp.text();
            infoPanel.innerHTML = html;
            infoPanel.classList.add('open');
        } catch (e) {
            // Fallback: navigate to detail page
            window.location.href = `/portefeuille/${slug}`;
        } finally {
            infoPanel.classList.remove('loading');
        }
    }

    // Close panel
    document.addEventListener('click', (e) => {
        if (infoPanel && e.target.closest('.panel-close')) {
            infoPanel.classList.remove('open');
        }
    });

    // 5. Dropdown nav (desktop hover, mobile click)
    const dropdowns = document.querySelectorAll('.has-dropdown');
    dropdowns.forEach(item => {
        const link = item.querySelector('a');
        const sub = item.querySelector('.dropdown');
        if (!sub) return;

        // Mobile: toggle on click
        link.addEventListener('click', (e) => {
            if (window.innerWidth < 768) {
                e.preventDefault();
                item.classList.toggle('dropdown-open');
            }
        });
    });

});
```

---

## Task 5: Page d'accueil (accueil.php) — Timeline = fil d'accueil

**Files:**
- Create: `site/templates/accueil.php`

**Step 1: Write accueil.php**

La timeline complete EST le contenu principal de l'accueil. C'est le fil narratif qui raconte la SILL SA.

Sections:
1. Hero — full-width photo + baseline
2. KPI — 4 animated counters
3. Timeline COMPLETE — les 22 jalons, verticale, filtrable par categorie

```php
<?php
// templates/accueil.php — La timeline est le fil d'accueil
$kpis = query('SELECT * FROM sill_kpi WHERE is_public = 1 ORDER BY sort_order LIMIT 4');
$allTimeline = query('SELECT * FROM sill_timeline WHERE is_active = 1 ORDER BY event_date ASC');

// Extract unique categories for filter buttons
$categories = [];
foreach ($allTimeline as $item) {
    $cat = $item['category'];
    if (!isset($categories[$cat])) {
        $categories[$cat] = ucfirst(str_replace('_', ' ', $cat));
    }
}
?>

<!-- HERO -->
<section class="hero">
    <div class="hero-image">
        <img src="<?= SITE_URL ?>/uploads/2025/04/24-11-01-_DSF0019.jpg"
             alt="SILL SA — Lausanne"
             loading="eager"
             width="1920" height="1080">
    </div>
    <div class="hero-content">
        <h1 class="hero-title">Batir le Lausanne<br>durable</h1>
        <p class="hero-subtitle">Logements de qualite depuis 2009</p>
    </div>
</section>

<!-- KPI -->
<section class="kpi-section reveal">
    <div class="container">
        <div class="kpi-grid">
            <?php foreach ($kpis as $kpi): ?>
                <div class="kpi-card">
                    <span class="kpi-value"
                          data-count="<?= e($kpi['value_num'] ?? $kpi['value_text'] ?? '0') ?>"
                          data-decimals="<?= ($kpi['unit'] === 'M CHF') ? '1' : '0' ?>">
                        0
                    </span>
                    <?php if ($kpi['unit']): ?>
                        <span class="kpi-unit"><?= e($kpi['unit']) ?></span>
                    <?php endif; ?>
                    <span class="kpi-label"><?= e($kpi['label']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- TIMELINE COMPLETE — Le fil d'accueil -->
<section id="chronologie" class="timeline-section">
    <div class="container">
        <h2 class="reveal">Notre histoire</h2>

        <!-- Category filters -->
        <div class="timeline-filters reveal" role="group" aria-label="Filtrer par categorie">
            <button class="filter-btn active" data-filter="all">Tous</button>
            <?php foreach ($categories as $key => $label): ?>
                <button class="filter-btn" data-filter="<?= e($key) ?>"><?= e($label) ?></button>
            <?php endforeach; ?>
        </div>

        <!-- Timeline verticale complete -->
        <div class="timeline">
            <div class="timeline-axis"></div>
            <?php foreach ($allTimeline as $i => $item): ?>
                <div class="timeline-item reveal <?= ($i % 2 === 0) ? 'left' : 'right' ?>"
                     data-category="<?= e($item['category']) ?>">
                    <div class="timeline-dot"></div>
                    <div class="timeline-card">
                        <span class="timeline-date"><?= e(substr($item['event_date'], 0, 4)) ?></span>
                        <h3><?= e($item['title']) ?></h3>
                        <p><?= e($item['description']) ?></p>
                        <?php if ($item['image_id']): ?>
                            <img src="<?= mediaUrl((int)$item['image_id']) ?>"
                                 alt="<?= e($item['title']) ?>"
                                 loading="lazy" width="400" height="250">
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<script>
// Timeline category filter
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const filter = btn.dataset.filter;
        document.querySelectorAll('.timeline-item').forEach(item => {
            if (filter === 'all' || item.dataset.category === filter) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });
});
</script>
```

---

## Task 6: Route /chronologie — redirection vers accueil

**Files:**
- Create: `site/templates/chronologie.php`

**Step 1: Write chronologie.php — simple redirect to homepage anchor**

La timeline complete vit sur la page d'accueil. La route `/chronologie` redirige vers `/#chronologie`.

```php
<?php
// templates/chronologie.php — Redirect to homepage timeline
header('Location: ' . SITE_URL . '/#chronologie', true, 301);
exit;
```

**Step 2: Update router in index.php**

The route `chronologie` stays in the route map but points to the redirect template.
No change needed — the existing route entry already maps to `templates/chronologie.php`.

---

## Task 7: Portefeuille + carte SVG (portefeuille.php)

**Files:**
- Create: `site/templates/portefeuille.php`
- Create: `site/assets/img/carte-lausanne.svg`

**Step 1: Write portefeuille.php**

Carte SVG Lausanne avec points interactifs + panneau lateral.

```php
<?php
// templates/portefeuille.php
$immeubles = query('SELECT * FROM sill_immeubles WHERE is_active = 1 ORDER BY sort_order');
$totalLogements = 0;
foreach ($immeubles as $im) $totalLogements += (int)$im['nb_logements'];
?>

<section class="page-header reveal">
    <div class="container">
        <h1>Portefeuille</h1>
        <p class="chapeau"><?= count($immeubles) ?> immeubles &middot; <?= $totalLogements ?> logements a Lausanne</p>
    </div>
</section>

<section class="portefeuille-section">
    <div class="container portefeuille-layout">
        <!-- Carte SVG -->
        <div class="carte-container">
            <svg viewBox="0 0 800 600" class="carte-lausanne" xmlns="http://www.w3.org/2000/svg">
                <!-- Simplified Lausanne outline — to be refined -->
                <path class="carte-outline" d="M100,300 Q200,100 400,150 Q600,200 700,350 Q650,500 400,480 Q150,450 100,300 Z" />
                <!-- Lac Leman -->
                <path class="carte-lac" d="M50,420 Q200,380 400,400 Q600,420 750,410 L750,600 L50,600 Z" />

                <?php foreach ($immeubles as $im): ?>
                    <?php if ($im['latitude'] && $im['longitude']): ?>
                        <?php
                        // Convert lat/lng to SVG coordinates (approximate for Lausanne area)
                        // Lausanne bounds: lat 46.50-46.56, lng 6.58-6.68
                        $x = (($im['longitude'] - 6.58) / 0.10) * 700 + 50;
                        $y = ((46.56 - $im['latitude']) / 0.06) * 400 + 50;
                        ?>
                        <g class="map-point" data-slug="<?= e($im['slug']) ?>" transform="translate(<?= round($x) ?>,<?= round($y) ?>)">
                            <circle r="8" class="point-circle" />
                            <circle r="4" class="point-inner" />
                            <g class="map-tooltip">
                                <rect x="-60" y="-45" width="120" height="35" rx="4" class="tooltip-bg" />
                                <text x="0" y="-25" text-anchor="middle" class="tooltip-text">
                                    <?= e($im['nom']) ?>
                                </text>
                                <text x="0" y="-15" text-anchor="middle" class="tooltip-sub">
                                    <?= (int)$im['nb_logements'] ?> logements
                                </text>
                            </g>
                        </g>
                    <?php endif; ?>
                <?php endforeach; ?>
            </svg>
        </div>

        <!-- Info panel (click on map point) -->
        <div class="map-info-panel">
            <p class="panel-placeholder">Cliquez sur un immeuble pour voir sa fiche.</p>
        </div>
    </div>

    <!-- Mobile fallback: grid list -->
    <div class="container portefeuille-grid-mobile">
        <?php foreach ($immeubles as $im): ?>
            <a href="<?= SITE_URL ?>/portefeuille/<?= e($im['slug']) ?>" class="immeuble-card reveal">
                <?php if ($im['image_id']): ?>
                    <img src="<?= mediaUrl((int)$im['image_id']) ?>" alt="<?= e($im['nom']) ?>" loading="lazy" width="400" height="250">
                <?php endif; ?>
                <div class="immeuble-card-info">
                    <h3><?= e($im['nom']) ?></h3>
                    <span><?= (int)$im['nb_logements'] ?> logements</span>
                    <?php if ($im['label_energie']): ?>
                        <span class="label-energie"><?= e($im['label_energie']) ?></span>
                    <?php endif; ?>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>
```

**Step 2: Create carte-lausanne.svg placeholder**

A simplified SVG outline of Lausanne. This will be refined later with actual cartographic data. The PHP code above generates the points dynamically from lat/lng in the database.

---

## Task 8: Fiche immeuble (immeuble.php)

**Files:**
- Create: `site/templates/immeuble.php`

**Step 1: Write immeuble.php**

```php
<?php
// templates/immeuble.php
$im = queryOne('SELECT * FROM sill_immeubles WHERE slug = ? AND is_active = 1', [$pageData['slug']]);
if (!$im) {
    http_response_code(404);
    echo '<div class="container"><h1>Immeuble non trouve</h1></div>';
    return;
}
?>

<section class="immeuble-detail">
    <div class="container">
        <a href="<?= SITE_URL ?>/portefeuille" class="back-link">&larr; Portefeuille</a>

        <div class="immeuble-hero reveal">
            <?php if ($im['image_id']): ?>
                <img src="<?= mediaUrl((int)$im['image_id']) ?>" alt="<?= e($im['nom']) ?>" width="1200" height="600">
            <?php endif; ?>
        </div>

        <div class="immeuble-content">
            <h1><?= e($im['nom']) ?></h1>
            <?php if ($im['chapeau']): ?>
                <p class="chapeau"><?= e($im['chapeau']) ?></p>
            <?php endif; ?>

            <div class="immeuble-meta reveal">
                <div class="meta-item">
                    <span class="meta-label">Adresse</span>
                    <span class="meta-value"><?= e($im['adresse'] ?? '') ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Logements</span>
                    <span class="meta-value"><?= (int)$im['nb_logements'] ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Livraison</span>
                    <span class="meta-value"><?= e($im['annee_livraison'] ?? '') ?></span>
                </div>
                <?php if ($im['label_energie']): ?>
                    <div class="meta-item">
                        <span class="meta-label">Label</span>
                        <span class="meta-value"><?= e($im['label_energie']) ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($im['description']): ?>
                <div class="immeuble-desc reveal">
                    <?= $im['description'] ?>
                </div>
            <?php endif; ?>

            <?php if ($im['details']): ?>
                <div class="immeuble-details reveal">
                    <?= $im['details'] ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
```

---

## Task 9: Pages statiques + CA + Publications

**Files:**
- Create: `site/templates/page.php`
- Create: `site/templates/ca.php`
- Create: `site/templates/publications.php`
- Create: `site/templates/location.php`
- Create: `site/templates/404.php`

**Step 1: Write page.php (generic static page)**

```php
<?php
// templates/page.php — Generic static page
$page = getPage($pageData['route']);
if (!$page) {
    http_response_code(404);
    echo '<div class="container"><h1>Page non trouvee</h1></div>';
    return;
}
?>

<section class="page-header reveal">
    <div class="container">
        <h1><?= e($page['title']) ?></h1>
    </div>
</section>

<section class="page-content">
    <div class="container content-narrow">
        <div class="rich-text reveal">
            <?= $page['content'] ?>
        </div>
    </div>
</section>
```

**Step 2: Write ca.php (Conseil d'administration)**

```php
<?php
// templates/ca.php
$membres = query('SELECT * FROM sill_membres_ca WHERE is_active = 1 ORDER BY sort_order');
?>

<section class="page-header reveal">
    <div class="container">
        <h1>Conseil d'administration</h1>
    </div>
</section>

<section class="ca-section">
    <div class="container">
        <div class="ca-grid">
            <?php foreach ($membres as $m): ?>
                <div class="ca-card reveal">
                    <?php if ($m['photo_id']): ?>
                        <img src="<?= mediaUrl((int)$m['photo_id']) ?>"
                             alt="<?= e($m['prenom'] . ' ' . $m['nom']) ?>"
                             loading="lazy" width="200" height="250">
                    <?php endif; ?>
                    <h3><?= e($m['prenom'] . ' ' . $m['nom']) ?></h3>
                    <p class="ca-fonction"><?= e($m['fonction']) ?></p>
                    <?php if ($m['bio']): ?>
                        <p class="ca-bio"><?= e($m['bio']) ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
```

**Step 3: Write publications.php**

```php
<?php
// templates/publications.php
$publications = query('SELECT p.*, m.filepath as cover_path
    FROM sill_publications p
    LEFT JOIN sill_medias m ON p.cover_image_id = m.id
    WHERE p.is_active = 1
    ORDER BY p.annee DESC');
?>

<section class="page-header reveal">
    <div class="container">
        <h1>Publications</h1>
        <p class="chapeau">Rapports annuels et documents ESG</p>
    </div>
</section>

<section class="publications-section">
    <div class="container">
        <div class="publications-grid">
            <?php foreach ($publications as $pub): ?>
                <a href="<?= SITE_URL ?>/uploads/<?= e(basename($pub['pdf_path'] ?? '')) ?>"
                   target="_blank" rel="noopener"
                   class="publication-card reveal">
                    <?php if ($pub['cover_path']): ?>
                        <img src="<?= SITE_URL ?>/uploads/<?= e(basename($pub['cover_path'])) ?>"
                             alt="<?= e($pub['title']) ?>"
                             loading="lazy" width="200" height="280">
                    <?php endif; ?>
                    <div class="publication-info">
                        <span class="publication-year"><?= e($pub['annee']) ?></span>
                        <h3><?= e($pub['title']) ?></h3>
                        <span class="publication-type"><?= e(ucfirst(str_replace('_', ' ', $pub['type']))) ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
```

**Step 4: Write location.php**

```php
<?php // templates/location.php ?>

<section class="page-header reveal">
    <div class="container">
        <h1>Location</h1>
        <p class="chapeau">Premieres locations de nos developpements neufs et surfaces d'activites.</p>
    </div>
</section>

<section class="location-section">
    <div class="container content-narrow">
        <div class="reveal">
            <h2>Logements en premiere location</h2>
            <p>Les logements de nos nouveaux developpements sont proposes en premiere location lors de leur livraison.
               Les annonces sont publiees sur cette page et sur les portails immobiliers.</p>
            <p>Actuellement, aucun logement n'est disponible en premiere location.</p>
        </div>

        <div class="reveal">
            <h2>Surfaces d'activites</h2>
            <p>La SILL SA dispose de surfaces commerciales et d'activites dans certains de ses immeubles.</p>
            <p>Pour toute demande, veuillez nous contacter :</p>
            <p>
                <strong>SILL SA</strong><br>
                <?= e(setting('contact_address') ?? '') ?><br>
                <a href="mailto:<?= e(setting('contact_email') ?? '') ?>"><?= e(setting('contact_email') ?? '') ?></a>
            </p>
        </div>
    </div>
</section>
```

**Step 5: Write 404.php**

```php
<?php // templates/404.php ?>

<section class="page-404">
    <div class="container">
        <h1>404</h1>
        <p>Page non trouvee.</p>
        <a href="<?= SITE_URL ?>/" class="btn-link">Retour a l'accueil</a>
    </div>
</section>
```

---

## Task 10: API endpoint for map panel

**Files:**
- Modify: `site/index.php` — add API route

**Step 1: Add API route for AJAX immeuble panel**

Add before the template routing in `index.php`:

```php
// API routes (AJAX)
if ($page === 'api' && ($segments[1] ?? '') === 'immeuble' && ($segments[2] ?? '')) {
    header('Content-Type: text/html; charset=utf-8');
    $im = queryOne('SELECT * FROM sill_immeubles WHERE slug = ? AND is_active = 1', [$segments[2]]);
    if (!$im) {
        http_response_code(404);
        echo '<p>Immeuble non trouve.</p>';
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
        <span>Livre en <strong><?= e($im['annee_livraison'] ?? '') ?></strong></span>
        <?php if ($im['label_energie']): ?>
            <span class="label-energie"><?= e($im['label_energie']) ?></span>
        <?php endif; ?>
    </div>
    <?php if ($im['chapeau']): ?>
        <p><?= e($im['chapeau']) ?></p>
    <?php endif; ?>
    <a href="<?= SITE_URL ?>/portefeuille/<?= e($im['slug']) ?>" class="btn-link">Voir la fiche complete &rarr;</a>
    <?php
    exit;
}
```

---

## Task 11: Chart.js integration

**Files:**
- Create: `site/assets/js/chart-config.js`

**Step 1: Write chart-config.js**

Chart.js for KPI visualizations on pages that need them (homepage or dedicated section).

```javascript
// chart-config.js — Chart.js configuration for SILL SA
// Loaded only on pages with data-chart elements

document.addEventListener('DOMContentLoaded', () => {
    const chartContainers = document.querySelectorAll('[data-chart]');
    if (chartContainers.length === 0) return;

    // SILL color palette
    const colors = {
        red: '#FF0000',
        black: '#1A1A1A',
        gray: '#999999',
        lightGray: '#E0E0E0',
        white: '#FFFFFF',
    };

    // Chart.js global defaults
    Chart.defaults.font.family = "'Lato', sans-serif";
    Chart.defaults.font.size = 13;
    Chart.defaults.color = colors.black;

    chartContainers.forEach(container => {
        const type = container.dataset.chart;
        const canvas = container.querySelector('canvas');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');

        if (type === 'patrimoine') {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: JSON.parse(container.dataset.labels || '[]'),
                    datasets: [{
                        label: 'Logements',
                        data: JSON.parse(container.dataset.values || '[]'),
                        backgroundColor: colors.red,
                        borderRadius: 2,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: colors.lightGray } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        if (type === 'repartition') {
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: JSON.parse(container.dataset.labels || '[]'),
                    datasets: [{
                        data: JSON.parse(container.dataset.values || '[]'),
                        backgroundColor: [colors.red, colors.black, colors.gray, '#CC0000', '#666666'],
                        borderWidth: 0,
                    }]
                },
                options: {
                    responsive: true,
                    cutout: '65%',
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        }
    });
});
```

---

## Task 12: SEO — sitemap.xml generation

**Files:**
- Create: `site/sitemap.php`
- Modify: `site/.htaccess` — route sitemap.xml

**Step 1: Write sitemap.php**

```php
<?php
// sitemap.php — Dynamic XML sitemap
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url><loc><?= SITE_URL ?>/</loc><priority>1.0</priority></url>
    <url><loc><?= SITE_URL ?>/la-societe</loc><priority>0.8</priority></url>
    <url><loc><?= SITE_URL ?>/conseil-administration</loc><priority>0.6</priority></url>
    <url><loc><?= SITE_URL ?>/organisation</loc><priority>0.6</priority></url>
    <url><loc><?= SITE_URL ?>/environnement</loc><priority>0.7</priority></url>
    <url><loc><?= SITE_URL ?>/aspects-societaux</loc><priority>0.6</priority></url>
    <url><loc><?= SITE_URL ?>/portefeuille</loc><priority>0.9</priority></url>
    <url><loc><?= SITE_URL ?>/chronologie</loc><priority>0.8</priority></url>
    <url><loc><?= SITE_URL ?>/location</loc><priority>0.7</priority></url>
    <url><loc><?= SITE_URL ?>/publications</loc><priority>0.7</priority></url>
    <?php
    $immeubles = query('SELECT slug, updated_at FROM sill_immeubles WHERE is_active = 1');
    foreach ($immeubles as $im):
    ?>
    <url>
        <loc><?= SITE_URL ?>/portefeuille/<?= e($im['slug']) ?></loc>
        <lastmod><?= date('Y-m-d', strtotime($im['updated_at'])) ?></lastmod>
        <priority>0.7</priority>
    </url>
    <?php endforeach; ?>
</urlset>
```

**Step 2: Add .htaccess rule for sitemap**

Add to `.htaccess` before the main rewrite rule:
```apache
RewriteRule ^sitemap\.xml$ sitemap.php [L]
```

---

## Task 13: Media upload organization

**Files:**
- This is a file-system task, no PHP code.

**Step 1: Organize uploads directory**

The WordPress uploads (1362 files) need to be copied to the new `site/uploads/` directory. The migration SQL references paths like `/wp-content/uploads/2025/04/filename.jpg`.

The `mediaUrl()` function in `functions.php` converts these paths. Two options:
1. Flat copy: all files in `uploads/` root, update SQL paths
2. Preserve structure: `uploads/2025/04/`, `uploads/2025/05/`, etc.

**Recommended: Preserve WP structure** — less risk of filename collisions.

Copy command (to run on deployment):
```bash
cp -r "download (2)/web/wordpress/wp-content/uploads/"* site/uploads/
```

Update `mediaUrl()` in functions.php to handle the path correctly:
```php
function mediaUrl(int $id): string {
    $media = queryOne('SELECT filepath FROM sill_medias WHERE id = ?', [$id]);
    if (!$media) return SITE_URL . '/assets/img/placeholder.jpg';
    $path = str_replace('/wp-content/uploads/', '/uploads/', $media['filepath']);
    return SITE_URL . $path;
}
```

---

## Task 14: Deploy to 26.sillsa.ch

**Prerequisite:** BDD MariaDB created on Infomaniak with credentials filled in `config.php`.

**Step 1: Upload files via FTP/SSH**

Upload entire `site/` directory to `/sites/26.sillsa.ch/`

**Step 2: Import SQL**

```bash
mysql -h <host> -u <user> -p <dbname> < schema_sillsa_v2.sql
mysql -h <host> -u <user> -p <dbname> < migration_data.sql
```

**Step 3: Copy uploads**

```bash
cp -r uploads/ /sites/26.sillsa.ch/uploads/
```

**Step 4: Update config.php with real credentials**

**Step 5: Test all routes**

Verify each URL returns 200:
- `/` (accueil)
- `/la-societe`
- `/conseil-administration`
- `/portefeuille`
- `/portefeuille/{first-immeuble-slug}`
- `/chronologie`
- `/location`
- `/publications`
- `/nonexistent` (should 404)

---

## Summary

| Task | Description | Files |
|------|-------------|-------|
| 1 | Project scaffold + config | index.php, config.php, .htaccess, robots.txt |
| 2 | Includes (functions, header, footer) | 3 files in includes/ |
| 3 | CSS Swiss Design + Golden Ratio | style.css (~500 lines) |
| 4 | JavaScript main.js | main.js |
| 5 | Page d'accueil | accueil.php |
| 6 | Chronologie complete | chronologie.php |
| 7 | Portefeuille + carte SVG | portefeuille.php + SVG |
| 8 | Fiche immeuble | immeuble.php |
| 9 | Pages statiques + CA + Publications + Location + 404 | 5 template files |
| 10 | API endpoint for map panel | Modify index.php |
| 11 | Chart.js integration | chart-config.js |
| 12 | SEO sitemap | sitemap.php + .htaccess update |
| 13 | Media upload organization | File system task |
| 14 | Deploy to 26.sillsa.ch | Server configuration |
