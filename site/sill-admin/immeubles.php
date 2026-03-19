<?php
// immeubles.php — CRUD immeubles (sill_immeubles)
// Included from layout.php inside admin-main div.
// Has access to: db(), query(), queryOne(), e(), csrfField(), csrfCheck(), flash(), $action, $id

$categories = [
    'subventionne' => 'Subventionné',
    'controle'     => 'Contrôlé',
    'libre'        => 'Libre',
    'mixte'        => 'Mixte',
    'ppe'          => 'PPE',
    'etudiant'     => 'Étudiant',
];

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
            $stmt = db()->prepare("UPDATE sill_immeubles SET is_active = ? WHERE id = ?");
            $stmt->execute([$is_active, $item_id]);
            flash('success', $is_active ? 'Immeuble activé.' : 'Immeuble désactivé.');
        }
        header('Location: ?page=immeubles');
        exit;
    }

    // --- DELETE ---
    if ($action === 'delete') {
        if (!canDelete()) { flash('error', 'Suppression réservée aux administrateurs.'); header('Location: ?page=immeubles'); exit; }
        $item_id = (int) ($_POST['id'] ?? 0);
        if ($item_id > 0) {
            $row = queryOne("SELECT slug FROM sill_immeubles WHERE id = ?", [$item_id]);
            $stmt = db()->prepare("DELETE FROM sill_immeubles WHERE id = ?");
            $stmt->execute([$item_id]);
            $msg = 'Immeuble supprimé.';
            if ($row) {
                $mediaDir = immeubleMediaPath($row['slug']);
                if (is_dir($mediaDir)) {
                    $msg .= ' Le dossier media/immeubles/' . $row['slug'] . '/ existe encore et peut être supprimé manuellement.';
                }
            }
            flash('success', $msg);
        }
        header('Location: ?page=immeubles');
        exit;
    }

    // --- DELETE GALLERY IMAGE ---
    if ($action === 'delete-image') {
        if (!canDelete()) { flash('error', 'Suppression réservée aux administrateurs.'); header('Location: ?page=immeubles'); exit; }
        $item_id  = (int) ($_POST['id'] ?? 0);
        $filename = basename($_POST['filename'] ?? '');
        $row = queryOne("SELECT slug FROM sill_immeubles WHERE id = ?", [$item_id]);
        // Only allow deletion of gallery images (pattern: NN-name.ext), never cover
        if ($row && $filename && preg_match('/^\d{2}-[\w-]+\.(jpg|jpeg|png|webp)$/', $filename)) {
            $filepath = immeubleMediaPath($row['slug']) . '/' . $filename;
            if (file_exists($filepath)) {
                unlink($filepath);
                flash('success', 'Image supprimée.');
            }
        }
        header('Location: ?page=immeubles&action=edit&id=' . $item_id);
        exit;
    }

    // Common fields
    $nom             = trim($_POST['nom'] ?? '');
    $slug_raw        = trim($_POST['slug'] ?? '');
    $adresse         = trim($_POST['adresse'] ?? '');
    $quartier        = trim($_POST['quartier'] ?? '');
    $categorie       = trim($_POST['categorie'] ?? 'mixte');
    $nb_logements    = !empty($_POST['nb_logements']) ? (int) $_POST['nb_logements'] : null;
    $annee_livraison = !empty($_POST['annee_livraison']) ? (int) $_POST['annee_livraison'] : null;
    $label_energie   = trim($_POST['label_energie'] ?? '');
    $chapeau         = trim($_POST['chapeau'] ?? '');
    $description     = trim($_POST['description'] ?? '');
    $details         = cleanSwissTypography(trim($_POST['details'] ?? ''));
    $sort_order      = (int) ($_POST['sort_order'] ?? 0);
    $is_active       = isset($_POST['is_active']) ? 1 : 0;

    $slug = $slug_raw !== '' ? $slug_raw : make_slug($nom);

    // --- CREATE ---
    if ($action === 'create') {
        // Check slug uniqueness
        $existing = queryOne("SELECT id FROM sill_immeubles WHERE slug = ?", [$slug]);
        if ($existing) {
            flash('error', 'Le slug "' . e($slug) . '" existe déjà. Choisissez un autre nom ou slug.');
            header('Location: ?page=immeubles&action=create');
            exit;
        }
        $stmt = db()->prepare(
            "INSERT INTO sill_immeubles (slug, nom, adresse, quartier, categorie, nb_logements, annee_livraison, label_energie, chapeau, description, details, sort_order, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$slug, $nom, $adresse, $quartier, $categorie, $nb_logements, $annee_livraison, $label_energie, $chapeau, $description, $details, $sort_order, $is_active]);
        $newId = (int) db()->lastInsertId();

        // Create media directory
        $dir = immeubleMediaPath($slug);
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        // Handle cover upload
        if (!empty($_FILES['cover']['name'])) {
            $existing_covers = glob($dir . '/cover.{jpg,jpeg,png,webp}', GLOB_BRACE);
            foreach ($existing_covers as $old) unlink($old);
            uploadImmeubleImage($_FILES['cover'], $slug, 'cover');
        }

        // Handle gallery uploads (max 6 total)
        if (!empty($_FILES['galerie']['name'][0])) {
            $existingGallery = glob($dir . '/[0-9][0-9]-*.{jpg,jpeg,png,webp}', GLOB_BRACE);
            $nextNum = count($existingGallery) + 1;
            $maxGallery = 6;
            foreach ($_FILES['galerie']['name'] as $i => $name) {
                if ($nextNum > $maxGallery) {
                    flash('error', 'Galerie limitée à ' . $maxGallery . ' images. Certaines images n\'ont pas été ajoutées.');
                    break;
                }
                if ($_FILES['galerie']['error'][$i] !== UPLOAD_ERR_OK) continue;
                $file = [
                    'name'     => $_FILES['galerie']['name'][$i],
                    'type'     => $_FILES['galerie']['type'][$i],
                    'tmp_name' => $_FILES['galerie']['tmp_name'][$i],
                    'error'    => $_FILES['galerie']['error'][$i],
                    'size'     => $_FILES['galerie']['size'][$i],
                ];
                $prefix = str_pad($nextNum, 2, '0', STR_PAD_LEFT);
                $caption = pathinfo($name, PATHINFO_FILENAME);
                $caption = preg_replace('/[^a-z0-9]+/', '-', strtolower($caption));
                uploadImmeubleImage($file, $slug, $prefix . '-' . $caption);
                $nextNum++;
            }
        }

        flash('success', 'Immeuble créé avec succès.');
        header('Location: ?page=immeubles');
        exit;
    }

    // --- EDIT ---
    if ($action === 'edit') {
        $item_id = (int) ($_POST['id'] ?? $id);
        $existingRow = queryOne("SELECT slug FROM sill_immeubles WHERE id = ?", [$item_id]);
        $existingSlug = $existingRow['slug'] ?? $slug;

        $stmt = db()->prepare(
            "UPDATE sill_immeubles
             SET nom = ?, adresse = ?, quartier = ?, categorie = ?, nb_logements = ?, annee_livraison = ?,
                 label_energie = ?, chapeau = ?, description = ?, details = ?, sort_order = ?, is_active = ?
             WHERE id = ?"
        );
        $stmt->execute([$nom, $adresse, $quartier, $categorie, $nb_logements, $annee_livraison, $label_energie, $chapeau, $description, $details, $sort_order, $is_active, $item_id]);

        $dir = immeubleMediaPath($existingSlug);
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        // Handle cover upload
        if (!empty($_FILES['cover']['name'])) {
            $old = glob($dir . '/cover.{jpg,jpeg,png,webp}', GLOB_BRACE);
            foreach ($old as $f) unlink($f);
            uploadImmeubleImage($_FILES['cover'], $existingSlug, 'cover');
        }

        // Handle gallery uploads (max 6 total)
        if (!empty($_FILES['galerie']['name'][0])) {
            $existing_gallery = glob($dir . '/[0-9][0-9]-*.{jpg,jpeg,png,webp}', GLOB_BRACE);
            $nextNum = count($existing_gallery) + 1;
            $maxGallery = 6;
            foreach ($_FILES['galerie']['name'] as $i => $name) {
                if ($nextNum > $maxGallery) {
                    flash('error', 'Galerie limitée à ' . $maxGallery . ' images. Certaines images n\'ont pas été ajoutées.');
                    break;
                }
                if ($_FILES['galerie']['error'][$i] !== UPLOAD_ERR_OK) continue;
                $file = [
                    'name'     => $_FILES['galerie']['name'][$i],
                    'type'     => $_FILES['galerie']['type'][$i],
                    'tmp_name' => $_FILES['galerie']['tmp_name'][$i],
                    'error'    => $_FILES['galerie']['error'][$i],
                    'size'     => $_FILES['galerie']['size'][$i],
                ];
                $prefix = str_pad($nextNum, 2, '0', STR_PAD_LEFT);
                $caption = pathinfo($name, PATHINFO_FILENAME);
                $caption = preg_replace('/[^a-z0-9]+/', '-', strtolower($caption));
                uploadImmeubleImage($file, $existingSlug, $prefix . '-' . $caption);
                $nextNum++;
            }
        }

        flash('success', 'Immeuble mis à jour.');
        header('Location: ?page=immeubles');
        exit;
    }
}

