<?php
// settings.php — Key/value editor for sill_settings
// Included from layout.php; has access to db(), query(), queryOne(), e(), csrfField(), csrfCheck(), flash(), $action, $id

// ── POST HANDLER ──────────────────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();

    $settings_post = $_POST['settings'] ?? [];

    if (!is_array($settings_post)) {
        flash('error', 'Données invalides.');
        header('Location: ' . ?page=settings);
        exit;
    }

    $updated = 0;
    foreach ($settings_post as $key => $value) {
        // Sanitize key: only allow alphanumeric and underscores
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $key)) {
            continue;
        }
        query(
            'UPDATE sill_settings SET setting_value=? WHERE setting_key=?',
            [(string)$value, (string)$key]
        );
        $updated++;
    }

    flash('success', $updated . ' paramètre(s) mis à jour.');
    header('Location: ' . ?page=settings);
    exit;
}

// ── VIEW ──────────────────────────────────────────────────────────────────────

$settings = query('SELECT * FROM sill_settings ORDER BY setting_key');
?>
<div class="admin-section-header">
    <h2>Paramètres du site</h2>
</div>

<?php if (empty($settings)): ?>
    <p class="empty-state">Aucun paramètre trouvé dans la base de données.</p>
<?php else: ?>
    <form method="post" action="<?= SITE_URL ?>sill-admin/?section=settings" class="admin-form settings-form">
        <?= csrfField() ?>

        <div class="settings-grid">
            <?php foreach ($settings as $row): ?>
                <?php
                $key   = $row['setting_key'];
                $value = $row['setting_value'] ?? '';
                $is_long = strlen($value) > 100;
                ?>
                <div class="form-group settings-row">
                    <label for="setting_<?= e($key) ?>" class="setting-key">
                        <?= e($key) ?>
                    </label>
                    <?php if ($is_long): ?>
                        <textarea
                            id="setting_<?= e($key) ?>"
                            name="settings[<?= e($key) ?>]"
                            rows="4"
                            class="setting-textarea"
                        ><?= e($value) ?></textarea>
                    <?php else: ?>
                        <input
                            type="text"
                            id="setting_<?= e($key) ?>"
                            name="settings[<?= e($key) ?>]"
                            value="<?= e($value) ?>"
                            class="setting-input"
                        >
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enregistrer les paramètres</button>
        </div>
    </form>
<?php endif; ?>
