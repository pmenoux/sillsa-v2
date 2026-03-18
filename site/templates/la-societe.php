<?php
// templates/la-societe.php — Swiss Design layout with sidebar navigation
$page = getPage($pageData['route']);
if (!$page) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    return;
}

// Parse HTML content — fix WordPress URLs
$content = $page['content'];
$content = str_replace('https://sillsa.ch/wp-content/uploads/', SITE_URL . '/uploads/', $content);
$content = str_replace('/wp-content/uploads/', '/uploads/', $content);

// Remove leading h2 (duplicate of page h1)
$content = preg_replace('/<h2[^>]*>.*?<\/h2>/is', '', $content, 1);

// Extract all images (including srcset)
preg_match_all('/<img[^>]+\/?>/i', $content, $imgMatches);
$images = $imgMatches[0] ?? [];

// Remove images from text flow
$textContent = preg_replace('/<img[^>]+\/?>/i', '', $content);

// Split: paragraphs before first list = intro, list onwards = operations
$introText = $textContent;
$listText = '';
if (preg_match('/(<(?:ul|ol)\b.*)/is', $textContent, $listMatch, PREG_OFFSET_CAPTURE)) {
    $introText = substr($textContent, 0, $listMatch[0][1]);
    $listText = $listMatch[0][0];
}

$introText = preg_replace('/<p>\s*<\/p>/i', '', trim($introText));
$listText = preg_replace('/<p>\s*<\/p>/i', '', trim($listText));

// Split intro paragraphs: first = chapeau, rest = body
$introParagraphs = [];
preg_match_all('/<p[^>]*>.*?<\/p>/is', $introText, $pMatches);
if (!empty($pMatches[0])) {
    $introParagraphs = $pMatches[0];
}
$chapeau = $introParagraphs[0] ?? '';
$bodyParagraphs = array_slice($introParagraphs, 1);

// Hero image
$heroImage = $images[0] ?? '';
if ($heroImage) {
    $heroImage = str_replace('<img', '<img class="societe-hero-img"', $heroImage);
    if (strpos($heroImage, 'loading=') === false) {
        $heroImage = str_replace('<img', '<img loading="lazy"', $heroImage);
    }
}

// Carousel buildings
$immeubles = query('SELECT nom, slug FROM sill_immeubles WHERE is_active = 1 ORDER BY annee_livraison DESC LIMIT 12');
?>

<section class="page-header">
  <div class="container">
    <h1><?= e($page['title']) ?></h1>
  </div>
</section>

<section class="section-about">
  <div class="container">
    <div class="about-layout">

      <!-- Left: contextual sidebar navigation -->
      <aside class="about-sidebar">
        <nav class="about-nav" aria-label="Section À propos">
          <?php
          $navItems = [
              'la-societe'             => 'La Société',
              'conseil-administration' => 'Le CA',
              'organisation'           => 'L\'organisation',
              'aspects-societaux'      => 'Aspects sociétaux',
              'environnement'          => 'Environnement',
          ];
          foreach ($navItems as $route => $label): ?>
            <a href="<?= SITE_URL ?>/<?= $route ?>"
               class="about-nav-link<?= ('la-societe' === $route) ? ' is-active' : '' ?>">
              <?= $label ?>
            </a>
          <?php endforeach; ?>
        </nav>
      </aside>

      <!-- Right: content -->
      <div class="about-body">
        <!-- Chapeau + body text -->
        <div class="societe-intro">
          <div class="societe-chapeau rich-text">
            <?= $chapeau ?>
          </div>
          <div class="societe-body rich-text">
            <?php foreach ($bodyParagraphs as $p): ?>
              <?= $p ?>
            <?php endforeach; ?>
          </div>
        </div>

        <?php if ($heroImage): ?>
        <div class="societe-image reveal">
          <?= $heroImage ?>
        </div>
        <?php endif; ?>

        <?php if ($listText): ?>
        <div class="societe-operations">
          <h2>Projets réalisés</h2>
          <div class="societe-ops-content rich-text">
            <?= $listText ?>
          </div>
        </div>
        <?php endif; ?>

        <?php if ($immeubles): ?>
        <div class="societe-carousel">
          <div class="carousel-header">
            <h2>Nos réalisations</h2>
            <div class="carousel-nav">
              <button class="carousel-btn carousel-prev" aria-label="Précédent">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
              </button>
              <button class="carousel-btn carousel-next" aria-label="Suivant">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 6 15 12 9 18"/></svg>
              </button>
            </div>
          </div>
          <div class="carousel-track">
            <?php foreach ($immeubles as $im): ?>
            <a href="<?= SITE_URL ?>/portefeuille/<?= e($im['slug']) ?>" class="carousel-slide">
              <img src="<?= immeubleCoverUrl($im['slug']) ?>" alt="<?= e($im['nom']) ?>" loading="lazy">
              <span class="carousel-caption"><?= e($im['nom']) ?></span>
            </a>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

      </div>

    </div>
  </div>
</section>
