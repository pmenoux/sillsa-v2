<?php
// repartition.php — Répartition locative par affectation
// Included from layout.php inside admin-main div.

// ---------------------------------------------------------------------------
// Auto-create table if missing
// ---------------------------------------------------------------------------
db()->exec("
    CREATE TABLE IF NOT EXISTS sill_repartition_locative (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        affectation VARCHAR(50) NOT NULL UNIQUE,
        nb_logements INT UNSIGNED NOT NULL DEFAULT 0,
        surface_m2 DECIMAL(12,1) NOT NULL DEFAULT 0,
        loyer_annuel_net DECIMAL(15,0) NOT NULL DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// ---------------------------------------------------------------------------
// POST handling
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();

    // --- CSV IMPORT ---
    if ($action === 'import-csv') {
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            flash('error', 'Fichier CSV manquant ou erreur d\'upload.');
            header('Location: ?page=repartition');
            exit;
        }

        $content = file_get_contents($_FILES['csv_file']['tmp_name']);
        $sep = (substr_count($content, ';') > substr_count($content, ',')) ? ';' : ',';
        $lines = array_filter(array_map('trim', explode("\n", $content)));

        if (count($lines) < 2) {
            flash('error', 'Le fichier CSV est vide ou ne contient qu\'un en-tête.');
            header('Location: ?page=repartition');
            exit;
        }

        $header = array_map(function($h) { return strtolower(trim($h)); }, str_getcsv(array_shift($lines), $sep));

        $colAff = array_search('affectation', $header);
        if ($colAff === false) {
            flash('error', 'Colonne <code>affectation</code> introuvable. Colonnes trouvées : ' . implode(', ', $header));
            header('Location: ?page=repartition');
            exit;
        }

        $colMap = [
            'nb_logements'     => 'nb_logements',
            'nb_lots'          => 'nb_logements',
            'surface_m2'       => 'surface_m2',
            'surface_sup'      => 'surface_m2',
            'surface sup (m2)' => 'surface_m2',
            'loyer_annuel_net' => 'loyer_annuel_net',
            'loyer annuel net' => 'loyer_annuel_net',
            'loyer annuel net (chf)' => 'loyer_annuel_net',
        ];

        $inserted = 0;
        $updated  = 0;

        foreach ($lines as $line) {
            $row = str_getcsv($line, $sep);
            $aff = trim($row[$colAff] ?? '');
            if ($aff === '') continue;

            $fields = ['affectation' => $aff];
            foreach ($colMap as $csvName => $dbCol) {
                $idx = array_search($csvName, $header);
                if ($idx !== false && isset($row[$idx]) && trim($row[$idx]) !== '') {
                    $val = str_replace([' ', "'", ','], ['', '', '.'], trim($row[$idx]));
                    $fields[$dbCol] = $dbCol === 'surface_m2' ? (float) $val : (int) round((float) $val);
                }
            }

            $existing = queryOne('SELECT id FROM sill_repartition_locative WHERE affectation = ?', [$aff]);
            if ($existing) {
                $sets = [];
                $params = [];
                foreach ($fields as $col => $val) {
                    if ($col === 'affectation') continue;
                    $sets[] = "$col = ?";
                    $params[] = $val;
                }
                if ($sets) {
                    $params[] = $existing['id'];
                    query('UPDATE sill_repartition_locative SET ' . implode(', ', $sets) . ' WHERE id = ?', $params);
                    $updated++;
                }
            } else {
                $cols = implode(', ', array_keys($fields));
                $placeholders = implode(', ', array_fill(0, count($fields), '?'));
                query("INSERT INTO sill_repartition_locative ($cols) VALUES ($placeholders)", array_values($fields));
                $inserted++;
            }
        }

        flash('success', "Import terminé : $inserted créé(s), $updated mis à jour.");
        header('Location: ?page=repartition');
        exit;
    }

    // --- INLINE EDIT (single row) ---
    if ($action === 'edit') {
        $rid = (int) ($_POST['id'] ?? 0);
        $nb  = (int) ($_POST['nb_logements'] ?? 0);
        $sf  = (float) ($_POST['surface_m2'] ?? 0);
        $ly  = (int) round((float) ($_POST['loyer_annuel_net'] ?? 0));

        if ($rid > 0) {
            query('UPDATE sill_repartition_locative SET nb_logements = ?, surface_m2 = ?, loyer_annuel_net = ? WHERE id = ?',
                  [$nb, $sf, $ly, $rid]);
            flash('success', 'Ligne mise à jour.');
        }
        header('Location: ?page=repartition');
        exit;
    }

    // --- DELETE ---
    if ($action === 'delete') {
        if (!canDelete()) { flash('error', 'Suppression réservée aux administrateurs.'); header('Location: ?page=repartition'); exit; }
        $rid = (int) ($_POST['id'] ?? 0);
        if ($rid > 0) {
            query('DELETE FROM sill_repartition_locative WHERE id = ?', [$rid]);
            flash('success', 'Ligne supprimée.');
        }
        header('Location: ?page=repartition');
        exit;
    }

    // --- CREATE ---
    if ($action === 'create') {
        $aff = trim($_POST['affectation'] ?? '');
        $nb  = (int) ($_POST['nb_logements'] ?? 0);
        $sf  = (float) ($_POST['surface_m2'] ?? 0);
        $ly  = (int) round((float) ($_POST['loyer_annuel_net'] ?? 0));

        if ($aff === '') {
            flash('error', 'Le nom d\'affectation est obligatoire.');
            header('Location: ?page=repartition');
            exit;
        }

        $exists = queryOne('SELECT id FROM sill_repartition_locative WHERE affectation = ?', [$aff]);
        if ($exists) {
            flash('error', "L'affectation « $aff » existe déjà.");
            header('Location: ?page=repartition');
            exit;
        }

        query('INSERT INTO sill_repartition_locative (affectation, nb_logements, surface_m2, loyer_annuel_net) VALUES (?, ?, ?, ?)',
              [$aff, $nb, $sf, $ly]);
        flash('success', "Affectation « $aff » créée.");
        header('Location: ?page=repartition');
        exit;
    }
}

