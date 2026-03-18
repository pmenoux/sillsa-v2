<?php
// templates/environnement.php — Aspects environnementaux, Swiss Design layout
$page = getPage($pageData['route']);
if (!$page) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    return;
}

// Fix WordPress image paths
$content = $page['content'];
$content = str_replace('https://sillsa.ch/wp-content/uploads/', SITE_URL . '/uploads/', $content);
$content = str_replace('/wp-content/uploads/', '/uploads/', $content);

// Extract all images
preg_match_all('/<img[^>]+\/?>/i', $content, $imgMatches);
$images = $imgMatches[0] ?? [];

// Remove images from text flow
$textContent = preg_replace('/<img[^>]+\/?>/i', '', $content);
$textContent = preg_replace('/<p>\s*<\/p>/i', '', trim($textContent));

// Identify hero image (the building/industrial photo — typically the 2nd image)
// First image is usually the scatter chart
$chartImage = $images[0] ?? '';
$heroImage = $images[1] ?? '';

// If we only have one image, use it as hero
if (count($images) === 1) {
    $heroImage = $images[0];
    $chartImage = '';
}

// Ensure hero has proper attributes
if ($heroImage) {
    if (strpos($heroImage, 'loading=') === false) {
        $heroImage = str_replace('<img', '<img loading="lazy"', $heroImage);
    }
}

// Ensure chart has proper attributes
if ($chartImage) {
    if (strpos($chartImage, 'loading=') === false) {
        $chartImage = str_replace('<img', '<img loading="lazy"', $chartImage);
    }
}

// Sidebar navigation items
$navItems = [
    'la-societe'             => 'La Société',
    'conseil-administration' => 'Conseil d\'administration',
    'organisation'           => 'L\'organisation',
    'aspects-societaux'      => 'Aspects sociétaux',
    'environnement'          => 'Environnement',
];
?>

<section class="page-header">
  <div class="container">
    <h1><?= e($page['title']) ?></h1>
  </div>
</section>

<?php if ($heroImage): ?>
<section class="env-hero">
  <div class="container">
    <?= $heroImage ?>
  </div>
</section>
<?php endif; ?>

<section class="section-about">
  <div class="container">
    <div class="about-layout">

      <!-- Left: contextual sidebar navigation -->
      <aside class="about-sidebar">
        <nav class="about-nav" aria-label="Section À propos">
          <?php foreach ($navItems as $route => $label): ?>
            <a href="<?= SITE_URL ?>/<?= $route ?>"
               class="about-nav-link<?= ($pageData['route'] === $route) ? ' is-active' : '' ?>">
              <?= $label ?>
            </a>
          <?php endforeach; ?>
        </nav>
      </aside>

      <!-- Right: content -->
      <div class="about-body">

        <?php if ($chartImage): ?>
        <div class="env-chart reveal">
          <?= $chartImage ?>
        </div>
        <?php endif; ?>

        <div class="rich-text env-text">
          <?= $textContent ?>
        </div>

      </div>

    </div>
  </div>
</section>
