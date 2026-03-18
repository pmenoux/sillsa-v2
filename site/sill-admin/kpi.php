<?php
// kpi.php — CRUD KPIs (sill_kpi)
// Included from layout.php inside admin-main div.

$MAX_PUBLIC = 8;

$categories = [
    'patrimoine'     => 'Patrimoine',
    'finance'        => 'Finance',
    'social'         => 'Social',
    'environnement'  => 'Environnement',
    'gouvernance'    => 'Gouvernance',
    'marche'         => 'Marché',
    'energie'        => 'Énergie',
    'sill'           => 'SILL (loyers)',
];

// ---------------------------------------------------------------------------
// POST handling
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();

    // --- TOGGLE visibility ---
    if ($action === 'toggle') {
        $kpi_id    = (int) ($_POST['kpi_id'] ?? 0);
        $is_public = (int) ($_POST['is_public'] ?? 0);

        if ($kpi_id > 0 && $is_public === 1) {
            $count = (int) queryOne("SELECT COUNT(*) AS c FROM sill_kpi WHERE is_public = 1")['c'];
            if ($count >= $MAX_PUBLIC) {
                flash('error', "Maximum $MAX_PUBLIC KPIs visibles atteint. Désactivez-en un d'abord.");
                header('Location: ?page=kpi');
                exit;
            }
        }

        if ($kpi_id > 0) {
            $stmt = db()->prepare("UPDATE sill_kpi SET is_public = ? WHERE id = ?");
            $stmt->execute([$is_public, $kpi_id]);
        }
        header('Location: ?page=kpi');
        exit;
    }

    // --- DELETE ---
    if ($action === 'delete') {
        $kpi_id = (int) ($_POST['id'] ?? 0);
        if ($kpi_id > 0) {
            $stmt = db()->prepare("DELETE FROM sill_kpi WHERE id = ?");
            $stmt->execute([$kpi_id]);
            flash('success', 'KPI supprimé.');
        }
        header('Location: ?page=kpi');
        exit;
    }

    // Common fields
    $label      = trim($_POST['label'] ?? '');
    $kpi_key    = trim($_POST['kpi_key'] ?? '');
    $value_num  = $_POST['value_num'] !== '' ? (float) $_POST['value_num'] : null;
    $value_text = trim($_POST['value_text'] ?? '');
    $unit       = trim($_POST['unit'] ?? '');
    $category   = $_POST['category'] ?? 'patrimoine';
    $sort_order = (int) ($_POST['sort_order'] ?? 0);
    $is_public  = isset($_POST['is_public']) ? 1 : 0;

    if ($label === '') {
        flash('error', 'Le label est obligatoire.');
        header('Location: ?page=kpi&action=' . $action . ($id ? "&id=$id" : ''));
        exit;
    }

    // --- CREATE ---
    if ($action === 'create') {
        if ($is_public) {
            $count = (int) queryOne("SELECT COUNT(*) AS c FROM sill_kpi WHERE is_public = 1")['c'];
            if ($count >= $MAX_PUBLIC) {
                flash('error', "Maximum $MAX_PUBLIC KPIs visibles atteint.");
                header('Location: ?page=kpi&action=create');
                exit;
            }
        }
        $stmt = db()->prepare(
            "INSERT INTO sill_kpi (kpi_key, label, value_num, value_text, unit, category, sort_order, is_public)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$kpi_key, $label, $value_num, $value_text, $unit, $category, $sort_order, $is_public]);
        flash('success', 'KPI créé.');
        header('Location: ?page=kpi');
        exit;
    }

    // --- EDIT ---
    if ($action === 'edit') {
        $kpi_id = (int) ($_POST['id'] ?? $id);
        if ($is_public) {
            $count = (int) queryOne("SELECT COUNT(*) AS c FROM sill_kpi WHERE is_public = 1 AND id != ?", [$kpi_id])['c'];
            if ($count >= $MAX_PUBLIC) {
                flash('error', "Maximum $MAX_PUBLIC KPIs visibles atteint.");
                header('Location: ?page=kpi&action=edit&id=' . $kpi_id);
                exit;
            }
        }
        $stmt = db()->prepare(
            "UPDATE sill_kpi SET kpi_key = ?, label = ?, value_num = ?, value_text = ?, unit = ?, category = ?, sort_order = ?, is_public = ?
             WHERE id = ?"
        );
        $stmt->execute([$kpi_key, $label, $value_num, $value_text, $unit, $category, $sort_order, $is_public, $kpi_id]);
        flash('success', 'KPI mis à jour.');
        header('Location: ?page=kpi');
        exit;
    }
}

