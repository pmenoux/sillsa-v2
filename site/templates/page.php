<?php
// templates/page.php — Generic page with Swiss 2-column layout
$page = getPage($pageData['route']);
if (!$page) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    return;
}

// Sibling pages for contextual sidebar navigation (À propos section)
$aboutRoutes = ['la-societe', 'conseil-administration', 'organisation', 'aspects-societaux', 'environnement'];
$isAboutSection = in_array($pageData['route'], $aboutRoutes);

// Fix WordPress image paths (wp-content → uploads, old domain → current site)
$content = $page['content'];
$content = str_replace('https://sillsa.ch/wp-content/uploads/', SITE_URL . '/uploads/', $content);
$content = str_replace('/wp-content/uploads/', '/uploads/', $content);

// Remove leading h2 if it duplicates the page title
$content = preg_replace('/<h2[^>]*>.*?<\/h2>/is', '', $content, 1);
?>

<section class="page-header">
  <div class="container">
    <h1><?= e($page['title']) ?></h1>
  </div>
</section>

<section class="section-about">
  <div class="container">
    <div class="about-layout">

      <?php if ($isAboutSection): ?>
      <!-- Left: contextual sidebar navigation -->
      <aside class="about-sidebar">
        <nav class="about-nav" aria-label="Section À propos">
          <?php
          $navItems = [
              'la-societe'             => 'La Société',
              'conseil-administration' => 'Conseil d\'administration',
              'organisation'           => 'L\'organisation',
              'aspects-societaux'      => 'Aspects sociétaux',
              'environnement'          => 'Environnement',
          ];
          foreach ($navItems as $route => $label): ?>
            <a href="<?= SITE_URL ?>/<?= $route ?>"
               class="about-nav-link<?= ($pageData['route'] === $route) ? ' is-active' : '' ?>">
              <?= $label ?>
            </a>
          <?php endforeach; ?>
        </nav>
      </aside>
      <?php endif; ?>

      <!-- Right: content -->
      <div class="about-body">
        <div class="rich-text">
          <?= $content ?>
        </div>
      </div>

    </div>
  </div>
</section>
