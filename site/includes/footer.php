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
                    <li><a href="<?= SITE_URL ?>/la-societe">La Société</a></li>
                    <li><a href="<?= SITE_URL ?>/portefeuille">Portefeuille</a></li>
                    <li><a href="<?= SITE_URL ?>/#chronologie">Chronologie</a></li>
                    <li><a href="<?= SITE_URL ?>/publications">Publications</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> SILL SA. Tous droits réservés.</p>
        </div>
    </div>
</footer>

<script src="<?= SITE_URL ?>/assets/js/main.js" defer></script>
</body>
</html>
