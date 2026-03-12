<?php
// publications.php — CRUD sill_publications with PDF upload
// Included from layout.php; has access to db(), query(), queryOne(), e(), csrfField(), csrfCheck(), flash(), $action, $id

// ── POST HANDLERS ─────────────────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();

    $post_action = $_POST['action'] ?? '';

    if ($post_action === 'create') {
        $title  = trim($_POST['title'] ?? '');
        $annee  = (int)($_POST['annee'] ?? date('Y'));
        $type   = $_POST['type'] ?? 'autre';
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if ($title === '') {
            flash('error', 'Le titre est obligatoire.');
            header('Location: ' . SITE_URL . 'sill-admin/?section=publications&action=create');
            exit;
        }

        // PDF upload — required on create
        if (empty($_FILES['pdf_file']['name'])) {
            flash('error', 'Le fichier PDF est obligatoire pour une nouvelle publication.');
            header('Location: ' . SITE_URL . 'sill-admin/?section=publications&action=create');
            exit;
        }

        $pdf_path = handlePdfUpload($_FILES['pdf_file'], $annee, $title);
        if ($pdf_path === false) {
            header('Location: ' . SITE_URL . 'sill-admin/?section=publications&action=create');
            exit;
        }

        query(
            'INSERT INTO sill_publications (title, annee, type, pdf_path, is_active) VALUES (?, ?, ?, ?, ?)',
            [$title, $annee, $type, $pdf_path, $is_active]
        );
        flash('success', 'Publication créée avec succès.');
        header('Location: ' . SITE_URL . 'sill-admin/?section=publications');
        exit;
    }

    if ($post_action === 'edit') {
        $edit_id  = (int)($_POST['id'] ?? 0);
        $title    = trim($_POST['title'] ?? '');
        $annee    = (int)($_POST['annee'] ?? date('Y'));
        $type     = $_POST['type'] ?? 'autre';
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $existing_pdf = $_POST['existing_pdf'] ?? '';

        if ($title === '' || $edit_id === 0) {
            flash('error', 'Données invalides.');
            header('Location: ' . SITE_URL . 'sill-admin/?section=publications&action=edit&id=' . $edit_id);
            exit;
        }

        $pdf_path = $existing_pdf;

        // New file uploaded?
        if (!empty($_FILES['pdf_file']['name'])) {
            $new_path = handlePdfUpload($_FILES['pdf_file'], $annee, $title);
            if ($new_path === false) {
                header('Location: ' . SITE_URL . 'sill-admin/?section=publications&action=edit&id=' . $edit_id);
                exit;
            }
            $pdf_path = $new_path;
        }

        query(
            'UPDATE sill_publications SET title=?, annee=?, type=?, pdf_path=?, is_active=? WHERE id=?',
            [$title, $annee, $type, $pdf_path, $is_active, $edit_id]
        );
        flash('success', 'Publication mise à jour.');
        header('Location: ' . SITE_URL . 'sill-admin/?section=publications');
        exit;
    }

    if ($post_action === 'delete') {
        $del_id = (int)($_POST['id'] ?? 0);
        if ($del_id > 0) {
            query('UPDATE sill_publications SET is_active=0 WHERE id=?', [$del_id]);
            flash('success', 'Publication désactivée.');
        }
        header('Location: ' . SITE_URL . 'sill-admin/?section=publications');
        exit;
    }
}

// ── HELPER: PDF UPLOAD ────────────────────────────────────────────────────────

function handlePdfUpload(array $file, int $annee, string $title): string|false
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        flash('error', 'Erreur lors du téléversement (code ' . $file['error'] . ').');
        return false;
    }

    // Size check: max 10 MB
    if ($file['size'] > 10 * 1024 * 1024) {
        flash('error', 'Le fichier dépasse la taille maximale autorisée (10 Mo).');
        return false;
    }

    // MIME check via finfo — only application/pdf
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if ($mime !== 'application/pdf') {
        flash('error', 'Seuls les fichiers PDF sont acceptés (type détecté : ' . e($mime) . ').');
        return false;
    }

    // Slugify title
    $slug = slugifyTitle($title);
    $filename = $annee . '-' . $slug . '.pdf';

    $upload_dir = rtrim(UPLOADS_DIR, '/\\') . '/publications/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $dest = $upload_dir . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        flash('error', 'Impossible de déplacer le fichier téléversé.');
        return false;
    }

    return 'publications/' . $filename;
}

function slugifyTitle(string $title): string
{
    $title = mb_strtolower($title, 'UTF-8');
    // Replace accented chars
    $map = [
        'à'=>'a','â'=>'a','ä'=>'a','á'=>'a','ã'=>'a',
        'è'=>'e','ê'=>'e','ë'=>'e','é'=>'e',
        'î'=>'i','ï'=>'i','í'=>'i','ì'=>'i',
        'ô'=>'o','ö'=>'o','ó'=>'o','ò'=>'o','õ'=>'o',
        'û'=>'u','ü'=>'u','ú'=>'u','ù'=>'u',
        'ç'=>'c','ñ'=>'n',
    ];
    $title = strtr($title, $map);
    $title = preg_replace('/[^a-z0-9]+/', '-', $title);
    return trim($title, '-');
}

// ── VIEWS ─────────────────────────────────────────────────────────────────────

$pub_types = [
    'rapport_annuel' => 'Rapport annuel',
    'esg'            => 'ESG',
    'autre'          => 'Autre',
];