// ---------------------------------------------------------------------------
// VIEW: Edit form
// ---------------------------------------------------------------------------
if ($action === 'edit' && $id) {
    $item = queryOne("SELECT * FROM sill_kpi WHERE id = ?", [(int) $id]);
    if (!$item) {
        flash('error', 'KPI introuvable.');
        header('Location: ?page=kpi');
        exit;
    }
    ?>
    <div class="page-header">
        <h1>Modifier le KPI</h1>
        <a href="?page=kpi" class="btn btn-secondary">Retour</a>
    </div>

    <form method="post" action="?page=kpi&action=edit" class="admin-form">
        <?= csrfField() ?>
        <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">

        <div class="form-row">
            <div class="form-group" style="flex:2">
                <label for="label">Label <span class="required">*</span></label>
                <input type="text" id="label" name="label" value="<?= e($item['label']) ?>" required>
            </div>
            <div class="form-group">
                <label for="kpi_key">Clé technique</label>
                <input type="text" id="kpi_key" name="kpi_key" value="<?= e($item['kpi_key'] ?? '') ?>">
                <small class="form-hint">Identifiant interne (optionnel)</small>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="value_num">Valeur numérique</label>
                <input type="number" id="value_num" name="value_num" value="<?= $item['value_num'] !== null ? e($item['value_num']) : '' ?>" step="0.01">
            </div>
            <div class="form-group">
                <label for="value_text">Valeur texte</label>
                <input type="text" id="value_text" name="value_text" value="<?= e($item['value_text'] ?? '') ?>">
                <small class="form-hint">Si rempli, remplace la valeur numérique</small>
            </div>
            <div class="form-group">
                <label for="unit">Unité</label>
                <input type="text" id="unit" name="unit" value="<?= e($item['unit'] ?? '') ?>">
                <small class="form-hint">Ex: M CHF, %, m², kWh/m²</small>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="category">Catégorie</label>
                <select id="category" name="category">
                    <?php foreach ($categories as $val => $label): ?>
                        <option value="<?= e($val) ?>" <?= ($item['category'] ?? '') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="sort_order">Ordre</label>
                <input type="number" id="sort_order" name="sort_order" value="<?= (int) ($item['sort_order'] ?? 0) ?>" min="0">
            </div>
        </div>

        <div class="form-group form-group--checkbox">
            <label>
                <input type="checkbox" name="is_public" value="1" <?= $item['is_public'] ? 'checked' : '' ?>>
                Visible sur la homepage (max <?= $MAX_PUBLIC ?>)
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="?page=kpi" class="btn btn-secondary">Retour</a>
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
        <h1>Nouveau KPI</h1>
        <a href="?page=kpi" class="btn btn-secondary">Retour</a>
    </div>

    <form method="post" action="?page=kpi&action=create" class="admin-form">
        <?= csrfField() ?>

        <div class="form-row">
            <div class="form-group" style="flex:2">
                <label for="label">Label <span class="required">*</span></label>
                <input type="text" id="label" name="label" value="" required>
            </div>
            <div class="form-group">
                <label for="kpi_key">Clé technique</label>
                <input type="text" id="kpi_key" name="kpi_key" value="">
                <small class="form-hint">Identifiant interne (optionnel)</small>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="value_num">Valeur numérique</label>
                <input type="number" id="value_num" name="value_num" value="" step="0.01">
            </div>
            <div class="form-group">
                <label for="value_text">Valeur texte</label>
                <input type="text" id="value_text" name="value_text" value="">
                <small class="form-hint">Si rempli, remplace la valeur numérique</small>
            </div>
            <div class="form-group">
                <label for="unit">Unité</label>
                <input type="text" id="unit" name="unit" value="">
                <small class="form-hint">Ex: M CHF, %, m², kWh/m²</small>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="category">Catégorie</label>
                <select id="category" name="category">
                    <?php foreach ($categories as $val => $label): ?>
                        <option value="<?= e($val) ?>"><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="sort_order">Ordre</label>
                <input type="number" id="sort_order" name="sort_order" value="0" min="0">
            </div>
        </div>

        <div class="form-group form-group--checkbox">
            <label>
                <input type="checkbox" name="is_public" value="1">
                Visible sur la homepage (max <?= $MAX_PUBLIC ?>)
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Créer</button>
            <a href="?page=kpi" class="btn btn-secondary">Retour</a>
        </div>
    </form>
    <?php
    return;
}