// ---------------------------------------------------------------------------
// VIEW: Edit form
// ---------------------------------------------------------------------------
if ($action === 'edit' && $id) {
    $item = queryOne("SELECT * FROM sill_immeubles WHERE id = ?", [(int) $id]);
    if (!$item) {
        flash('error', 'Immeuble introuvable.');
        header('Location: ?page=immeubles');
        exit;
    }
    $coverUrl = immeubleCoverUrl($item['slug']);
    $galerie  = immeubleGalerie($item['slug']);
    ?>
    <div class="page-header">
        <h1>Modifier l'immeuble</h1>
        <a href="?page=immeubles" class="btn btn-secondary">Retour</a>
    </div>

    <form method="post" action="?page=immeubles&action=edit" enctype="multipart/form-data" class="admin-form">
        <?= csrfField() ?>
        <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">

        <h2 class="form-section-title">Identité</h2>

        <div class="form-row">
            <div class="form-group">
                <label for="nom">Nom <span class="required">*</span></label>
                <input type="text" id="nom" name="nom" value="<?= e($item['nom']) ?>" required>
            </div>
            <div class="form-group">
                <label for="slug">Slug</label>
                <input type="text" id="slug" name="slug" value="<?= e($item['slug']) ?>" readonly class="input-readonly">
                <small class="form-hint">Non modifiable (lié au dossier images)</small>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="adresse">Adresse</label>
                <input type="text" id="adresse" name="adresse" value="<?= e($item['adresse'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="quartier">Quartier</label>
                <input type="text" id="quartier" name="quartier" value="<?= e($item['quartier'] ?? '') ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="categorie">Catégorie</label>
                <select id="categorie" name="categorie">
                    <?php foreach ($categories as $val => $label): ?>
                        <option value="<?= e($val) ?>" <?= ($item['categorie'] ?? '') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="nb_logements">Nb logements</label>
                <input type="number" id="nb_logements" name="nb_logements" value="<?= (int) ($item['nb_logements'] ?? 0) ?>" min="0" style="width:100px">
            </div>
            <div class="form-group">
                <label for="annee_livraison">Année livraison</label>
                <input type="number" id="annee_livraison" name="annee_livraison" value="<?= e($item['annee_livraison'] ?? '') ?>" min="1900" max="2100" style="width:100px">
            </div>
            <div class="form-group">
                <label for="label_energie">Labellisation</label>
                <input type="text" id="label_energie" name="label_energie" value="<?= e($item['label_energie'] ?? '') ?>" placeholder="Minergie-P">
            </div>
            <div class="form-group">
                <label for="sort_order">Ordre</label>
                <input type="number" id="sort_order" name="sort_order" value="<?= (int) ($item['sort_order'] ?? 0) ?>" min="0" style="width:80px">
            </div>
        </div>

        <h2 class="form-section-title">Contenu</h2>

        <div class="form-group">
            <label for="chapeau">Chapeau</label>
            <textarea id="chapeau" name="chapeau" rows="3"><?= e($item['chapeau'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="5"><?= e($item['description'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label for="details">Détails (texte riche)</label>
            <textarea id="details" name="details" rows="10"><?= e($item['details'] ?? '') ?></textarea>
        </div>

        <h2 class="form-section-title">Images</h2>

        <div class="form-group">
            <label for="cover">Image principale (cover)</label>
            <?php if ($coverUrl && !str_contains($coverUrl, 'placeholder')): ?>
                <div class="image-preview">
                    <img src="<?= e($coverUrl) ?>" alt="Cover actuel" style="max-width:300px;max-height:200px;border-radius:4px;margin-bottom:8px;">
                </div>
            <?php endif; ?>
            <input type="file" id="cover" name="cover" accept="image/jpeg,image/png,image/webp">
            <small class="form-hint">JPG, PNG ou WebP. Max 5 Mo. Laisser vide pour conserver l'image actuelle.</small>
        </div>

        <div class="form-group">
            <label>Galerie (max 6 images)</label>
            <?php if ($galerie): ?>
                <div class="admin-galerie-grid">
                    <?php foreach ($galerie as $img): ?>
                        <div class="admin-galerie-item">
                            <img src="<?= e($img['url']) ?>" alt="<?= e($img['caption']) ?>">
                            <span class="admin-galerie-caption"><?= e($img['caption']) ?></span>
                            <?php if (canDelete()): ?>
                            <form method="post" action="?page=immeubles&action=delete-image" class="form-inline">
                                <?= csrfField() ?>
                                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                <input type="hidden" name="filename" value="<?= e($img['filename']) ?>">
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer cette image ?')">Supprimer</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if (count($galerie) < 6): ?>
                <input type="file" id="galerie" name="galerie[]" accept="image/jpeg,image/png,image/webp" multiple>
                <small class="form-hint">Ajouter des images à la galerie. <?= 6 - count($galerie) ?> place(s) restante(s).</small>
            <?php else: ?>
                <small class="form-hint">Galerie complète (6/6). Supprimez une image pour en ajouter.</small>
            <?php endif; ?>
        </div>

        <div class="form-group form-group--checkbox">
            <label>
                <input type="checkbox" name="is_active" value="1" <?= $item['is_active'] ? 'checked' : '' ?>>
                Immeuble actif
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="?page=immeubles" class="btn btn-secondary">Retour</a>
        </div>
    </form>
    <script>initTinyMCE('#details', 400);</script>
    <?php
    return;
}

// ---------------------------------------------------------------------------
// VIEW: Create form
// ---------------------------------------------------------------------------
if ($action === 'create') {
    ?>
    <div class="page-header">
        <h1>Nouvel immeuble</h1>
        <a href="?page=immeubles" class="btn btn-secondary">Retour</a>
    </div>

    <form method="post" action="?page=immeubles&action=create" enctype="multipart/form-data" class="admin-form">
        <?= csrfField() ?>

        <h2 class="form-section-title">Identité</h2>

        <div class="form-row">
            <div class="form-group">
                <label for="nom">Nom <span class="required">*</span></label>
                <input type="text" id="nom" name="nom" value="" required>
            </div>
            <div class="form-group">
                <label for="slug">Slug</label>
                <input type="text" id="slug" name="slug" value="">
                <small class="form-hint">Auto-généré si vide</small>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="adresse">Adresse</label>
                <input type="text" id="adresse" name="adresse" value="">
            </div>
            <div class="form-group">
                <label for="quartier">Quartier</label>
                <input type="text" id="quartier" name="quartier" value="">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="categorie">Catégorie</label>
                <select id="categorie" name="categorie">
                    <?php foreach ($categories as $val => $label): ?>
                        <option value="<?= e($val) ?>" <?= $val === 'mixte' ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="nb_logements">Nb logements</label>
                <input type="number" id="nb_logements" name="nb_logements" value="" min="0" style="width:100px">
            </div>
            <div class="form-group">
                <label for="annee_livraison">Année livraison</label>
                <input type="number" id="annee_livraison" name="annee_livraison" value="" min="1900" max="2100" style="width:100px">
            </div>
            <div class="form-group">
                <label for="label_energie">Labellisation</label>
                <input type="text" id="label_energie" name="label_energie" value="" placeholder="Minergie-P">
            </div>
            <div class="form-group">
                <label for="sort_order">Ordre</label>
                <input type="number" id="sort_order" name="sort_order" value="0" min="0" style="width:80px">
            </div>
        </div>

        <h2 class="form-section-title">Contenu</h2>

        <div class="form-group">
            <label for="chapeau">Chapeau</label>
            <textarea id="chapeau" name="chapeau" rows="3"></textarea>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="5"></textarea>
        </div>

        <div class="form-group">
            <label for="details">Détails (texte riche)</label>
            <textarea id="details" name="details" rows="10"></textarea>
        </div>

        <h2 class="form-section-title">Images</h2>

        <div class="form-group">
            <label for="cover">Image principale (cover)</label>
            <input type="file" id="cover" name="cover" accept="image/jpeg,image/png,image/webp">
            <small class="form-hint">JPG, PNG ou WebP. Max 5 Mo.</small>
        </div>

        <div class="form-group">
            <label for="galerie">Galerie (max 6 images)</label>
            <input type="file" id="galerie" name="galerie[]" accept="image/jpeg,image/png,image/webp" multiple>
            <small class="form-hint">Sélectionnez jusqu'à 6 images.</small>
        </div>

        <div class="form-group form-group--checkbox">
            <label>
                <input type="checkbox" name="is_active" value="1" checked>
                Immeuble actif
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="?page=immeubles" class="btn btn-secondary">Retour</a>
        </div>
    </form>
    <script>initTinyMCE('#details', 400);</script>
    <?php
    return;
}

// ---------------------------------------------------------------------------
// VIEW: List (default)
// ---------------------------------------------------------------------------
$all_items = query("SELECT * FROM sill_immeubles ORDER BY sort_order, nom");
?>

<div class="page-header">
    <h1>Immeubles</h1>
    <a href="?page=immeubles&action=create" class="btn btn-primary">Nouvel immeuble</a>
</div>

<?php if (empty($all_items)): ?>
    <p class="admin-empty">Aucun immeuble trouvé.</p>
<?php else: ?>
<div class="table-wrapper">
    <table class="admin-table">
        <thead>
            <tr>
                <th style="width:50px">Cover</th>
                <th>Nom</th>
                <th>Quartier</th>
                <th>Logements</th>
                <th>Livraison</th>
                <th>Actif</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($all_items as $item): ?>
            <tr>
                <td>
                    <img src="<?= e(immeubleCoverUrl($item['slug'])) ?>"
                         alt="" style="width:48px;height:36px;object-fit:cover;border-radius:3px;">
                </td>
                <td><strong><?= e($item['nom']) ?></strong></td>
                <td><?= e($item['quartier'] ?? '') ?></td>
                <td><?= (int) ($item['nb_logements'] ?? 0) ?></td>
                <td><?= e($item['annee_livraison'] ?? '') ?></td>
                <td>
                    <form method="post" action="?page=immeubles&action=toggle" class="form-inline">
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
                    <a href="?page=immeubles&action=edit&id=<?= (int) $item['id'] ?>" class="btn btn-sm btn-secondary">Modifier</a>
                    <?php if (canDelete()): ?>
                    <form method="post" action="?page=immeubles&action=delete" class="form-inline"
                          onsubmit="return confirm('Supprimer définitivement cet immeuble ?')">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Supprimer</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
