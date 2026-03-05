<?php
// templates/location.php
$page = getPage('location');
?>

<section class="page-header">
  <div class="container">
    <h1>Location</h1>
    <p class="chapeau">Premières locations et surfaces d'activités</p>
  </div>
</section>

<section class="section-location">
  <div class="container">
    <div class="content-narrow">

      <h2 class="reveal">Premières locations</h2>
      <p class="reveal">Lors de la mise en service de nos développements neufs, les logements sont proposés en première location. Pour toute demande, merci de nous contacter directement.</p>

      <div class="location-contact reveal" style="margin: 2em 0; padding: 2em; background: var(--color-bg-warm); border-radius: var(--radius);">
        <p><strong>Contact location</strong></p>
        <p><?= e(setting('contact_address') ?? '') ?></p>
        <p>Email : <a href="mailto:<?= e(setting('contact_email') ?? '') ?>"><?= e(setting('contact_email') ?? '') ?></a></p>
      </div>

      <h2 class="reveal">Surfaces d'activités</h2>
      <p class="reveal">La SILL SA dispose également de surfaces d'activités dans certains de ses immeubles. Pour toute information, n'hésitez pas à prendre contact avec nous.</p>

      <?php if ($page && $page['content']): ?>
        <div class="rich-text reveal">
          <?= $page['content'] ?>
        </div>
      <?php endif; ?>

    </div>
  </div>
</section>
