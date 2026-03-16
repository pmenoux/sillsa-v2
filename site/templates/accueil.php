<?php
// templates/accueil.php — Homepage (fil d'accueil)
// The timeline IS the homepage content.

// ─── Data queries ───────────────────────────────────────────────
$tagline = setting('site_tagline') ?? SITE_TAGLINE;

$kpis = query(
    "SELECT * FROM sill_kpi WHERE is_public = 1 AND category = 'patrimoine' ORDER BY sort_order"
);

$events = query(
    'SELECT t.*, m.filepath
     FROM sill_timeline t
     LEFT JOIN sill_medias m ON t.image_id = m.id
     WHERE t.is_active = 1
     ORDER BY t.event_date DESC'
);

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

// Build unique category list from timeline data (deduplicate by display label)
$categories = [];
$seenLabels = [];
foreach ($events as $ev) {
    $cat = $ev['category'] ?? '';
    $label = $categoryLabels[$cat] ?? ucfirst(str_replace('_', ' ', $cat));
    if ($cat && !isset($seenLabels[$label])) {
        $categories[$cat] = $cat;
        $seenLabels[$label] = true;
    }
}
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
<section class="section-kpi">
    <div class="container">
        <div class="kpi-grid">
            <?php foreach ($kpis as $kpi):
                $fmt = kpiFormat($kpi['value_num'] ?? null);
            ?>
                <div class="kpi-item reveal">
                    <span class="kpi-label"><?= e($kpi['label'] ?? '') ?></span>

                    <?php if ($kpi['value_num'] === null && !empty($kpi['value_text'])): ?>
                        <span class="kpi-value"><?= e($kpi['value_text']) ?></span>
                    <?php else: ?>
                        <span class="kpi-value"
                              data-count="<?= e($fmt['formatted']) ?>"
                              <?php if ($fmt['decimals'] > 0): ?>data-decimals="<?= $fmt['decimals'] ?>"<?php endif; ?>
                        >0</span>
                    <?php endif; ?>

                    <span class="kpi-unit"><?= e($kpi['unit'] ?? '') ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ════════════════════════════════════════════════════════════════
     2b. TEASER MARCHÉ → lien vers page dédiée
     ════════════════════════════════════════════════════════════════ -->
<?php
$marcheTeaser = query(
    "SELECT * FROM sill_kpi WHERE category = 'marche' AND is_public = 1 ORDER BY sort_order LIMIT 3"
);
?>
<?php if ($marcheTeaser): ?>
<section class="section-marche-teaser">
    <div class="container">
        <div class="marche-teaser-inner">
            <div class="marche-teaser-left">
                <span class="marche-rule"></span>
                <h2 class="marche-title">Contexte de marché</h2>
                <p class="marche-teaser-text">Taux, loyers, énergie — les indicateurs clés du marché immobilier romand.</p>
                <a href="<?= SITE_URL ?>/contexte" class="btn-marche">Voir les indicateurs &rarr;</a>
            </div>
            <div class="marche-teaser-right">
                <?php foreach ($marcheTeaser as $k):
                    $fmt = kpiFormat($k['value_num'] ?? null);
                ?>
                <div class="marche-teaser-kpi">
                    <span class="marche-value"
                          <?php if ($k['value_num'] !== null): ?>
                          data-count="<?= e($fmt['formatted']) ?>"
                          <?php if ($fmt['decimals'] > 0): ?>data-decimals="<?= $fmt['decimals'] ?>"<?php endif; ?>
                          <?php endif; ?>
                    ><?= ($k['value_num'] !== null) ? '0' : e($k['value_text'] ?? '') ?></span>
                    <span class="marche-unit"><?= e($k['unit'] ?? '') ?></span>
                    <span class="marche-label"><?= e($k['label'] ?? '') ?></span>
                </div>
                <?php endforeach; ?>
            </div>
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
                <?php
                    $label = $categoryLabels[$catKey] ?? ucfirst(str_replace('_', ' ', $catKey));
                    // Collect all category keys that share this label
                    $matchingKeys = [];
                    foreach ($categoryLabels as $k => $l) {
                        if ($l === $label) $matchingKeys[] = $k;
                    }
                ?>
                <button class="filter-btn" data-filter="<?= e(implode(',', $matchingKeys)) ?>">
                    <?= e($label) ?>
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

            // Filter timeline items (supports comma-separated category keys)
            var cats = btn.dataset.filter.split(',');
            items.forEach(function (item) {
                if (cats[0] === 'all' || cats.indexOf(item.dataset.category) !== -1) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
})();
</script>
