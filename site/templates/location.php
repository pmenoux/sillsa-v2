<?php
// templates/location.php — Swiss Design layout with sidebar
// All tenant contact redirected to gérances (Gérance Ville + PBBG)
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
          <h2>Gérance de la Ville</h2>
          <p class="location-gerance-scope">Plaines-du-Loup, Fiches-Nord, Cojonnex, Falaises, Bonne-Espérance, Prairie, Béthusy, Jomini, Égralets</p>
          <p class="location-address">Gérance de la Ville de Lausanne<br>Place Chauderon 9<br>1003 Lausanne</p>
          <p class="location-phone">
            <a href="tel:+41213157575">021 315 75 75</a>
          </p>
          <p class="location-email">
            <a href="mailto:gerance@lausanne.ch">gerance@lausanne.ch</a>
          </p>
        </div>

        <div class="location-contact-card" style="margin-top: 32px;">
          <span class="location-rule"></span>
          <h2>PBBG</h2>
          <p class="location-gerance-scope">Avenue de la Sallaz 1-3</p>
          <p class="location-phone">
            <a href="tel:+41213453636">021 345 36 36</a>
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

        <!-- Places de parc -->
        <div class="location-section reveal">
          <h2>Places de parc</h2>
          <p class="location-intro">Des places de stationnement sont disponibles aux Plaines-du-Loup. Pour connaître les disponibilités et les conditions, adressez-vous à la Gérance de la Ville.</p>
        </div>

        <!-- Surfaces d'activités -->
        <div class="location-section reveal">
          <h2>Surfaces d'activités</h2>
          <p class="location-intro">La SILL SA propose des surfaces d'activités aux Plaines-du-Loup (133 et 158 m², divisibles et aménageables, route des Plaines-du-Loup 51a). Pour toute information sur les disponibilités, adressez-vous à la Gérance de la Ville.</p>
        </div>

        <!-- Chercher un logement -->
        <div class="location-section reveal">
          <h2>Chercher un logement</h2>
          <p class="location-intro">Vous cherchez un appartement dans un immeuble de la SILL ? Consultez la liste des objets disponibles auprès de la gérance concernée. La majorité de nos logements sont des loyers modérés ou abordables, attribués selon les critères en vigueur.</p>
        </div>

      </div>

    </div>
  </div>
</section>