// ---------------------------------------------------------------------------
// VIEW: Edit form (single row)
// ---------------------------------------------------------------------------
if ($action === 'edit' && $id) {
    $item = queryOne('SELECT * FROM sill_repartition_locative WHERE id = ?', [$id]);
    if (!$item) {
        flash('error', 'Ligne introuvable.');
        header('Location: ?page=repartition');
        exit;
    }
    ?>
    <div class="page-header">
        <h1>Modifier — <?= e($item['affectation']) ?></h1>
        <a href="?page=repartition" class="btn btn-secondary">Retour</a>
    </div>

    <form method="post" action="?page=repartition&action=edit" class="admin-form">
        <?= csrfField() ?>
        <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">

        <div class="form-row">
            <div class="form-group">
                <label>Affectation</label>
                <input type="text" value="<?= e($item['affectation']) ?>" disabled>
            </div>
            <div class="form-group">
                <label for="nb_logements">Nb logements</label>
                <input type="number" id="nb_logements" name="nb_logements" value="<?= (int) $item['nb_logements'] ?>" min="0" required>
            </div>
            <div class="form-group">
                <label for="surface_m2">Surface SUP (m²)</label>
                <input type="number" id="surface_m2" name="surface_m2" value="<?= e($item['surface_m2']) ?>" min="0" step="0.1" required>
            </div>
            <div class="form-group">
                <label for="loyer_annuel_net">Loyer annuel net (CHF)</label>
                <input type="number" id="loyer_annuel_net" name="loyer_annuel_net" value="<?= (int) $item['loyer_annuel_net'] ?>" min="0" required>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="?page=repartition" class="btn btn-secondary">Retour</a>
        </div>
    </form>
    <?php
    return;
}

// ---------------------------------------------------------------------------
// VIEW: List (default)
// ---------------------------------------------------------------------------
$rows = query('SELECT * FROM sill_repartition_locative ORDER BY nb_logements DESC, affectation');

$totalLogements = 0;
$totalSurface   = 0;
$totalLoyer     = 0;
foreach ($rows as $r) {
    $totalLogements += (int) $r['nb_logements'];
    $totalSurface   += (float) $r['surface_m2'];
    $totalLoyer     += (float) $r['loyer_annuel_net'];
}
?>

<div class="page-header">
    <h1>Répartition locative</h1>
    <div style="display:flex; gap:8px; align-items:center;">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('csv-import-panel').classList.toggle('hidden')">Importer CSV</button>
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('add-row-panel').classList.toggle('hidden')">Ajouter</button>
    </div>
</div>

<!-- CSV Import Panel -->
<div id="csv-import-panel" class="hidden" style="background:#F0F4FF; border:1px solid var(--admin-accent); padding:20px 24px; margin-bottom:20px;">
    <h3 style="margin:0 0 8px; font-size:14px; font-weight:600;">Importer depuis un fichier CSV</h3>
    <p style="font-size:13px; color:#666; margin-bottom:12px;">
        Colonne obligatoire : <code>affectation</code>.
        Colonnes reconnues : <code>nb_logements</code> (ou <code>nb_lots</code>), <code>surface_m2</code> (ou <code>surface_sup</code>), <code>loyer_annuel_net</code>.
        Les affectations existantes seront mises à jour, les nouvelles créées.
    </p>
    <form method="post" action="?page=repartition&action=import-csv" enctype="multipart/form-data" style="display:flex; gap:12px; align-items:end;">
        <?= csrfField() ?>
        <div>
            <label style="font-size:12px; font-weight:600; display:block; margin-bottom:4px;">Fichier CSV</label>
            <input type="file" name="csv_file" accept=".csv,.txt" required style="font-size:13px;">
        </div>
        <button type="submit" class="btn btn-primary" onclick="return confirm('Importer les données de répartition ?')">Lancer l'import</button>
    </form>
