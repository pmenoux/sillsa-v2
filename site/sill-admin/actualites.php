<?php
// actualites.php — CRUD actualités (sill_actualites)
// Included from layout.php inside admin-main div.
// Has access to: db(), query(), queryOne(), e(), csrfField(), csrfCheck(), flash(), $action, $id

// ---------------------------------------------------------------------------
// Helper: generate slug from title
// ---------------------------------------------------------------------------
if (!function_exists('make_slug')) {
    function make_slug(string $title): string {
        $slug = strtolower(trim($title));
        $slug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $slug);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        return trim($slug, '-');
    }
}

// ---------------------------------------------------------------------------
// POST handling
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();

    // --- TOGGLE active ---
    if ($action === 'toggle') {
        $item_id   = (int) ($_POST['id'] ?? 0);
        $is_active = (int) ($_POST['is_active'] ?? 0);
        if ($item_id > 0) {
            $stmt = db()->prepare("UPDATE sill_actualites SET is_active = ? WHERE id = ?");
            $stmt->execute([$is_active, $item_id]);
            flash('success', $is_active ? 'Actualité activée.' : 'Actualité désactivée.');
        }
        header('Location: ?page=actualites');
        exit;
    }

    // --- DELETE ---
    if ($action === 'delete') {
        $item_id = (int) ($_POST['id'] ?? 0);
        if ($item_id > 0) {
            $stmt = db()->prepare("DELETE FROM sill_actualites WHERE id = ?");
            $stmt->execute([$item_id]);
            flash('success', 'Actualité supprimée.');
        }
        header('Location: ?page=actualites');
        exit;
    }

    // Common fields
    $title        = trim($_POST['title'] ?? '');
    $slug_raw     = trim($_POST['slug'] ?? '');
    $chapeau      = trim($_POST['chapeau'] ?? '');
    $description  = trim($_POST['description'] ?? '');
    $details      = trim($_POST['details'] ?? '');
    $published_at = trim($_POST['published_at'] ?? '');
    $is_active    = isset($_POST['is_active']) ? 1 : 0;

    $slug = $slug_raw !== '' ? $slug_raw : make_slug($title);

    // --- CREATE ---
    if ($action === 'create') {
        $stmt = db()->prepare(
            "INSERT INTO sill_actualites (slug, title, chapeau, description, details, published_at, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$slug, $title, $chapeau, $description, $details, $published_at, $is_active]);
        flash('success', 'Actualité créée.');
        header('Location: ?page=actualites');
        exit;
    }

    // --- EDIT ---
    if ($action === 'edit') {
        $item_id = (int) ($_POST['id'] ?? $id);
        $stmt = db()->prepare(
            "UPDATE sill_actualites
             SET slug = ?, title = ?, chapeau = ?, description = ?, details = ?, published_at = ?, is_active = ?
             WHERE id = ?"
        );
        $stmt->execute([$slug, $title, $chapeau, $description, $details, $published_at, $is_active, $item_id]);
        flash('success', 'Actualité mise à jour.');
        header('Location: ?page=actualites');
        exit;
    }
}

