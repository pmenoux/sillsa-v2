<?php
// publications.php — CRUD sill_publications with PDF + cover image upload
// Included from layout.php inside admin-main div.

// ---------------------------------------------------------------------------
// Publication types
// ---------------------------------------------------------------------------
$pub_types = [
    'rapport_annuel' => 'Rapport annuel',
    'esg'            => 'ESG',
    'communique'     => 'Communiqué',
    'autre'          => 'Autre',
];

// ---------------------------------------------------------------------------
// Helper: upload a file (PDF or image)
// ---------------------------------------------------------------------------
function uploadFile(array $file, string $subdir, string $prefix, array $allowed_mimes, int $max_mb = 10): string|false
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        flash('error', 'Erreur téléversement (code ' . $file['error'] . ').');
        return false;
    }
    if ($file['size'] > $max_mb * 1024 * 1024) {
        flash('error', 'Fichier trop volumineux (max ' . $max_mb . ' Mo).');
        return false;
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!in_array($mime, $allowed_mimes, true)) {
        flash('error', 'Type de fichier non autorisé (' . e($mime) . ').');
        return false;
    }
    $ext = match ($mime) {
        'application/pdf' => '.pdf',
        'image/jpeg'      => '.jpg',
        'image/png'       => '.png',
        'image/webp'      => '.webp',
        default           => '',
    };
    $filename = $prefix . $ext;
    $upload_dir = UPLOADS_DIR . '/' . $subdir . '/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    $dest = $upload_dir . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        flash('error', 'Impossible de déplacer le fichier.');
        return false;
    }
    return $subdir . '/' . $filename;
}

function makePrefix(int $annee, string $title): string
{
    $slug = strtolower(trim($title));
    $slug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $slug);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $annee . '-' . $slug;
}

// ---------------------------------------------------------------------------
// POST handling
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();

    // --- TOGGLE ---
    if ($action === 'toggle') {
        $item_id   = (int) ($_POST['id'] ?? 0);
        $is_active = (int) ($_POST['is_active'] ?? 0);
        if ($item_id > 0) {
            $stmt = db()->prepare("UPDATE sill_publications SET is_active = ? WHERE id = ?");
            $stmt->execute([$is_active, $item_id]);
        }
        header('Location: ?page=publications');
        exit;
    }

    // --- DELETE ---
    if ($action === 'delete') {
        $item_id = (int) ($_POST['id'] ?? 0);
        if ($item_id > 0) {
            $stmt = db()->prepare("DELETE FROM sill_publications WHERE id = ?");
            $stmt->execute([$item_id]);
            flash('success', 'Publication supprimée.');
        }
        header('Location: ?page=publications');
        exit;
    }

    // Common fields
    $title      = trim($_POST['title'] ?? '');
    $annee      = (int) ($_POST['annee'] ?? date('Y'));
    $type       = $_POST['type'] ?? 'rapport_annuel';
    $description = trim($_POST['description'] ?? '');
    $is_active  = isset($_POST['is_active']) ? 1 : 0;
    $prefix     = makePrefix($annee, $title);

    // --- CREATE ---
    if ($action === 'create') {
        if ($title === '') {
            flash('error', 'Le titre est obligatoire.');
            header('Location: ?page=publications&action=create');
            exit;
        }

        // PDF upload (required)
        if (empty($_FILES['pdf_file']['name'])) {
            flash('error', 'Le fichier PDF est obligatoire.');
            header('Location: ?page=publications&action=create');
            exit;
        }
        $pdf_path = uploadFile($_FILES['pdf_file'], 'publications', $prefix, ['application/pdf'], 15);
        if ($pdf_path === false) {
            header('Location: ?page=publications&action=create');
            exit;
        }

        // Cover image (optional)
        $cover_path = '';
        if (!empty($_FILES['cover_file']['name'])) {
            $cover_path = uploadFile($_FILES['cover_file'], 'covers', $prefix, ['image/jpeg', 'image/png', 'image/webp'], 5);
            if ($cover_path === false) $cover_path = '';
        }

        $stmt = db()->prepare(
            "INSERT INTO sill_publications (title, annee, type, pdf_path, cover_path, description, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$title, $annee, $type, $pdf_path, $cover_path, $description, $is_active]);
        flash('success', 'Publication créée.');
        header('Location: ?page=publications');
        exit;
    }

    // --- EDIT ---
    if ($action === 'edit') {
        $item_id = (int) ($_POST['id'] ?? $id);

        $pdf_path = $_POST['existing_pdf'] ?? '';
        if (!empty($_FILES['pdf_file']['name'])) {
            $new_pdf = uploadFile($_FILES['pdf_file'], 'publications', $prefix, ['application/pdf'], 15);
            if ($new_pdf !== false) $pdf_path = $new_pdf;
        }

        $cover_path = $_POST['existing_cover'] ?? '';
        if (!empty($_FILES['cover_file']['name'])) {
            $new_cover = uploadFile($_FILES['cover_file'], 'covers', $prefix, ['image/jpeg', 'image/png', 'image/webp'], 5);
            if ($new_cover !== false) $cover_path = $new_cover;
        }

        $stmt = db()->prepare(
            "UPDATE sill_publications
             SET title = ?, annee = ?, type = ?, pdf_path = ?, cover_path = ?, description = ?, is_active = ?
             WHERE id = ?"
        );
        $stmt->execute([$title, $annee, $type, $pdf_path, $cover_path, $description, $is_active, $item_id]);
        flash('success', 'Publication mise à jour.');
        header('Location: ?page=publications');
        exit;
    }
}

