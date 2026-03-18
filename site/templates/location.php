<?php
// templates/location.php — Swiss Design layout with sidebar
// All tenant contact redirected to gérances (PBVG + Gérance Ville de Lausanne)
$page = getPage('location');
?>

<section class="page-header">
  <div class="container">
    <nav class="breadcrumb"><a href="<?= SITE_URL ?>/">Accueil</a> / Location</nav>
    <h1>Location</h1>
  </div>
</section>

<section class="section-location">
  <div class="container">
    <div class="location-layout">

      <!-- Left: sidebar gérances -->
      <aside class="location-sidebar">

        <div class="location-contact-card">
          <span class="location-rule"></span>
          <h2>Gérance PBVG</h2>
          <p class="location-gerance-scope">Plaines-du-Loup, Fiches-Nord, Cojonnex, Falaises</p>
          <p class="location-address">Pilet &amp; Barras Vaud Gestion SA<br>Rue du Petit-Chêne 18<br>1003 Lausanne</p>
          <p class="location-phone">
            <a href="tel:+41213103030">021 310 30 30</a>
          </p>
          <p class="location-email">
            <a href="mailto:lausanne@pbvg.ch">lausanne@pbvg.ch</a>
          </p>
        </div>

        <div class="location-contact-card" style="margin-top: 32px;">
          <span class="location-rule"></span>
          <h2>Gérance de la Ville</h2>
          <p class="location-gerance-scope">Bonne-Espérance, Prairie, Sallaz, Béthusy, Jomini, Égralets</p>
          <p class="location-address">Gérance de la Ville de Lausanne<br>Rue du Port-Franc 18<br>1003 Lausanne</p>
          <p class="location-phone">
            <a href="tel:+41213157575">021 315 75 75</a>
          </p>
          <p class="location-email">
            <a href="mailto:gerance@lausanne.ch">gerance@lausanne.ch</a>
          </p>
        </div>

      </aside>

      <!-- Right: content -->
      <div class="location-body">

        <!-- Être locataire -->
        <div class="location-section reveal">
          <h2>Être locataire</h2>
          <p class="location-intro">La gestion locative des immeubles de la SILL SA est assurée par des gérances professionnelles. Pour toute question relative à votre logement, votre bail, une demande d'entretien ou un dossier de candidature, adressez-vous directement à la gérance en charge de votre immeuble.</p>
          <p class="location-intro" style="margin-top: 16px;">Les coordonnées de votre gérance figurent sur votre bail et sont rappelées ci-contre.</p>
        </div>

        <!-- Bonnes pratiques -->
        <div class="location-section reveal">
          <h2>Bonnes pratiques énergie</h2>
          <p class="location-intro">Nos immeubles sont conçus pour offrir un haut niveau de performance énergétique. Retrouvez les gestes simples pour économiser l'énergie et améliorer votre confort au quotidien.</p>
          <p style="margin-top: 16px;">
            <a href="<?= SITE_URL ?>/bonnes-pratiques" class="location-link-arrow">Consulter le guide des bonnes pratiques</a>
          </p>
        </div>

        <!-- Surfaces d'activités -->
        <div class="location-section reveal">
          <h2>Surfaces d'activités</h2>
          <p class="location-intro">La SILL SA dispose de surfaces d'activités dans certains de ses immeubles. Pour toute information sur les disponibilités, adressez-vous à la gérance en charge de l'immeuble concerné.</p>
        </div>

        <!-- Contenu CMS -->
        <?php if ($page && $page['content']): ?>
        <div class="location-section reveal">
          <div class="rich-text">
            <?= $page['content'] ?>
          </div>
        </div>
        <?php endif; ?>

      </div>

    </div>
  </div>
</section>
