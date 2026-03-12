<?php
// templates/la-societe.php — Swiss Design layout for "La Société"
$page = getPage($pageData['route']);
if (!$page) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    return;
}

// Parse HTML content: extract paragraphs, lists, and images
$content = $page['content'];

// Remove the leading h2 "La Société" (duplicate of page h1)
$content = preg_replace('/<h2[^>]*>.*?<\/h2>/is', '', $content, 1);

// Extract all images
preg_match_all('/<img[^>]+>/i', $content, $imgMatches);
$images = $imgMatches[0] ?? [];

// Remove images from content flow
$textContent = preg_replace('/<img[^>]+>/i', '', $content);

// Split content into intro paragraphs and the list section
// Find the first <ul> or <ol> — everything before is intro, everything from there is operations
$introText = $textContent;
$listText = '';
if (preg_match('/(<(?:ul|ol)\b.*)/is', $textContent, $listMatch, PREG_OFFSET_MATCH)) {
    $introText = substr($textContent, 0, $listMatch[0][1]);
    $listText = $listMatch[0][0];
}

// Clean up empty paragraphs
$introText = preg_replace('/<p>\s*<\/p>/i', '', trim($introText));
$listText = preg_replace('/<p>\s*<\/p>/i', '', trim($listText));

// First image for hero column
$heroImage = $images[0] ?? '';
if ($heroImage) {
    // Add CSS class to the image
    $heroImage = str_replace('<img', '<img class="societe-hero-img"', $heroImage);
    // Ensure loading lazy
    if (strpos($heroImage, 'loading=') === false) {
        $heroImage = str_replace('<img', '<img loading="lazy"', $heroImage);
    }
}
?>

<section class="page-header">
  <div class="container">
    <h1><?= e($page['title']) ?></h1>
  </div>
</section>

<section class="societe-section">
  <div class="container">

    <!-- Intro: 2-column layout — text left, image right -->
    <div class="societe-intro">
      <div class="societe-text rich-text">
        <?= $introText ?>
      </div>
      <?php if ($heroImage): ?>
      <div class="societe-image">
        <?= $heroImage ?>
      </div>
      <?php endif; ?>
    </div>

    <?php if ($listText): ?>
    <!-- Operations: full width with accent left border -->
    <div class="societe-operations">
      <h2>Opérations</h2>
      <div class="rich-text">
        <?= $listText ?>
      </div>
    </div>
    <?php endif; ?>

    <?php
    // Carousel: featured buildings with images
    $immeubles = query('SELECT nom, slug, image_id FROM sill_immeubles WHERE is_active = 1 AND image_id IS NOT NULL AND image_id > 0 ORDER BY annee_livraison DESC LIMIT 12');
    if ($immeubles):
    ?>
    <!-- Carrousel projets phares -->
    <div class="societe-carousel">
      <h2>Nos réalisations</h2>
      <div class="carousel-track">
        <?php foreach ($immeubles as $im): ?>
        <a href="<?= SITE_URL ?>/portefeuille/<?= e($im['slug']) ?>" class="carousel-slide">
          <img src="<?= mediaUrl((int)$im['image_id']) ?>" alt="<?= e($im['nom']) ?>" loading="lazy">
          <span class="carousel-caption"><?= e($im['nom']) ?></span>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  </div>
</section>
