<?php
// pages.php — CRUD pages (sill_pages)
// Included from layout.php inside admin-main div.
// Has access to: db(), query(), queryOne(), e(), csrfField(), csrfCheck(), flash(), $action, $id

// ---------------------------------------------------------------------------
// Helper: generate slug from title
// ---------------------------------------------------------------------------
function make_slug(string $title): string {
    $slug = strtolower(trim($title));
    $slug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $slug);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}

// ---------------------------------------------------------------------------
// POST handling
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();

    $title      = trim($_POST['title']      ?? '');
    $slug_raw   = trim($_POST['slug']       ?? '');
    $content    = trim($_POST['content']    ?? '');
    $meta_title = trim($_POST['meta_title'] ?? '');
    $meta_desc  = trim($_POST['meta_desc']  ?? '');
    $is_active  = isset($_POST['is_active']) ? 1 : 0;

    $slug = $slug_raw !== '' ? $slug_raw : make_slug($title);

    // --- CREATE ---
    if ($action === 'create') {
        $stmt = db()->prepare(
            "INSERT INTO sill_pages (title, slug, content, meta_title, meta_desc, is_active)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$title, $slug, $content, $meta_title, $meta_desc, $is_active]);
        flash('success', 'Page créée avec succès.');
        header('Location: ?page=pages');
        exit;
    }

    // --- EDIT ---
    if ($action === 'edit') {
        $page_id = (int) ($_POST['id'] ?? $id);
        $stmt = db()->prepare(
            "UPDATE sill_pages
             SET title = ?, slug = ?, content = ?, meta_title = ?, meta_desc = ?, is_active = ?
             WHERE id = ?"
        );
        $stmt->execute([$title, $slug, $content, $meta_title, $meta_desc, $is_active, $page_id]);
        flash('success', 'Page mise à jour.');
        header('Location: ?page=pages');
        exit;
    }

    // --- DELETE (soft) ---
    if ($action === 'delete') {
        $page_id = (int) ($_POST['id'] ?? $id);
        $stmt = db()->prepare("UPDATE sill_pages SET is_active = 0 WHERE id = ?");
        $stmt->execute([$page_id]);
        flash('success', 'Page désactivée.');
        header('Location: ?page=pages');
        exit;
    }
}

// ---------------------------------------------------------------------------
// VIEW: Edit form
// ---------------------------------------------------------------------------
if ($action === 'edit' && $id) {
    $item = queryOne("SELECT * FROM sill_pages WHERE id = ?", [(int) $id]);
    if (!$item) {
        flash('error', 'Page introuvable.');
        header('Location: ?page=pages');
        exit;
    }
    ?>
    <div class="page-header">
        <h1>Modifier la page</h1>
        <a href="?page=pages" class="btn btn-secondary">Retour</a>
    </div>

    <form method="post" action="?page=pages&action=edit" class="admin-form">
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
            <label for="content">Contenu</label>
            <textarea id="content" name="content" rows="10"><?= e($item['content'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label for="meta_title">Meta title</label>
            <input type="text" id="meta_title" name="meta_title" value="<?= e($item['meta_title'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="meta_desc">Meta description</label>
            <textarea id="meta_desc" name="meta_desc" rows="3"><?= e($item['meta_desc'] ?? '') ?></textarea>
        </div>

        <div class="form-group form-group--checkbox">
            <label>
                <input type="checkbox" name="is_active" value="1" <?= $item['is_active'] ? 'checked' : '' ?>>
                Page active
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="?page=pages" class="btn btn-secondary">Retour</a>
        </div>
    </form>
    <?php
    return;
}

// ---------------------------------------------------------------------------
// VIEW: Create form
// ---------------------------------------------------------------------------
if ($action === 'create') {
    ?>
    <div class="page-header">
        <h1>Nouvelle page</h1>
        <a href="?page=pages" class="btn btn-secondary">Retour</a>
    </div>

    <form method="post" action="?page=pages&action=create" class="admin-form">
        <?= csrfField() ?>

        <div class="form-group">
            <label for="title">Titre <span class="required">*</span></label>
            <input type="text" id="title" name="title" value="" required>
        </div>

        <div class="form-group">
            <label for="slug">Slug</label>
            <input type="text" id="slug" name="slug" value="">
            <small class="form-hint">Auto-genere si vide</small>
        </div>

        <div class="form-group">
            <label for="content">Contenu</label>
            <textarea id="content" name="content" rows="10"></textarea>
        </div>

        <div class="form-group">
            <label for="meta_title">Meta title</label>
            <input type="text" id="meta_title" name="meta_title" value="">
        </div>

        <div class="form-group">
            <label for="meta_desc">Meta description</label>
            <textarea id="meta_desc" name="meta_desc" rows="3"></textarea>
        </div>

        <div class="form-group form-group--checkbox">
            <label>
                <input type="checkbox" name="is_active" value="1" checked>
                Page active
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="?page=pages" class="btn btn-secondary">Retour</a>
        </div>
    </form>
    <?php
    return;
}

// ---------------------------------------------------------------------------
// VIEW: List (default)
// ---------------------------------------------------------------------------
$all_pages = query("SELECT * FROM sill_pages ORDER BY id DESC");
?>

<div class="page-header">
    <h1>Pages</h1>
    <a href="?page=pages&action=create" class="btn btn-primary">Nouvelle page</a>
</div>

<?php if (empty($all_pages)): ?>
    <p class="admin-empty">Aucune page trouvée.</p>
<?php else: ?>
<div class="table-wrapper">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Titre</th>
                <th>Slug</th>
                <th>Active</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($all_pages as $page): ?>
            <tr>
                <td><?= e($page['title']) ?></td>
                <td><code><?= e($page['slug']) ?></code></td>
                <td><?= $page['is_active'] ? 'Oui' : 'Non' ?></td>
                <td class="cell-actions">
                    <a href="?page=pages&action=edit&id=<?= (int) $page['id'] ?>" class="btn btn-sm btn-secondary">Modifier</a>
                    <form method="post" action="?page=pages&action=delete" class="form-inline"
                          onsubmit="return confirm('Désactiver cette page ?')">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= (int) $page['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Désactiver</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
