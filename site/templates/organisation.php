<?php
// templates/organisation.php — Page dédiée Organisation SILL SA
$page = getPage('organisation');
if (!$page) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    return;
}

// Equipe de direction
$equipe = [
    ['prenom' => 'Sylvie',   'nom' => 'Traimond',  'fonction' => 'Codirectrice'],
    ['prenom' => 'Pierre',   'nom' => 'Menoux',     'fonction' => 'Architecte EPF, Codirecteur'],
    ['prenom' => 'Samuel',   'nom' => 'Varone',     'fonction' => 'Responsable Finances'],
    ['prenom' => 'Patricia', 'nom' => 'Libouton',   'fonction' => 'Architecte'],
    ['prenom' => 'Ophélie',  'nom' => 'Coisne',     'fonction' => 'Architecte'],
];

// Sidebar nav
$navItems = [
    'la-societe'             => 'La Société',
    'conseil-administration' => 'Conseil d\'administration',
    'organisation'           => 'L\'organisation',
    'aspects-societaux'      => 'Aspects sociétaux',
    'environnement'          => 'Environnement',
];
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
          <?php foreach ($navItems as $route => $label): ?>
            <a href="<?= SITE_URL ?>/<?= $route ?>"
               class="about-nav-link<?= ($pageData['route'] === $route) ? ' is-active' : '' ?>">
              <?= $label ?>
            </a>
          <?php endforeach; ?>
        </nav>
      </aside>

      <!-- Right: content -->
      <div class="about-body">

        <!-- Photo d'équipe -->
        <div class="org-photo reveal">
          <img src="<?= SITE_URL ?>/media/conseiladminSILL.jpg"
               alt="Équipe SILL SA" loading="lazy">
        </div>

        <!-- Équipe de direction -->
        <div class="org-section">
          <h2 class="org-section-title">Direction</h2>
          <div class="org-grid">
            <?php foreach ($equipe as $i => $membre): ?>
              <div class="org-card<?= $i < 2 ? ' org-card--lead' : '' ?>">
                <span class="org-name"><?= e($membre['prenom']) ?> <?= e(strtoupper($membre['nom'])) ?></span>
                <span class="org-fonction"><?= e($membre['fonction']) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Organigramme textuel -->
        <div class="org-section">
          <h2 class="org-section-title">Gouvernance</h2>
          <div class="org-governance">
            <div class="org-gov-block">
              <span class="org-gov-label">Assemblée Générale</span>
              <span class="org-gov-sub">Ville de Lausanne, actionnaire unique</span>
            </div>
            <div class="org-gov-arrow"></div>
            <div class="org-gov-block">
              <span class="org-gov-label">Conseil d'administration</span>
              <span class="org-gov-sub">8 membres — <a href="<?= SITE_URL ?>/conseil-administration">Voir la composition</a></span>
            </div>
            <div class="org-gov-arrow"></div>
            <div class="org-gov-block org-gov-block--active">
              <span class="org-gov-label">Direction</span>
              <span class="org-gov-sub">Codirection Sylvie Traimond / Pierre Menoux</span>
            </div>
          </div>
        </div>

      </div>

    </div>
  </div>
</section>