// ---------------------------------------------------------------------------
// VIEW: Edit form
// ---------------------------------------------------------------------------
if ($action === 'edit' && $id) {
    $item = queryOne("SELECT * FROM sill_publications WHERE id = ?", [(int) $id]);
    if (!$item) {
        flash('error', 'Publication introuvable.');
        header('Location: ?page=publications');
        exit;
    }
    ?>
    <div class="page-header">
        <h1>Modifier la publication</h1>
        <a href="?page=publications" class="btn btn-secondary">Retour</a>
    </div>

    <form method="post" action="?page=publications&action=edit" enctype="multipart/form-data" class="admin-form">
        <?= csrfField() ?>
        <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
        <input type="hidden" name="existing_pdf" value="<?= e($item['pdf_path'] ?? '') ?>">
        <input type="hidden" name="existing_cover" value="<?= e($item['cover_path'] ?? '') ?>">

        <div class="form-group">
            <label for="title">Titre <span class="required">*</span></label>
            <input type="text" id="title" name="title" value="<?= e($item['title']) ?>" required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="annee">Année <span class="required">*</span></label>
                <input type="number" id="annee" name="annee" value="<?= (int) $item['annee'] ?>" min="2000" max="2099" required>
            </div>
            <div class="form-group">
                <label for="type">Type</label>
                <select id="type" name="type">
                    <?php foreach ($pub_types as $val => $label): ?>
                        <option value="<?= e($val) ?>" <?= ($item['type'] ?? '') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="3" style="min-height:80px"><?= e($item['description'] ?? '') ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="pdf_file">Fichier PDF</label>
                <?php if (!empty($item['pdf_path'])): ?>
                    <p class="form-hint">Actuel : <a href="<?= SITE_URL ?>/uploads/<?= e($item['pdf_path']) ?>" target="_blank"><?= e(basename($item['pdf_path'])) ?></a></p>
                <?php endif; ?>
                <input type="file" id="pdf_file" name="pdf_file" accept="application/pdf">
                <small class="form-hint">Laisser vide pour conserver le fichier actuel. Max 15 Mo.</small>
            </div>
            <div class="form-group">
                <label for="cover_file">Image de couverture</label>
                <?php if (!empty($item['cover_path'])): ?>
                    <div style="margin-bottom:8px">
                        <img src="<?= SITE_URL ?>/uploads/<?= e($item['cover_path']) ?>" alt="Couverture" style="max-height:120px; border:1px solid #ddd; border-radius:4px">
                    </div>
                <?php endif; ?>
                <input type="file" id="cover_file" name="cover_file" accept="image/jpeg,image/png,image/webp">
                <small class="form-hint">JPG, PNG ou WebP. Max 5 Mo.</small>
            </div>
        </div>

        <div class="form-group form-group--checkbox">
            <label>
                <input type="checkbox" name="is_active" value="1" <?= $item['is_active'] ? 'checked' : '' ?>>
                Publication active
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="?page=publications" class="btn btn-secondary">Retour</a>
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
        <h1>Nouvelle publication</h1>
        <a href="?page=publications" class="btn btn-secondary">Retour</a>
    </div>

    <form method="post" action="?page=publications&action=create" enctype="multipart/form-data" class="admin-form">
        <?= csrfField() ?>

        <div class="form-group">
            <label for="title">Titre <span class="required">*</span></label>
            <input type="text" id="title" name="title" value="" required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="annee">Année <span class="required">*</span></label>
                <input type="number" id="annee" name="annee" value="<?= date('Y') ?>" min="2000" max="2099" required>
            </div>
            <div class="form-group">
                <label for="type">Type</label>
                <select id="type" name="type">
                    <?php foreach ($pub_types as $val => $label): ?>
                        <option value="<?= e($val) ?>"><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="3" style="min-height:80px"></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="pdf_file">Fichier PDF <span class="required">*</span></label>
                <input type="file" id="pdf_file" name="pdf_file" accept="application/pdf" required>
                <small class="form-hint">Max 15 Mo.</small>
            </div>
            <div class="form-group">
                <label for="cover_file">Image de couverture</label>
                <input type="file" id="cover_file" name="cover_file" accept="image/jpeg,image/png,image/webp">
                <small class="form-hint">JPG, PNG ou WebP. Première page du rapport. Max 5 Mo.</small>
            </div>
        </div>

        <div class="form-group form-group--checkbox">
            <label>
                <input type="checkbox" name="is_active" value="1" checked>
                Publication active
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Créer</button>
            <a href="?page=publications" class="btn btn-secondary">Retour</a>
        </div>
    </form>
    <?php
    return;
}

