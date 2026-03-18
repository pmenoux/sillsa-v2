<?php
// pages.php — CRUD pages (sill_pages)
// Included from layout.php inside admin-main div.
// Has access to: db(), query(), queryOne(), e(), csrfField(), csrfCheck(), flash(), $action, $id

// ---------------------------------------------------------------------------
// POST handling
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();

    $title      = trim($_POST['title']      ?? '');
    $slug_raw   = trim($_POST['slug']       ?? '');
    $parent_id  = !empty($_POST['parent_id']) ? (int) $_POST['parent_id'] : null;
    $content    = trim($_POST['content']    ?? '');
    $meta_title = trim($_POST['meta_title'] ?? '');
    $meta_desc  = trim($_POST['meta_desc']  ?? '');
    $is_active  = isset($_POST['is_active']) ? 1 : 0;

    $slug = $slug_raw !== '' ? $slug_raw : make_slug($title);

    // --- CREATE ---
    if ($action === 'create') {
        $stmt = db()->prepare(
            "INSERT INTO sill_pages (title, slug, parent_id, content, meta_title, meta_desc, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$title, $slug, $parent_id, $content, $meta_title, $meta_desc, $is_active]);
        flash('success', 'Page créée avec succès.');
        header('Location: ?page=pages');
        exit;
    }

    // --- EDIT ---
    if ($action === 'edit') {
        $page_id = (int) ($_POST['id'] ?? $id);
        $stmt = db()->prepare(
            "UPDATE sill_pages
             SET title = ?, slug = ?, parent_id = ?, content = ?, meta_title = ?, meta_desc = ?, is_active = ?
             WHERE id = ?"
        );
        $stmt->execute([$title, $slug, $parent_id, $content, $meta_title, $meta_desc, $is_active, $page_id]);
        flash('success', 'Page mise à jour.');
        header('Location: ?page=pages');
        exit;
    }

    // --- TOGGLE active ---
    if ($action === 'toggle') {
        $page_id   = (int) ($_POST['id'] ?? 0);
        $is_active = (int) ($_POST['is_active'] ?? 0);
        if ($page_id > 0) {
            $stmt = db()->prepare("UPDATE sill_pages SET is_active = ? WHERE id = ?");
            $stmt->execute([$is_active, $page_id]);
            flash('success', $is_active ? 'Page activée.' : 'Page désactivée.');
        }
        header('Location: ?page=pages');
        exit;
    }

    // --- DELETE (permanent) ---
    if ($action === 'delete') {
        $page_id = (int) ($_POST['id'] ?? 0);
        if ($page_id > 0) {
            $stmt = db()->prepare("DELETE FROM sill_pages WHERE id = ?");
            $stmt->execute([$page_id]);
            flash('success', 'Page supprimée définitivement.');
        }
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
    $parent_options = query("SELECT id, title FROM sill_pages WHERE id != ? ORDER BY title", [(int) $id]);
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

        <div class="form-row">
            <div class="form-group">
                <label for="slug">Slug</label>
                <input type="text" id="slug" name="slug" value="<?= e($item['slug']) ?>">
            </div>
            <div class="form-group">
                <label for="parent_id">Page parente</label>
                <select id="parent_id" name="parent_id">
                    <option value="">— Page principale —</option>
                    <?php foreach ($parent_options as $opt): ?>
                        <option value="<?= (int) $opt['id'] ?>" <?= ($item['parent_id'] ?? null) == $opt['id'] ? 'selected' : '' ?>><?= e($opt['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
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
    <script>initTinyMCE('#content', 350);</script>
    <?php
    return;
}

// ---------------------------------------------------------------------------
// VIEW: Create form
// ---------------------------------------------------------------------------
if ($action === 'create') {
    $parent_options = query("SELECT id, title FROM sill_pages ORDER BY title");
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

        <div class="form-row">
            <div class="form-group">
                <label for="slug">Slug</label>
                <input type="text" id="slug" name="slug" value="">
                <small class="form-hint">Auto-généré si vide</small>
            </div>
            <div class="form-group">
                <label for="parent_id">Page parente</label>
                <select id="parent_id" name="parent_id">
                    <option value="">— Page principale —</option>
                    <?php foreach ($parent_options as $opt): ?>
                        <option value="<?= (int) $opt['id'] ?>"><?= e($opt['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
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
    <script>initTinyMCE('#content', 350);</script>
    <?php
    return;
}

// ---------------------------------------------------------------------------
// VIEW: List (default)
// ---------------------------------------------------------------------------
$all_pages = query("SELECT * FROM sill_pages ORDER BY sort_order, title");

// Build hierarchy: parents first, then children grouped under parent
$parents = [];
$children = [];
foreach ($all_pages as $p) {
    if (empty($p['parent_id'])) {
        $parents[] = $p;
    } else {
        $children[(int) $p['parent_id']][] = $p;
    }
}
// Flat list: parent → its children → next parent → ...
$sorted_pages = [];
foreach ($parents as $p) {
    $sorted_pages[] = ['item' => $p, 'depth' => 0];
    foreach ($children[(int) $p['id']] ?? [] as $child) {
        $sorted_pages[] = ['item' => $child, 'depth' => 1];
    }
}
// Orphan children (parent deleted?)
foreach ($children as $pid => $kids) {
    $parent_exists = false;
    foreach ($parents as $p) { if ((int)$p['id'] === $pid) { $parent_exists = true; break; } }
    if (!$parent_exists) {
        foreach ($kids as $child) {
            $sorted_pages[] = ['item' => $child, 'depth' => 1];
        }
    }
}
?>

<div class="page-header">
    <h1>Pages</h1>
    <a href="?page=pages&action=create" class="btn btn-primary">Nouvelle page</a>
</div>

<?php if (empty($sorted_pages)): ?>
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
        <?php foreach ($sorted_pages as $entry): $page = $entry['item']; $depth = $entry['depth']; ?>
            <tr>
                <td>
                    <?php if ($depth > 0): ?>
                        <span style="color:#BBB; margin-right:4px">└</span>
                    <?php endif; ?>
                    <?= $depth > 0 ? e($page['title']) : '<strong>' . e($page['title']) . '</strong>' ?>
                </td>
                <td><code><?= e($page['slug']) ?></code></td>
                <td>
                    <form method="post" action="?page=pages&action=toggle" class="form-inline">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= (int) $page['id'] ?>">
                        <input type="hidden" name="is_active" value="<?= $page['is_active'] ? 0 : 1 ?>">
                        <label class="toggle">
                            <input type="checkbox"
                                   <?= $page['is_active'] ? 'checked' : '' ?>
                                   onchange="this.form.submit()">
                            <span class="toggle-slider"></span>
                        </label>
                    </form>
                </td>
                <td class="cell-actions">
                    <a href="?page=pages&action=edit&id=<?= (int) $page['id'] ?>" class="btn btn-sm btn-secondary">Modifier</a>
                    <form method="post" action="?page=pages&action=delete" class="form-inline"
                          onsubmit="return confirm('Supprimer définitivement cette page ?')">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= (int) $page['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Supprimer</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
