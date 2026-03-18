<?php
// templates/immeuble.php — Immeuble detail page
// Route: /portefeuille/{slug}

// ─── Data query ────────────────────────────────────────────────
$immeuble = queryOne(
    'SELECT * FROM sill_immeubles WHERE slug = ? AND is_active = 1',
    [$pageData['slug']]
);

if (!$immeuble) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    return;
}

// ─── Process details: split paragraphs + extract architect signature ───
$detailsRaw = trim($immeuble['details'] ?? '');
$detailsHtml = '';
$architectSignature = '';

if ($detailsRaw) {
    // If content has no <p> tags, it's plain text — convert to paragraphs
    if (stripos($detailsRaw, '<p>') === false) {
        // Split on double newlines (paragraph breaks)
        $blocks = preg_split('/\n\s*\n/', $detailsRaw);
        $blocks = array_map('trim', $blocks);
        $blocks = array_filter($blocks);

        // Check if last block is architect signature (starts with "Pour ")
        $lastBlock = end($blocks);
        if ($lastBlock && preg_match('/^Pour\s+/i', $lastBlock)) {
            $architectSignature = array_pop($blocks);
        }

        // Wrap remaining blocks in <p> tags
        $detailsHtml = '';
        foreach ($blocks as $block) {
            $detailsHtml .= '<p>' . nl2br(e($block)) . '</p>' . "\n";
        }
    } else {
        // Already HTML — extract signature from last <p>
        $detailsHtml = $detailsRaw;
        if (preg_match('#<p>\s*Pour\s+.+?</p>\s*$#si', $detailsHtml, $m)) {
            $architectSignature = strip_tags($m[0]);
            $detailsHtml = str_replace($m[0], '', $detailsHtml);
        }
    }
}
?>

<!-- ════════════════════════════════════════════════════════════════
     1. PAGE HEADER — Back link
     ════════════════════════════════════════════════════════════════ -->
<section class="page-header">
    <div class="container">
        <a href="<?= SITE_URL ?>/portefeuille" class="back-link">Retour au portefeuille</a>
    </div>
</section>

<!-- ════════════════════════════════════════════════════════════════
     2. ARTICLE — Immeuble detail
     ════════════════════════════════════════════════════════════════ -->
<article class="section-immeuble">
    <div class="container">

        <!-- Hero image -->
        <?php
        $coverUrl = immeubleCoverUrl($immeuble['slug'], (int) $immeuble['image_id'] ?: null);
        if (!str_contains($coverUrl, 'placeholder')):
        ?>
            <div class="immeuble-hero reveal">
                <img src="<?= e($coverUrl) ?>"
                     alt="<?= e($immeuble['nom']) ?>"
                     loading="eager">
            </div>
        <?php endif; ?>

        <!-- Title -->
        <div class="content-narrow">
            <h1><?= e($immeuble['nom']) ?></h1>
        </div>

        <!-- 2-column Swiss layout: sidebar meta + body text -->
        <div class="immeuble-content reveal">

            <!-- Left: meta data stacked vertically -->
            <aside class="immeuble-sidebar">
                <?php if ($immeuble['adresse']): ?>
                    <div class="meta-item">
                        <span class="meta-label">Adresse</span>
                        <span class="meta-value"><?= e($immeuble['adresse']) ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($immeuble['quartier']): ?>
                    <div class="meta-item">
                        <span class="meta-label">Quartier</span>
                        <span class="meta-value"><?= e($immeuble['quartier']) ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($immeuble['nb_logements']): ?>
                    <div class="meta-item">
                        <span class="meta-label">Logements</span>
                        <span class="meta-value"><?= (int)$immeuble['nb_logements'] ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($immeuble['annee_livraison']): ?>
                    <div class="meta-item">
                        <span class="meta-label">Livraison</span>
                        <span class="meta-value"><?= e($immeuble['annee_livraison']) ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($immeuble['label_energie']): ?>
                    <div class="meta-item">
                        <span class="meta-label">Label énergie</span>
                        <span class="meta-value"><span class="label-energie"><?= e($immeuble['label_energie']) ?></span></span>
                    </div>
                <?php endif; ?>

                <?php if ($immeuble['categorie']): ?>
                    <div class="meta-item">
                        <span class="meta-label">Catégorie</span>
                        <span class="meta-value"><?= e(ucfirst(str_replace('_', ' ', $immeuble['categorie']))) ?></span>
                    </div>
                <?php endif; ?>
                <?php
                // Mini loyer-mix bar — housing type proportion
                $loyerMix = json_decode($immeuble['loyer_mix'] ?? 'null', true);
                if ($loyerMix):
                    $total = array_sum($loyerMix);
                    $labels = ['LLM' => 'Subventionné', 'LLA' => 'Contrôlé', 'LM' => 'Libre', 'ETU' => 'Étudiants'];
                    $classes = ['LLM' => 'mix-llm', 'LLA' => 'mix-lla', 'LM' => 'mix-lm', 'ETU' => 'mix-etu'];
                ?>
                <div class="loyer-mix">
                    <span class="meta-label">Répartition</span>
                    <div class="loyer-mix-bar">
                        <?php foreach ($loyerMix as $type => $count): ?>
                            <span class="<?= $classes[$type] ?? '' ?>" style="flex: <?= $count ?>"></span>
                        <?php endforeach; ?>
                    </div>
                    <div class="loyer-mix-legend">
                        <?php foreach ($loyerMix as $type => $count): ?>
                            <span class="<?= $classes[$type] ?? '' ?>"><?= $labels[$type] ?? $type ?> <?= $count ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

            </aside>

            <!-- Right: chapeau + description + rich-text -->
            <div class="immeuble-body">
                <?php if ($immeuble['chapeau']): ?>
                    <p class="page-chapeau"><?= e($immeuble['chapeau']) ?></p>
                <?php endif; ?>

                <?php if ($immeuble['description']): ?>
                    <p class="immeuble-description"><?= e($immeuble['description']) ?></p>
                <?php endif; ?>

                <?php if ($detailsHtml): ?>
                    <div class="rich-text">
                        <?= $detailsHtml ?>
                    </div>
                <?php endif; ?>

                <?php if ($architectSignature): ?>
                    <div class="architect-signature">
                        <?= nl2br(e($architectSignature)) ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <?php
        $galerie = immeubleGalerie($immeuble['slug']);
        if ($galerie):
        ?>
        <!-- Gallery -->
        <div class="immeuble-galerie reveal">
            <h2>Galerie</h2>
            <div class="galerie-grid">
                <?php foreach ($galerie as $img): ?>
                    <a href="<?= e($img['url']) ?>" class="galerie-item" data-caption="<?= e($img['caption']) ?>">
                        <img src="<?= e($img['url']) ?>" alt="<?= e($img['caption']) ?>" loading="lazy">
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
</article>