// ---------------------------------------------------------------------------
// VIEW: List (default)
// ---------------------------------------------------------------------------
$all_items = query("SELECT * FROM sill_publications ORDER BY annee DESC, title");
?>

<div class="page-header">
    <h1>Publications</h1>
    <a href="?page=publications&action=create" class="btn btn-primary">Nouvelle publication</a>
</div>

<?php if (empty($all_items)): ?>
    <p class="admin-empty">Aucune publication trouvée.</p>
<?php else: ?>
<div class="table-wrapper">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Couverture</th>
                <th>Titre</th>
                <th>Année</th>
                <th>Type</th>
                <th>PDF</th>
                <th>Active</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($all_items as $item): ?>
            <tr>
                <td>
                    <?php if (!empty($item['cover_path'])): ?>
                        <img src="<?= SITE_URL ?>/uploads/<?= e($item['cover_path']) ?>" alt="" style="height:48px; border-radius:3px; box-shadow:0 1px 3px rgba(0,0,0,0.15)">
                    <?php else: ?>
                        <span style="color:#ccc; font-size:11px">—</span>
                    <?php endif; ?>
                </td>
                <td><strong><?= e($item['title']) ?></strong></td>
                <td><?= (int) $item['annee'] ?></td>
                <td><?= e($pub_types[$item['type']] ?? $item['type']) ?></td>
                <td>
                    <?php if (!empty($item['pdf_path'])): ?>
                        <a href="<?= SITE_URL ?>/uploads/<?= e($item['pdf_path']) ?>" target="_blank" style="font-size:12px">Voir PDF</a>
                    <?php else: ?>
                        <span style="color:#ccc">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <form method="post" action="?page=publications&action=toggle" class="form-inline">
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
                    <a href="?page=publications&action=edit&id=<?= (int) $item['id'] ?>" class="btn btn-sm btn-secondary">Modifier</a>
                    <form method="post" action="?page=publications&action=delete" class="form-inline"
                          onsubmit="return confirm('Supprimer définitivement cette publication ?')">
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
