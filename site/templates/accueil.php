<?php
// templates/accueil.php — Homepage (fil d'accueil)
// The timeline IS the homepage content.

// ─── Data queries ───────────────────────────────────────────────
$tagline = setting('site_tagline') ?? SITE_TAGLINE;

$kpis = query(
    'SELECT * FROM sill_kpi WHERE is_public = 1 ORDER BY sort_order LIMIT 4'
);

$events = query(
    'SELECT t.*, m.filepath
     FROM sill_timeline t
     LEFT JOIN sill_medias m ON t.image_id = m.id
     WHERE t.is_active = 1
     ORDER BY t.event_date DESC'
);

// Build unique category list from timeline data
$categories = [];
foreach ($events as $ev) {
    $cat = $ev['category'] ?? '';
    if ($cat && !isset($categories[$cat])) {
        $categories[$cat] = $cat;
    }
}

// Category display labels (French)
$categoryLabels = [
    'gouvernance'              => 'Gouvernance',
    'projets_emblematiques'    => 'Projets',
    'strategie_financiere'     => 'Finance',
    'concours_architecturaux'  => 'Concours',
    'minergie_innovation'      => 'Minergie',
    'densification_durable'    => 'Densification',
    'collaborations_publiques' => 'Collaborations',
    'logement_etudiant'        => 'Étudiants',
    'metamorphose'             => 'Métamorphose',
    'developpement_durable'    => 'Durabilité',
    'livraisons_emblematiques' => 'Livraisons',
    'gouvernance_evolutive'    => 'Gouvernance',
    'innovation_sociale'       => 'Innovation',
    'politique_fonciere'       => 'Foncier',
    'communication_transparence' => 'Communication',
];
?>

<!-- ════════════════════════════════════════════════════════════════
     1. HERO
     ════════════════════════════════════════════════════════════════ -->
<?php
    // Hero image: use first immeuble image as fallback if hero-accueil.jpg doesn't exist
    $heroImg = SITE_URL . '/uploads/hero-accueil.jpg';
    $firstImmeuble = queryOne('SELECT m.filepath FROM sill_immeubles i JOIN sill_medias m ON i.image_id = m.id WHERE i.is_active = 1 ORDER BY i.sort_order LIMIT 1');
    if ($firstImmeuble) {
        $heroImg = SITE_URL . str_replace('/wp-content/uploads/', '/uploads/', $firstImmeuble['filepath']);
    }
?>
<section class="hero" style="background-image: url('<?= $heroImg ?>');">
    <div class="hero-content">
        <h1 class="hero-title">SILL SA</h1>
        <p class="hero-subtitle"><?= e($tagline) ?></p>
        <a href="#chronologie" class="hero-scroll" aria-label="Défiler vers le contenu">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
        </a>
    </div>
</section>

<!-- ════════════════════════════════════════════════════════════════
     2. KPI
     ════════════════════════════════════════════════════════════════ -->
<?php if ($kpis): ?>
<section class="section-kpi" style="background: var(--color-bg-warm);">
    <div class="container">
        <div class="kpi-grid">
            <?php foreach ($kpis as $kpi): ?>
                <div class="kpi-item reveal">
                    <?php if (!empty($kpi['value_text'])): ?>
                        <!-- Text value (e.g. année de création) — no counter animation -->
                        <span class="kpi-value"><?= e($kpi['value_text']) ?></span>
                    <?php else: ?>
                        <!-- Numeric value — animated counter -->
                        <span class="kpi-value"
                              data-count="<?= e((string)($kpi['value_num'] ?? '0')) ?>"
                              <?php if (floor((float)$kpi['value_num']) != (float)$kpi['value_num']): ?>
                                  data-decimals="1"
                              <?php endif; ?>
                        >0</span>
                    <?php endif; ?>

                    <?php if (!empty($kpi['unit'])): ?>
                        <span class="kpi-unit"><?= e($kpi['unit']) ?></span>
                    <?php endif; ?>

                    <span class="kpi-label"><?= e($kpi['label'] ?? '') ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ════════════════════════════════════════════════════════════════
     3. TIMELINE (main content — fil d'accueil)
     ════════════════════════════════════════════════════════════════ -->
<section class="section-timeline" id="chronologie">
    <div class="container">
        <h2 class="reveal">Notre histoire</h2>

        <!-- Filter buttons -->
        <?php if ($categories): ?>
        <div class="timeline-filters reveal">
            <button class="filter-btn is-active" data-filter="all">Tous</button>
            <?php foreach ($categories as $catKey): ?>
                <button class="filter-btn" data-filter="<?= e($catKey) ?>">
                    <?= e($categoryLabels[$catKey] ?? ucfirst(str_replace('_', ' ', $catKey))) ?>
                </button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Timeline -->
        <div class="timeline">
            <div class="timeline-axis"></div>

            <?php foreach ($events as $ev): ?>
                <?php
                    $cat      = $ev['category'] ?? '';
                    $year     = date('Y', strtotime($ev['event_date']));
                    $title    = $ev['title'] ?? '';
                    $desc     = $ev['description'] ?? '';
                    $filepath = $ev['filepath'] ?? '';
                ?>
                <div class="timeline-item reveal" data-category="<?= e($cat) ?>">
                    <div class="timeline-dot"></div>
                    <div class="timeline-card">
                        <span class="timeline-date"><?= e($year) ?></span>
                        <h3><?= e($title) ?></h3>

                        <?php if ($desc): ?>
                            <p><?= e($desc) ?></p>
                        <?php endif; ?>

                        <?php if ($filepath): ?>
                            <?php
                                $imgPath = str_replace('/wp-content/uploads/', '/uploads/', $filepath);
                            ?>
                            <img src="<?= SITE_URL . e($imgPath) ?>"
                                 alt="<?= e($title) ?>"
                                 loading="lazy"
                                 class="timeline-img">
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ════════════════════════════════════════════════════════════════
     4. INLINE JS — Timeline category filter
     ════════════════════════════════════════════════════════════════ -->
<script>
(function () {
    var filters = document.querySelectorAll('.filter-btn');
    var items   = document.querySelectorAll('.timeline-item');

    filters.forEach(function (btn) {
        btn.addEventListener('click', function () {
            // Update active state
            filters.forEach(function (b) { b.classList.remove('is-active'); });
            btn.classList.add('is-active');

            // Filter timeline items
            var cat = btn.dataset.filter;
            items.forEach(function (item) {
                if (cat === 'all' || item.dataset.category === cat) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
})();
</script>
