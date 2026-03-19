<?php
// menu.php — CRUD for sill_menu with reordering
// Included from layout.php; has access to db(), query(), queryOne(), e(), csrfField(), csrfCheck(), flash(), $action, $id

// ── POST HANDLERS ─────────────────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();

    $post_action = $_POST['action'] ?? '';

    // ── CREATE ────────────────────────────────────────────────────────────────
    if ($post_action === 'create') {
        $label        = trim($_POST['label'] ?? '');
        $target_value = trim($_POST['target_value'] ?? '');
        $parent_id    = (int)($_POST['parent_id'] ?? 0);
        $sort_order   = (int)($_POST['sort_order'] ?? 0);
        $is_active    = isset($_POST['is_active']) ? 1 : 0;

        if ($label === '') {
            flash('error', 'Le libellé est obligatoire.');
            header('Location: ?page=menu&action=create');
            exit;
        }

        query(
            'INSERT INTO sill_menu (label, target_value, parent_id, sort_order, is_active) VALUES (?, ?, ?, ?, ?)',
            [$label, $target_value, $parent_id, $sort_order, $is_active]
        );
        flash('success', 'Élément de menu créé.');
        header('Location: ?page=menu');
        exit;
    }

    // ── EDIT ──────────────────────────────────────────────────────────────────
    if ($post_action === 'edit') {
        $edit_id      = (int)($_POST['id'] ?? 0);
        $label        = trim($_POST['label'] ?? '');
        $target_value = trim($_POST['target_value'] ?? '');
        $parent_id    = (int)($_POST['parent_id'] ?? 0);
        $sort_order   = (int)($_POST['sort_order'] ?? 0);
        $is_active    = isset($_POST['is_active']) ? 1 : 0;

        if ($label === '' || $edit_id === 0) {
            flash('error', 'Données invalides.');
            header('Location: ?page=menu&action=edit&id=' . $edit_id);
            exit;
        }

        // Prevent item from being its own parent
        if ($parent_id === $edit_id) {
            $parent_id = 0;
        }

        query(
            'UPDATE sill_menu SET label=?, target_value=?, parent_id=?, sort_order=?, is_active=? WHERE id=?',
            [$label, $target_value, $parent_id, $sort_order, $is_active, $edit_id]
        );
        flash('success', 'Élément de menu mis à jour.');
        header('Location: ?page=menu');
        exit;
    }

    // ── DELETE (soft) ─────────────────────────────────────────────────────────
    if ($post_action === 'delete') {
        if (!canDelete()) { flash('error', 'Suppression réservée aux administrateurs.'); header('Location: ?page=menu'); exit; }
        $del_id = (int)($_POST['id'] ?? 0);
        if ($del_id > 0) {
            query('UPDATE sill_menu SET is_active=0 WHERE id=?', [$del_id]);
            flash('success', 'Élément de menu désactivé.');
        }
        header('Location: ?page=menu');
        exit;
    }

    // ── MOVE (reorder) ────────────────────────────────────────────────────────
    if ($post_action === 'move') {
        $move_id   = (int)($_POST['id'] ?? 0);
        $direction = $_POST['direction'] ?? '';

        if ($move_id > 0 && in_array($direction, ['up', 'down'], true)) {
            $current = queryOne('SELECT id, sort_order, parent_id FROM sill_menu WHERE id=?', [$move_id]);

            if ($current) {
                // Find neighbor in same parent group
                if ($direction === 'up') {
                    $neighbor = queryOne(
                        'SELECT id, sort_order FROM sill_menu WHERE parent_id=? AND sort_order < ? ORDER BY sort_order DESC LIMIT 1',
                        [(int)$current['parent_id'], (int)$current['sort_order']]
                    );
                } else {
                    $neighbor = queryOne(
                        'SELECT id, sort_order FROM sill_menu WHERE parent_id=? AND sort_order > ? ORDER BY sort_order ASC LIMIT 1',
                        [(int)$current['parent_id'], (int)$current['sort_order']]
                    );
                }

                if ($neighbor) {
                    // Swap sort_order values
                    query('UPDATE sill_menu SET sort_order=? WHERE id=?', [(int)$neighbor['sort_order'], $move_id]);
                    query('UPDATE sill_menu SET sort_order=? WHERE id=?', [(int)$current['sort_order'], (int)$neighbor['id']]);
                }
            }
        }

        header('Location: ?page=menu');
        exit;
    }
}

// ── SHARED DATA ───────────────────────────────────────────────────────────────

// Pages for datalist
$pages = query('SELECT slug, title FROM sill_pages WHERE is_active=1 ORDER BY title');

// Top-level items for parent select (parent_id = 0)
$top_level_items = query('SELECT id, label FROM sill_menu WHERE parent_id=0 AND is_active=1 ORDER BY sort_order');

