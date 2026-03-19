<?php // includes/footer.php ?>
</main>

<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-col">
                <h3>SILL SA</h3>
                <p><?= e(setting('site_tagline') ?? '') ?></p>
            </div>
            <div class="footer-col">
                <h3>Contact</h3>
                <p><?= e(setting('contact_address') ?? '') ?></p>
                <p><a href="mailto:<?= e(setting('contact_email') ?? '') ?>"><?= e(setting('contact_email') ?? '') ?></a></p>
            </div>
            <div class="footer-col">
                <h3>Navigation</h3>
                <ul>
                    <?php foreach (getMenu() as $item): ?>
                        <li><a href="<?= SITE_URL ?>/<?= e($item['target_value']) ?>"><?= e($item['label']) ?></a></li>
                        <?php if (!empty($item['children'])): ?>
                            <?php foreach ($item['children'] as $child): ?>
                                <li><a href="<?= SITE_URL ?>/<?= e($child['target_value']) ?>"><?= e($child['label']) ?></a></li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> SILL SA. Tous droits réservés.</p>
        </div>
    </div>
</footer>

<!-- JSON-LD Structured Data -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "SILL SA",
  "legalName": "Société Immobilière Lausanne-Littoral SA",
  "url": "<?= SITE_URL ?>",
  "logo": "<?= SITE_URL ?>/assets/img/logo_sill_2026.svg",
  "description": "<?= e(setting('meta_description') ?? '') ?>",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "Avenue d'Ouchy 4",
    "addressLocality": "Lausanne",
    "postalCode": "1006",
    "addressCountry": "CH"
  },
  "email": "<?= e(setting('contact_email') ?? 'info@sillsa.ch') ?>",
  "telephone": "<?= e(setting('contact_phone') ?? '') ?>",
  "foundingDate": "2009",
  "areaServed": {
    "@type": "City",
    "name": "Lausanne"
  },
  "sameAs": []
}
</script>

<script src="<?= SITE_URL ?>/assets/js/main.js?v=<?= filemtime(__DIR__ . '/../assets/js/main.js') ?>" defer></script>
</body>
</html>