// ---------------------------------------------------------------------------
// VIEW: List (default) — grouped by display zone then category
// ---------------------------------------------------------------------------
$kpis = query("SELECT * FROM sill_kpi ORDER BY sort_order, id");
$public_count = 0;
foreach ($kpis as $k) { if ($k['is_public']) $public_count++; }

// Map categories to display zones
$zones = [
    'Accueil — Bandeau chiffres clés' => ['patrimoine'],
    'Accueil — Teaser contexte'       => ['marche'],
    'Page Contexte — Indicateurs'     => ['energie', 'sill'],
    'Autres'                          => [],
];

// Assign zone labels to categories
$categoryToZone = [];
foreach ($zones as $zoneName => $cats) {
    foreach ($cats as $cat) {
        $categoryToZone[$cat] = $zoneName;
    }
}

// Group KPIs by zone then category
$grouped = [];
foreach ($kpis as $kpi) {
    $cat = $kpi['category'] ?? '';
    $zone = $categoryToZone[$cat] ?? 'Autres';
    $grouped[$zone][$cat][] = $kpi;
}

// Ensure zones appear in defined order
$orderedZones = [];
foreach ($zones as $zoneName => $cats) {
    if (isset($grouped[$zoneName])) {
        $orderedZones[$zoneName] = $grouped[$zoneName];
    }
}
?>

<div class="page-header">
    <h1>KPIs</h1>
    <a href="?page=kpi&action=create" class="btn btn-primary">Nouveau KPI</a>
</div>

<p class="admin-info">
    <?= $public_count ?> / <?= $MAX_PUBLIC ?> KPIs visibles sur la homepage.
    Vous pouvez en créer autant que nécessaire, mais seuls <?= $MAX_PUBLIC ?> maximum seront affichés.
</p>

<?php if (empty($kpis)): ?>
    <p class="admin-empty">Aucun KPI trouvé.</p>
<?php else: ?>

<?php foreach ($orderedZones as $zoneName => $catGroups): ?>
    <h2 class="form-section-title" style="margin-top:32px;"><?= e($zoneName) ?></h2>

    <?php foreach ($catGroups as $cat => $items): ?>
        <p style="margin:12px 0 8px; font-size:12px; text-transform:uppercase; letter-spacing:0.08em; color:#999;">
            <?= e($categories[$cat] ?? ucfirst($cat)) ?>
        </p>

        <div class="table-wrapper" style="margin-bottom:24px;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Label</th>
                        <th>Valeur</th>
                        <th>Unité</th>
                        <th>Ordre</th>
                        <th>Visible</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $kpi): ?>
                    <tr<?= $kpi['is_public'] ? '' : ' style="opacity:0.5"' ?>>
                        <td><strong><?= e($kpi['label']) ?></strong></td>
                        <td class="cell-readonly"><?= e($kpi['value_text'] ?: number_format((float)$kpi['value_num'], 2, '.', "'")) ?></td>
                        <td><?= e($kpi['unit'] ?? '') ?></td>
                        <td style="text-align:center"><?= (int) $kpi['sort_order'] ?></td>
                        <td>
                            <form method="post" action="?page=kpi&action=toggle" class="form-inline">
                                <?= csrfField() ?>
                                <input type="hidden" name="kpi_id" value="<?= (int) $kpi['id'] ?>">
                                <input type="hidden" name="is_public" value="<?= $kpi['is_public'] ? 0 : 1 ?>">
                                <label class="toggle">
                                    <input type="checkbox"
                                           <?= $kpi['is_public'] ? 'checked' : '' ?>
                                           onchange="this.form.submit()">
                                    <span class="toggle-slider"></span>
                                </label>
                            </form>
                        </td>
                        <td class="cell-actions" style="white-space:nowrap">
                            <a href="?page=kpi&action=edit&id=<?= (int) $kpi['id'] ?>" class="btn btn-sm btn-secondary">Modifier</a>
                            <form method="post" action="?page=kpi&action=delete" class="form-inline" style="display:inline"
                                  onsubmit="return confirm('Supprimer ce KPI ?')">
                                <?= csrfField() ?>
                                <input type="hidden" name="id" value="<?= (int) $kpi['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>
<?php endforeach; ?>

<?php endif; ?>
