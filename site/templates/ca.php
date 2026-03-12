<?php
// templates/ca.php — Conseil d'administration
$membres = query('SELECT ca.*, m.filepath FROM sill_membres_ca ca LEFT JOIN sill_medias m ON ca.photo_id = m.id WHERE ca.is_active = 1 ORDER BY ca.sort_order');
?>

<section class="page-header">
  <div class="container">
    <h1>Conseil d'administration</h1>
  </div>
</section>

<section class="section-ca">
  <div class="container">
    <!-- Photo de groupe -->
    <div class="ca-group-photo reveal">
      <img src="<?= SITE_URL ?>/uploads/2025/10/ca-groupe-sill.jpg"
           alt="Conseil d'administration SILL SA" loading="lazy">
    </div>

    <div class="ca-grid">
      <?php foreach ($membres as $membre): ?>
        <div class="ca-card reveal">
          <?php if ($membre['filepath']): ?>
            <img src="<?= mediaUrl((int)$membre['photo_id']) ?>"
                 alt="<?= e($membre['prenom'] . ' ' . $membre['nom']) ?>"
                 class="ca-photo" loading="lazy">
          <?php else: ?>
            <div class="ca-photo-placeholder"></div>
          <?php endif; ?>
          <h3><?= e($membre['prenom']) ?> <?= e($membre['nom']) ?></h3>
          <p class="ca-fonction"><?= e($membre['fonction']) ?></p>
          <?php if ($membre['bio']): ?>
            <p class="ca-bio"><?= e($membre['bio']) ?></p>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if (empty($membres)): ?>
      <p class="text-center" style="color: var(--color-muted);">Les membres du Conseil d'administration seront publiés prochainement.</p>
    <?php endif; ?>
  </div>
</section>
