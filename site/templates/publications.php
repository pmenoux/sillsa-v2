<?php
// templates/publications.php
$publications = query('SELECT * FROM sill_publications WHERE is_active = 1 ORDER BY annee DESC, sort_order ASC, title');

$typeLabels = [
    'rapport_annuel' => 'Rapports annuels',
    'communique'     => 'Communiqués',
    'esg'            => 'ESG',
    'autre'          => 'Autres'
];

// Get unique types for filter
$types = array_unique(array_filter(array_column($publications, 'type')));
?>

<section class="page-header">
  <div class="container">
    <h1>Publications</h1>
    <p class="chapeau">Rapports annuels et documents officiels</p>
  </div>
</section>

<section class="section-publications">
  <div class="container">

    <!-- Type filter -->
    <?php if (count($types) > 1): ?>
    <div class="timeline-filters">
      <button class="filter-btn is-active" data-filter="all">Tous</button>
      <?php foreach ($types as $type): ?>
        <button class="filter-btn" data-filter="<?= e($type) ?>">
          <?= e($typeLabels[$type] ?? ucfirst($type)) ?>
        </button>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="publications-grid">
      <?php foreach ($publications as $pub):
          // Resolve PDF URL
          $pdf_url = '';
          if (!empty($pub['pdf_path'])) {
              if (str_starts_with($pub['pdf_path'], '/wp-content/')) {
                  // Legacy WP path → redirect to uploads/
                  $pdf_url = SITE_URL . str_replace('/wp-content/uploads/', '/uploads/', $pub['pdf_path']);
              } else {
                  $pdf_url = SITE_URL . '/uploads/' . $pub['pdf_path'];
              }
          }

          // Resolve cover image
          $cover_url = '';
          if (!empty($pub['cover_path'])) {
              $cover_url = SITE_URL . '/uploads/' . $pub['cover_path'];
          }
      ?>
        <a href="<?= e($pdf_url) ?>"
           target="_blank" rel="noopener"
           class="publication-card reveal"
           data-type="<?= e($pub['type']) ?>">
          <?php if ($cover_url): ?>
            <img src="<?= e($cover_url) ?>"
                 alt="<?= e($pub['title']) ?>" loading="lazy">
          <?php else: ?>
            <div class="publication-cover-placeholder">
              <span>PDF</span>
            </div>
          <?php endif; ?>
          <div class="publication-info">
            <span class="publication-year"><?= (int) $pub['annee'] ?></span>
            <h3><?= e($pub['title']) ?></h3>
            <span class="publication-type"><?= e($typeLabels[$pub['type']] ?? '') ?></span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Publication filter script -->
<script>
(function() {
  const filters = document.querySelectorAll('.section-publications .filter-btn');
  const cards = document.querySelectorAll('.publication-card');

  filters.forEach(btn => {
    btn.addEventListener('click', () => {
      filters.forEach(b => b.classList.remove('is-active'));
      btn.classList.add('is-active');
      const type = btn.dataset.filter;
      cards.forEach(card => {
        card.style.display = (type === 'all' || card.dataset.type === type) ? '' : 'none';
      });
    });
  });
})();
</script>
