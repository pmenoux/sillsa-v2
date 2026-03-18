<?php
// templates/location.php — Swiss Design layout with sidebar
$page = getPage('location');

$address = setting('contact_address') ?? '';
$email   = setting('contact_email') ?? '';
$phone   = setting('contact_phone') ?? '';
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

      <!-- Left: sidebar contact -->
      <aside class="location-sidebar">
        <div class="location-contact-card">
          <span class="location-rule"></span>
          <h2>Contact</h2>
          <?php if ($address): ?>
            <p class="location-address"><?= nl2br(e($address)) ?></p>
          <?php endif; ?>
          <?php if ($email): ?>
            <p class="location-email">
              <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a>
            </p>
          <?php endif; ?>
          <?php if ($phone): ?>
            <p class="location-phone">
              <a href="tel:<?= e(preg_replace('/\s+/', '', $phone)) ?>"><?= e($phone) ?></a>
            </p>
          <?php endif; ?>
        </div>
      </aside>

      <!-- Right: content -->
      <div class="location-body">

        <!-- Être locataire -->
        <div class="location-section reveal">
          <h2>Être locataire</h2>
          <p class="location-intro">La SILL SA gère directement ses immeubles et privilégie une relation de proximité avec ses locataires. Pour toute demande relative à votre logement ou pour déposer un dossier de candidature, merci de nous contacter.</p>
        </div>

        <!-- Surfaces d'activités -->
        <div class="location-section reveal">
          <h2>Surfaces d'activités</h2>
          <p class="location-intro">La SILL SA dispose de surfaces d'activités dans certains de ses immeubles. Pour toute information, n'hésitez pas à prendre contact avec nous.</p>
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