</div>

<style>.hidden { display: none !important; }</style>

<!-- Add Row Panel -->
<div id="add-row-panel" class="hidden" style="background:#F5F0EB; border:1px solid #E0E0E0; padding:20px 24px; margin-bottom:20px;">
    <h3 style="margin:0 0 12px; font-size:14px; font-weight:600;">Nouvelle affectation</h3>
    <form method="post" action="?page=repartition&action=create" style="display:flex; gap:12px; align-items:end; flex-wrap:wrap;">
        <?= csrfField() ?>
        <div>
            <label style="font-size:12px; font-weight:600; display:block; margin-bottom:4px;">Affectation</label>
            <input type="text" name="affectation" required style="font-size:13px; padding:6px 10px; width:160px;">
        </div>
        <div>
            <label style="font-size:12px; font-weight:600; display:block; margin-bottom:4px;">Nb logements</label>
            <input type="number" name="nb_logements" value="0" min="0" style="font-size:13px; padding:6px 10px; width:100px;">
        </div>
        <div>
            <label style="font-size:12px; font-weight:600; display:block; margin-bottom:4px;">Surface m²</label>
            <input type="number" name="surface_m2" value="0" min="0" step="0.1" style="font-size:13px; padding:6px 10px; width:120px;">
        </div>
        <div>
            <label style="font-size:12px; font-weight:600; display:block; margin-bottom:4px;">Loyer annuel CHF</label>
            <input type="number" name="loyer_annuel_net" value="0" min="0" style="font-size:13px; padding:6px 10px; width:140px;">
        </div>
        <button type="submit" class="btn btn-primary">Créer</button>
    </form>
</div>

<p class="admin-info">
    Répartition par type d'affectation — <?= count($rows) ?> catégories, <?= number_format($totalLogements, 0, '.', "'") ?> logements, <?= number_format($totalLoyer, 0, '.', "'") ?> CHF loyer annuel net.
</p>

<?php if (empty($rows)): ?>
    <p class="admin-empty">Aucune donnée. Importez un CSV ou ajoutez des lignes manuellement.</p>
<?php else: ?>

<div class="table-wrapper">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Affectation</th>
                <th style="text-align:right">Nb logements</th>
                <th style="text-align:right">Part (logements)</th>
                <th style="text-align:right">Surface m²</th>
                <th style="text-align:right">Loyer annuel net</th>
                <th style="text-align:right">Part (loyer)</th>
                <th style="text-align:right">Loyer net /m²</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r):
            $partLog  = $totalLogements > 0 ? (int) $r['nb_logements'] / $totalLogements : 0;
            $partLoy  = $totalLoyer > 0 ? (float) $r['loyer_annuel_net'] / $totalLoyer : 0;
            $loyerM2  = (float) $r['surface_m2'] > 0 ? (float) $r['loyer_annuel_net'] / (float) $r['surface_m2'] : 0;
        ?>
            <tr>
                <td><strong><?= e($r['affectation']) ?></strong></td>
                <td style="text-align:right"><?= number_format((int) $r['nb_logements'], 0, '.', "'") ?></td>
                <td style="text-align:right; font-weight:600; color:var(--admin-accent, #0047BB)"><?= number_format($partLog * 100, 1, '.', '') ?> %</td>
                <td style="text-align:right"><?= number_format((float) $r['surface_m2'], 1, '.', "'") ?></td>
                <td style="text-align:right"><?= number_format((float) $r['loyer_annuel_net'], 0, '.', "'") ?></td>
                <td style="text-align:right; color:#666"><?= number_format($partLoy * 100, 1, '.', '') ?> %</td>
                <td style="text-align:right; color:#666"><?= number_format($loyerM2, 0, '.', "'") ?></td>
                <td class="cell-actions" style="white-space:nowrap">
                    <a href="?page=repartition&action=edit&id=<?= (int) $r['id'] ?>" class="btn btn-sm btn-secondary">Modifier</a>
                    <?php if (canDelete()): ?>
                    <form method="post" action="?page=repartition&action=delete" class="form-inline" style="display:inline"
                          onsubmit="return confirm('Supprimer cette affectation ?')">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                        <button type="submit" class="btn btn-sm" style="color:#999;background:none;border:none;font-size:11px;padding:4px 8px">&times;</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="font-weight:700; border-top:2px solid #000;">
                <td>TOTAL</td>
                <td style="text-align:right"><?= number_format($totalLogements, 0, '.', "'") ?></td>
                <td style="text-align:right">100 %</td>
                <td style="text-align:right"><?= number_format($totalSurface, 1, '.', "'") ?></td>
                <td style="text-align:right"><?= number_format($totalLoyer, 0, '.', "'") ?></td>
                <td style="text-align:right">100 %</td>
                <td style="text-align:right"><?= $totalSurface > 0 ? number_format($totalLoyer / $totalSurface, 0, '.', "'") : '—' ?></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>

<?php endif; ?>
