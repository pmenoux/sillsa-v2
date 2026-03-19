<?php
// templates/ca.php — Conseil d'administration — Swiss typographic layout
$membres = query('SELECT ca.*, m.filepath FROM sill_membres_ca ca LEFT JOIN sill_medias m ON ca.photo_id = m.id WHERE ca.is_active = 1 ORDER BY ca.sort_order');
?>

<section class="page-header">
  <div class="container">
    <h1>Conseil d'administration</h1>
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
              'conseil-administration' => 'Conseil d\'administration',
              'organisation'           => 'L\'organisation',
              'aspects-societaux'      => 'Aspects sociétaux',
              'environnement'          => 'Environnement',
          ];
          foreach ($navItems as $route => $label): ?>
            <a href="<?= SITE_URL ?>/<?= $route ?>"
               class="about-nav-link<?= ('conseil-administration' === $route) ? ' is-active' : '' ?>">
              <?= $label ?>
            </a>
          <?php endforeach; ?>
        </nav>
      </aside>

      <!-- Right: content -->
      <div class="about-body">
        <!-- Photo de groupe -->
        <div class="ca-group-photo reveal">
          <img src="<?= SITE_URL ?>/media/conseiladminSILL.jpg"
               alt="Conseil d'administration SILL SA" loading="lazy">
        </div>

        <div class="ca-grid">
          <?php foreach ($membres as $membre): ?>
            <div class="ca-card reveal">
              <span class="ca-card-name"><?= e($membre['prenom']) ?> <?= e($membre['nom']) ?></span>
              <span class="ca-fonction<?= in_array($membre['fonction'], ['Présidente', 'Président', 'Vice-président', 'Vice-présidente']) ? ' ca-fonction-president' : '' ?>"><?= e($membre['fonction']) ?></span>
            </div>
          <?php endforeach; ?>
        </div>

        <?php if (empty($membres)): ?>
          <p style="color: var(--color-muted);">Les membres du Conseil d'administration seront publiés prochainement.</p>
        <?php endif; ?>
      </div>

    </div>
  </div>
</section>
