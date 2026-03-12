<?php
// timeline.php — CRUD événements timeline (sill_timeline)
// Included from layout.php inside admin-main div.

// ---------------------------------------------------------------------------
// Categories (enum values → labels lisibles)
// ---------------------------------------------------------------------------
$categories = [
    'gouvernance'                => 'Gouvernance',
    'projets_emblematiques'      => 'Projets emblématiques',
    'strategie_financiere'       => 'Stratégie financière',
    'concours_architecturaux'    => 'Concours architecturaux',
    'minergie_innovation'        => 'Minergie / Innovation',
    'densification_durable'      => 'Densification durable',
    'collaborations_publiques'   => 'Collaborations publiques',
    'logement_etudiant'          => 'Logement étudiant',
    'metamorphose'               => 'Métamorphose',
    'developpement_durable'      => 'Développement durable',
    'livraisons_emblematiques'   => 'Livraisons emblématiques',
    'gouvernance_evolutive'      => 'Gouvernance évolutive',
    'innovation_sociale'         => 'Innovation sociale',
    'politique_fonciere'         => 'Politique foncière',
    'communication_transparence' => 'Communication / Transparence',
];

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
            $stmt = db()->prepare("UPDATE sill_timeline SET is_active = ? WHERE id = ?");
            $stmt->execute([$is_active, $item_id]);
            flash('success', $is_active ? 'Événement activé.' : 'Événement désactivé.');
        }
        header('Location: ?page=timeline');
        exit;
    }

    // --- DELETE ---
    if ($action === 'delete') {
        $item_id = (int) ($_POST['id'] ?? 0);
        if ($item_id > 0) {
            $stmt = db()->prepare("DELETE FROM sill_timeline WHERE id = ?");
            $stmt->execute([$item_id]);
            flash('success', 'Événement supprimé.');
        }
        header('Location: ?page=timeline');
        exit;
    }

    // Common fields
    $title       = trim($_POST['title'] ?? '');
    $slug_raw    = trim($_POST['slug'] ?? '');
    $event_date  = trim($_POST['event_date'] ?? '');
    $category    = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $link_url    = trim($_POST['link_url'] ?? '');
    $sort_order  = (int) ($_POST['sort_order'] ?? 0);
    $is_active   = isset($_POST['is_active']) ? 1 : 0;

    $slug = $slug_raw !== '' ? $slug_raw : make_slug($title);
    $event_year = $event_date ? date('Y', strtotime($event_date)) : null;

    // --- CREATE ---
    if ($action === 'create') {
        $stmt = db()->prepare(
            "INSERT INTO sill_timeline (slug, title, event_date, event_year, category, description, link_url, sort_order, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$slug, $title, $event_date, $event_year, $category, $description, $link_url, $sort_order, $is_active]);
        flash('success', 'Événement créé.');
        header('Location: ?page=timeline');
        exit;
    }

    // --- EDIT ---
    if ($action === 'edit') {
        $item_id = (int) ($_POST['id'] ?? $id);
        $stmt = db()->prepare(
            "UPDATE sill_timeline
             SET slug = ?, title = ?, event_date = ?, event_year = ?, category = ?, description = ?, link_url = ?, sort_order = ?, is_active = ?
             WHERE id = ?"
        );
        $stmt->execute([$slug, $title, $event_date, $event_year, $category, $description, $link_url, $sort_order, $is_active, $item_id]);
        flash('success', 'Événement mis à jour.');
        header('Location: ?page=timeline');
        exit;
    }
}

