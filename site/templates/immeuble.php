<?php
// templates/immeuble.php — Immeuble detail page
// Route: /portefeuille/{slug}

// ─── Data query ────────────────────────────────────────────────
$immeuble = queryOne(
    'SELECT i.*, m.filepath, m.alt_text, m.credit
     FROM sill_immeubles i
     LEFT JOIN sill_medias m ON i.image_id = m.id
     WHERE i.slug = ? AND i.is_active = 1',
    [$pageData['slug']]
);

if (!$immeuble) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    return;
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
        <?php if ($immeuble['filepath']): ?>
            <div class="immeuble-hero reveal">
                <img src="<?= mediaUrl((int)$immeuble['image_id']) ?>"
                     alt="<?= e($immeuble['alt_text'] ?? $immeuble['nom']) ?>"
                     loading="eager">
                <?php if ($immeuble['credit']): ?>
                    <p class="photo-credit">&copy; <?= e($immeuble['credit']) ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Title & chapeau -->
        <div class="content-narrow">
            <h1><?= e($immeuble['nom']) ?></h1>
            <?php if ($immeuble['chapeau']): ?>
                <p class="chapeau"><?= e($immeuble['chapeau']) ?></p>
            <?php endif; ?>
        </div>

        <!-- Key metrics grid -->
        <div class="immeuble-meta reveal">
            <?php if ($immeuble['adresse']): ?>
                <div class="meta-item">
                    <span class="meta-label">Adresse</span>
                    <span class="meta-value"><?= e($immeuble['adresse']) ?></span>
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
        </div>

        <!-- Description & details -->
        <div class="content-narrow reveal">
            <?php if ($immeuble['description']): ?>
                <p class="chapeau"><?= e($immeuble['description']) ?></p>
            <?php endif; ?>

            <?php if ($immeuble['details']): ?>
                <div class="rich-text">
                    <?= $immeuble['details'] ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
</article>