// ── EDIT FORM ─────────────────────────────────────────────────────────────────
if ($action === 'edit' && $id > 0) {
    $item = queryOne('SELECT * FROM sill_menu WHERE id=?', [$id]);
    if (!$item) {
        flash('error', 'Élément de menu introuvable.');
        header('Location: ?page=menu');
        exit;
    }
    ?>
    <div class="admin-section-header">
        <h2>Modifier l'élément de menu</h2>
        <a href="?page=menu" class="btn btn-secondary">← Retour</a>
    </div>

    <form method="post" action="?page=menu" class="admin-form">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">

        <div class="form-group">
            <label for="label">Libellé <span class="required">*</span></label>
            <input type="text" id="label" name="label" value="<?= e($item['label']) ?>" required maxlength="100">
        </div>

        <div class="form-group">
            <label for="target_value">Cible (slug ou URL)</label>
            <input type="text" id="target_value" name="target_value" value="<?= e($item['target_value']) ?>"
                   list="pages-datalist" maxlength="255">
            <datalist id="pages-datalist">
                <?php foreach ($pages as $page): ?>
                    <option value="<?= e($page['slug']) ?>"><?= e($page['title']) ?></option>
                <?php endforeach; ?>
            </datalist>
            <span class="form-hint">Saisir un slug de page ou une URL absolue.</span>
        </div>

        <div class="form-group">
            <label for="parent_id">Parent</label>
            <select id="parent_id" name="parent_id">
                <option value="0">Aucun (niveau 1)</option>
                <?php foreach ($top_level_items as $parent): ?>
                    <?php if ((int)$parent['id'] === (int)$item['id']) continue; ?>
                    <option value="<?= (int)$parent['id'] ?>"<?= (int)$item['parent_id'] === (int)$parent['id'] ? ' selected' : '' ?>>
                        <?= e($parent['label']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="sort_order">Ordre</label>
            <input type="number" id="sort_order" name="sort_order" value="<?= (int)$item['sort_order'] ?>" min="0">
        </div>

        <div class="form-group form-group--checkbox">
            <label>
                <input type="checkbox" name="is_active" value="1"<?= $item['is_active'] ? ' checked' : '' ?>>
                Actif (visible dans le menu)
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="?page=menu" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
    <?php
    return;
}

// ── CREATE FORM ───────────────────────────────────────────────────────────────
if ($action === 'create') {
    // Default sort_order = MAX(sort_order) + 1
    $max_order_row = queryOne('SELECT MAX(sort_order) AS max_order FROM sill_menu');
    $next_order    = (int)($max_order_row['max_order'] ?? 0) + 1;
    ?>
    <div class="admin-section-header">
        <h2>Nouvel élément de menu</h2>
        <a href="?page=menu" class="btn btn-secondary">← Retour</a>
    </div>

    <form method="post" action="?page=menu" class="admin-form">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="create">

        <div class="form-group">
            <label for="label">Libellé <span class="required">*</span></label>
            <input type="text" id="label" name="label" value="" required maxlength="100">
        </div>

        <div class="form-group">
            <label for="target_value">Cible (slug ou URL)</label>
            <input type="text" id="target_value" name="target_value" value=""
                   list="pages-datalist" maxlength="255">
            <datalist id="pages-datalist">
                <?php foreach ($pages as $page): ?>
                    <option value="<?= e($page['slug']) ?>"><?= e($page['title']) ?></option>
                <?php endforeach; ?>
            </datalist>
            <span class="form-hint">Saisir un slug de page ou une URL absolue.</span>
        </div>

        <div class="form-group">
            <label for="parent_id">Parent</label>
            <select id="parent_id" name="parent_id">
                <option value="0">Aucun (niveau 1)</option>
                <?php foreach ($top_level_items as $parent): ?>
                    <option value="<?= (int)$parent['id'] ?>"><?= e($parent['label']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="sort_order">Ordre</label>
            <input type="number" id="sort_order" name="sort_order" value="<?= $next_order ?>" min="0">
        </div>

        <div class="form-group form-group--checkbox">
            <label>
                <input type="checkbox" name="is_active" value="1" checked>
                Actif (visible dans le menu)
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Créer l'élément</button>
            <a href="?page=menu" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
    <?php
    return;
}

// ── LIST ──────────────────────────────────────────────────────────────────────

$menu_items = query(
    'SELECT m.*, p.label AS parent_label
     FROM sill_menu m
     LEFT JOIN sill_menu p ON m.parent_id = p.id
     ORDER BY m.parent_id, m.sort_order'
);
?>
<div class="admin-section-header">
    <h2>Menu de navigation</h2>
    <a href="?page=menu&action=create" class="btn btn-primary">+ Nouvel élément</a>
</div>

<?php if (empty($menu_items)): ?>
    <p class="empty-state">Aucun élément de menu enregistré.</p>
<?php else: ?>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Ordre</th>
                <th>Libellé</th>
                <th>Cible</th>
                <th>Parent</th>
                <th>Actif</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($menu_items as $item): ?>
                <tr class="<?= $item['is_active'] ? '' : 'row-inactive' ?>">
                    <td class="order-cell">
                        <form method="post" action="?page=menu" class="inline-form order-form">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="move">
                            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                            <input type="hidden" name="direction" value="up">
                            <button type="submit" class="btn btn-xs btn-icon" title="Monter">▲</button>
                        </form>
                        <span class="sort-order-value"><?= (int)$item['sort_order'] ?></span>
                        <form method="post" action="?page=menu" class="inline-form order-form">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="move">
                            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                            <input type="hidden" name="direction" value="down">
                            <button type="submit" class="btn btn-xs btn-icon" title="Descendre">▼</button>
                        </form>
                    </td>
                    <td>
                        <?= $item['parent_id'] > 0 ? '<span class="menu-child-indent">↳ </span>' : '' ?><?= e($item['label']) ?>
                    </td>
                    <td>
                        <?php if (!empty($item['target_value'])): ?>
                            <code><?= e($item['target_value']) ?></code>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= $item['parent_label'] !== null ? e($item['parent_label']) : '<span class="text-muted">—</span>' ?>
                    </td>
                    <td>
                        <?= $item['is_active']
                            ? '<span class="badge badge-active">Oui</span>'
                            : '<span class="badge badge-inactive">Non</span>' ?>
                    </td>
                    <td class="actions-cell">
                        <a href="?page=menu&action=edit&id=<?= (int)$item['id'] ?>" class="btn btn-sm btn-secondary">Modifier</a>
                        <?php if ($item['is_active'] && canDelete()): ?>
                            <form method="post" action="?page=menu" class="inline-form" onsubmit="return confirm('Désactiver cet élément ?')">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Désactiver</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