// ── EDIT FORM ─────────────────────────────────────────────────────────────────
if ($action === 'edit' && $id > 0) {
    $item = queryOne('SELECT * FROM sill_publications WHERE id=?', [$id]);
    if (!$item) {
        flash('error', 'Publication introuvable.');
        header('Location: ' . SITE_URL . 'sill-admin/?section=publications');
        exit;
    }
    ?>
    <div class="admin-section-header">
        <h2>Modifier la publication</h2>
        <a href="<?= SITE_URL ?>sill-admin/?section=publications" class="btn btn-secondary">← Retour</a>
    </div>

    <form method="post" action="<?= SITE_URL ?>sill-admin/?section=publications" enctype="multipart/form-data" class="admin-form">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
        <input type="hidden" name="existing_pdf" value="<?= e($item['pdf_path']) ?>">

        <div class="form-group">
            <label for="title">Titre <span class="required">*</span></label>
            <input type="text" id="title" name="title" value="<?= e($item['title']) ?>" required maxlength="255">
        </div>

        <div class="form-group">
            <label for="annee">Année <span class="required">*</span></label>
            <input type="number" id="annee" name="annee" value="<?= (int)$item['annee'] ?>" min="2000" max="2099" required>
        </div>

        <div class="form-group">
            <label for="type">Type <span class="required">*</span></label>
            <select id="type" name="type" required>
                <?php foreach ($pub_types as $val => $label): ?>
                    <option value="<?= e($val) ?>"<?= $item['type'] === $val ? ' selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="pdf_file">Fichier PDF</label>
            <?php if (!empty($item['pdf_path'])): ?>
                <p class="form-hint">
                    Fichier actuel : <a href="<?= SITE_URL ?>uploads/<?= e($item['pdf_path']) ?>" target="_blank"><?= e(basename($item['pdf_path'])) ?></a><br>
                    Laisser vide pour conserver ce fichier.
                </p>
            <?php endif; ?>
            <input type="file" id="pdf_file" name="pdf_file" accept="application/pdf">
            <span class="form-hint">PDF uniquement, 10 Mo max.</span>
        </div>

        <div class="form-group form-group--checkbox">
            <label>
                <input type="checkbox" name="is_active" value="1"<?= $item['is_active'] ? ' checked' : '' ?>>
                Actif (visible sur le site)
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="<?= SITE_URL ?>sill-admin/?section=publications" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
    <?php
    return;
}

// ── CREATE FORM ───────────────────────────────────────────────────────────────
if ($action === 'create') {
    ?>
    <div class="admin-section-header">
        <h2>Nouvelle publication</h2>
        <a href="<?= SITE_URL ?>sill-admin/?section=publications" class="btn btn-secondary">← Retour</a>
    </div>

    <form method="post" action="<?= SITE_URL ?>sill-admin/?section=publications" enctype="multipart/form-data" class="admin-form">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="create">

        <div class="form-group">
            <label for="title">Titre <span class="required">*</span></label>
            <input type="text" id="title" name="title" value="" required maxlength="255">
        </div>

        <div class="form-group">
            <label for="annee">Année <span class="required">*</span></label>
            <input type="number" id="annee" name="annee" value="<?= date('Y') ?>" min="2000" max="2099" required>
        </div>

        <div class="form-group">
            <label for="type">Type <span class="required">*</span></label>
            <select id="type" name="type" required>
                <?php foreach ($pub_types as $val => $label): ?>
                    <option value="<?= e($val) ?>"><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="pdf_file">Fichier PDF <span class="required">*</span></label>
            <input type="file" id="pdf_file" name="pdf_file" accept="application/pdf" required>
            <span class="form-hint">PDF uniquement, 10 Mo max.</span>
        </div>

        <div class="form-group form-group--checkbox">
            <label>
                <input type="checkbox" name="is_active" value="1" checked>
                Actif (visible sur le site)
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Créer la publication</button>
            <a href="<?= SITE_URL ?>sill-admin/?section=publications" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
    <?php
    return;
}

// ── LIST ──────────────────────────────────────────────────────────────────────

$publications = query('SELECT * FROM sill_publications ORDER BY annee DESC, title');
?>
<div class="admin-section-header">
    <h2>Publications</h2>
    <a href="<?= SITE_URL ?>sill-admin/?section=publications&action=create" class="btn btn-primary">+ Nouvelle publication</a>
</div>

<?php if (empty($publications)): ?>
    <p class="empty-state">Aucune publication enregistrée.</p>
<?php else: ?>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Titre</th>
                <th>Année</th>
                <th>Type</th>
                <th>PDF</th>
                <th>Actif</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($publications as $pub): ?>
                <tr class="<?= $pub['is_active'] ? '' : 'row-inactive' ?>">
                    <td><?= e($pub['title']) ?></td>
                    <td><?= (int)$pub['annee'] ?></td>
                    <td><?= e($pub_types[$pub['type']] ?? $pub['type']) ?></td>
                    <td>
                        <?php if (!empty($pub['pdf_path'])): ?>
                            <a href="<?= SITE_URL ?>uploads/<?= e($pub['pdf_path']) ?>" target="_blank" class="file-link">
                                <?= e(basename($pub['pdf_path'])) ?>
                            </a>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= $pub['is_active']
                            ? '<span class="badge badge-active">Oui</span>'
                            : '<span class="badge badge-inactive">Non</span>' ?>
                    </td>
                    <td class="actions-cell">
                        <a href="<?= SITE_URL ?>sill-admin/?section=publications&action=edit&id=<?= (int)$pub['id'] ?>" class="btn btn-sm btn-secondary">Modifier</a>
                        <?php if ($pub['is_active']): ?>
                            <form method="post" action="<?= SITE_URL ?>sill-admin/?section=publications" class="inline-form" onsubmit="return confirm('Désactiver cette publication ?')">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$pub['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Désactiver</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