// ---------------------------------------------------------------------------
// VIEW: Edit form
// ---------------------------------------------------------------------------
if ($action === 'edit' && $id) {
    $item = queryOne("SELECT * FROM sill_actualites WHERE id = ?", [(int) $id]);
    if (!$item) {
        flash('error', 'Actualité introuvable.');
        header('Location: ?page=actualites');
        exit;
    }
    ?>
    <div class="page-header">
        <h1>Modifier l'actualité</h1>
        <a href="?page=actualites" class="btn btn-secondary">Retour</a>
    </div>

    <form method="post" action="?page=actualites&action=edit" class="admin-form">
        <?= csrfField() ?>
        <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">

        <div class="form-group">
            <label for="title">Titre <span class="required">*</span></label>
            <input type="text" id="title" name="title" value="<?= e($item['title']) ?>" required>
        </div>

        <div class="form-group">
            <label for="slug">Slug</label>
            <input type="text" id="slug" name="slug" value="<?= e($item['slug']) ?>">
        </div>

        <div class="form-group">
            <label for="published_at">Date de publication <span class="required">*</span></label>
            <input type="date" id="published_at" name="published_at" value="<?= e($item['published_at'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label for="chapeau">Chapeau (résumé court)</label>
            <textarea id="chapeau" name="chapeau" rows="3" style="min-height:80px"><?= e($item['chapeau'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="6" style="min-height:120px"><?= e($item['description'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label for="details">Détails (contenu complet)</label>
            <textarea id="details" name="details" rows="10"><?= e($item['details'] ?? '') ?></textarea>
        </div>

        <div class="form-group form-group--checkbox">
            <label>
                <input type="checkbox" name="is_active" value="1" <?= $item['is_active'] ? 'checked' : '' ?>>
                Actualité active
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="?page=actualites" class="btn btn-secondary">Retour</a>
        </div>
    </form>
    <script>
    CKEDITOR.replace('details', {
        language: 'fr',
        height: 350,
        removePlugins: 'elementspath',
        toolbar: [
            { name: 'basic',   items: ['Bold', 'Italic', 'Underline', 'Strike', '-', 'RemoveFormat'] },
            { name: 'para',    items: ['NumberedList', 'BulletedList', '-', 'Blockquote'] },
            { name: 'links',   items: ['Link', 'Unlink'] },
            { name: 'insert',  items: ['Image', 'Table', 'HorizontalRule'] },
            { name: 'styles',  items: ['Format'] },
            { name: 'tools',   items: ['Maximize', 'Source'] }
        ]
    });
    </script>
    <?php
    return;
}

// ---------------------------------------------------------------------------
// VIEW: Create form
// ---------------------------------------------------------------------------
if ($action === 'create') {
    ?>
    <div class="page-header">
        <h1>Nouvelle actualité</h1>
        <a href="?page=actualites" class="btn btn-secondary">Retour</a>
    </div>

    <form method="post" action="?page=actualites&action=create" class="admin-form">
        <?= csrfField() ?>

        <div class="form-group">
            <label for="title">Titre <span class="required">*</span></label>
            <input type="text" id="title" name="title" value="" required>
        </div>

        <div class="form-group">
            <label for="slug">Slug</label>
            <input type="text" id="slug" name="slug" value="">
            <small class="form-hint">Auto-généré si vide</small>
        </div>

        <div class="form-group">
            <label for="published_at">Date de publication <span class="required">*</span></label>
            <input type="date" id="published_at" name="published_at" value="<?= date('Y-m-d') ?>" required>
        </div>

        <div class="form-group">
            <label for="chapeau">Chapeau (résumé court)</label>
            <textarea id="chapeau" name="chapeau" rows="3" style="min-height:80px"></textarea>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="6" style="min-height:120px"></textarea>
        </div>

        <div class="form-group">
            <label for="details">Détails (contenu complet)</label>
            <textarea id="details" name="details" rows="10"></textarea>
        </div>

        <div class="form-group form-group--checkbox">
            <label>
                <input type="checkbox" name="is_active" value="1" checked>
                Actualité active
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="?page=actualites" class="btn btn-secondary">Retour</a>
        </div>
    </form>
    <script>
    CKEDITOR.replace('details', {
        language: 'fr',
        height: 350,
        removePlugins: 'elementspath',
        toolbar: [
            { name: 'basic',   items: ['Bold', 'Italic', 'Underline', 'Strike', '-', 'RemoveFormat'] },
            { name: 'para',    items: ['NumberedList', 'BulletedList', '-', 'Blockquote'] },
            { name: 'links',   items: ['Link', 'Unlink'] },
            { name: 'insert',  items: ['Image', 'Table', 'HorizontalRule'] },
            { name: 'styles',  items: ['Format'] },
            { name: 'tools',   items: ['Maximize', 'Source'] }
        ]
    });
    </script>
    <?php
    return;
}

// ---------------------------------------------------------------------------
// VIEW: List (default)
// ---------------------------------------------------------------------------
$all_items = query("SELECT * FROM sill_actualites ORDER BY published_at DESC, id DESC");
?>

<div class="page-header">
    <h1>Actualités</h1>
    <a href="?page=actualites&action=create" class="btn btn-primary">Nouvelle actualité</a>
</div>

<?php if (empty($all_items)): ?>
    <p class="admin-empty">Aucune actualité trouvée.</p>
<?php else: ?>
<div class="table-wrapper">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Titre</th>
                <th>Chapeau</th>
                <th>Active</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($all_items as $item): ?>
            <tr>
                <td style="white-space:nowrap"><?= e($item['published_at'] ?? '') ?></td>
                <td><strong><?= e($item['title']) ?></strong></td>
                <td class="cell-readonly"><?= e(mb_strimwidth($item['chapeau'] ?? '', 0, 80, '…')) ?></td>
                <td>
                    <form method="post" action="?page=actualites&action=toggle" class="form-inline">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                        <input type="hidden" name="is_active" value="<?= $item['is_active'] ? 0 : 1 ?>">
                        <label class="toggle">
                            <input type="checkbox"
                                   <?= $item['is_active'] ? 'checked' : '' ?>
                                   onchange="this.form.submit()">
                            <span class="toggle-slider"></span>
                        </label>
                    </form>
                </td>
                <td class="cell-actions">
                    <a href="?page=actualites&action=edit&id=<?= (int) $item['id'] ?>" class="btn btn-sm btn-secondary">Modifier</a>
                    <form method="post" action="?page=actualites&action=delete" class="form-inline"
                          onsubmit="return confirm('Supprimer définitivement cette actualité ?')">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Supprimer</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