// ---------------------------------------------------------------------------
// VIEW: Edit form
// ---------------------------------------------------------------------------
if ($action === 'edit' && $id) {
    $item = queryOne("SELECT * FROM sill_timeline WHERE id = ?", [(int) $id]);
    if (!$item) {
        flash('error', 'Événement introuvable.');
        header('Location: ?page=timeline');
        exit;
    }
    ?>
    <div class="page-header">
        <h1>Modifier l'événement</h1>
        <a href="?page=timeline" class="btn btn-secondary">Retour</a>
    </div>

    <form method="post" action="?page=timeline&action=edit" class="admin-form">
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

        <div class="form-row">
            <div class="form-group">
                <label for="event_date">Date <span class="required">*</span></label>
                <input type="date" id="event_date" name="event_date" value="<?= e($item['event_date'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="category">Catégorie</label>
                <select id="category" name="category">
                    <option value="">— Aucune —</option>
                    <?php foreach ($categories as $val => $label): ?>
                        <option value="<?= e($val) ?>" <?= ($item['category'] ?? '') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="sort_order">Ordre</label>
                <input type="number" id="sort_order" name="sort_order" value="<?= (int) ($item['sort_order'] ?? 0) ?>" min="0" style="width:80px">
            </div>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="8"><?= e($item['description'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label for="link_url">Lien (URL)</label>
            <input type="url" id="link_url" name="link_url" value="<?= e($item['link_url'] ?? '') ?>" placeholder="https://...">
        </div>

        <div class="form-group form-group--checkbox">
            <label>
                <input type="checkbox" name="is_active" value="1" <?= $item['is_active'] ? 'checked' : '' ?>>
                Événement actif
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="?page=timeline" class="btn btn-secondary">Retour</a>
        </div>
    </form>
    <script>
    CKEDITOR.replace('description', {
        language: 'fr',
        height: 300,
        removePlugins: 'elementspath',
        toolbar: [
            { name: 'basic',   items: ['Bold', 'Italic', 'Underline', '-', 'RemoveFormat'] },
            { name: 'para',    items: ['NumberedList', 'BulletedList', '-', 'Blockquote'] },
            { name: 'links',   items: ['Link', 'Unlink'] },
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
        <h1>Nouvel événement</h1>
        <a href="?page=timeline" class="btn btn-secondary">Retour</a>
    </div>

    <form method="post" action="?page=timeline&action=create" class="admin-form">
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

        <div class="form-row">
            <div class="form-group">
                <label for="event_date">Date <span class="required">*</span></label>
                <input type="date" id="event_date" name="event_date" value="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="form-group">
                <label for="category">Catégorie</label>
                <select id="category" name="category">
                    <option value="">— Aucune —</option>
                    <?php foreach ($categories as $val => $label): ?>
                        <option value="<?= e($val) ?>"><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="sort_order">Ordre</label>
                <input type="number" id="sort_order" name="sort_order" value="0" min="0" style="width:80px">
            </div>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="8"></textarea>
        </div>

        <div class="form-group">
            <label for="link_url">Lien (URL)</label>
            <input type="url" id="link_url" name="link_url" value="" placeholder="https://...">
        </div>

        <div class="form-group form-group--checkbox">
            <label>
                <input type="checkbox" name="is_active" value="1" checked>
                Événement actif
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="?page=timeline" class="btn btn-secondary">Retour</a>
        </div>
    </form>
    <script>
    CKEDITOR.replace('description', {
        language: 'fr',
        height: 300,
        removePlugins: 'elementspath',
        toolbar: [
            { name: 'basic',   items: ['Bold', 'Italic', 'Underline', '-', 'RemoveFormat'] },
            { name: 'para',    items: ['NumberedList', 'BulletedList', '-', 'Blockquote'] },
            { name: 'links',   items: ['Link', 'Unlink'] },
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
$all_items = query("SELECT * FROM sill_timeline ORDER BY event_date DESC, sort_order ASC");
?>

<div class="page-header">
    <h1>Timeline — Notre histoire</h1>
    <a href="?page=timeline&action=create" class="btn btn-primary">Nouvel événement</a>
</div>

<p class="admin-info">Ces événements apparaissent dans la section « Notre histoire » sur la page d'accueil.</p>

<?php if (empty($all_items)): ?>
    <p class="admin-empty">Aucun événement trouvé.</p>
<?php else: ?>
<div class="table-wrapper">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Titre</th>
                <th>Catégorie</th>
                <th>Active</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($all_items as $item): ?>
            <tr>
                <td style="white-space:nowrap"><?= e($item['event_date'] ?? '') ?></td>
                <td><strong><?= e($item['title']) ?></strong></td>
                <td><?= e($categories[$item['category']] ?? $item['category'] ?? '') ?></td>
                <td>
                    <form method="post" action="?page=timeline&action=toggle" class="form-inline">
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
                    <a href="?page=timeline&action=edit&id=<?= (int) $item['id'] ?>" class="btn btn-sm btn-secondary">Modifier</a>
                    <form method="post" action="?page=timeline&action=delete" class="form-inline"
                          onsubmit="return confirm('Supprimer définitivement cet événement ?')">
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
