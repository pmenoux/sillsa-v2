<?php
// kpi.php — Gestion visibilité KPIs
// Included from layout.php inside admin-main div.
// Has access to: db(), query(), queryOne(), e(), csrfField(), csrfCheck(), flash(), $action, $id
// Values are imported from Excel — NOT editable here. Only is_public toggle is exposed.

// --- POST: toggle visibility ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'toggle') {
    csrfCheck();
    $kpi_id    = (int) ($_POST['kpi_id'] ?? 0);
    $is_public = (int) ($_POST['is_public'] ?? 0);

    if ($kpi_id > 0) {
        $stmt = db()->prepare("UPDATE sill_kpi SET is_public = ? WHERE id = ?");
        $stmt->execute([$is_public, $kpi_id]);
        flash('success', 'Visibilité du KPI mise à jour.');
    }

    header('Location: ?page=kpi');
    exit;
}

// --- Query all KPIs ---
$kpis = query("SELECT * FROM sill_kpi ORDER BY sort_order, id");
?>

<div class="page-header">
    <h1>KPIs — Visibilité</h1>
</div>

<p class="admin-info">
    Les valeurs sont importees depuis Excel. Ici vous controlez uniquement quels KPIs sont visibles sur la homepage.
</p>

<?php if (empty($kpis)): ?>
    <p class="admin-empty">Aucun KPI trouvé dans la base de données.</p>
<?php else: ?>
<div class="table-wrapper">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Label</th>
                <th>Valeur (readonly)</th>
                <th>Unité</th>
                <th>Visible</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($kpis as $kpi): ?>
            <tr>
                <td><?= e($kpi['label']) ?></td>
                <td class="cell-readonly"><?= e($kpi['value']) ?></td>
                <td><?= e($kpi['unit'] ?? '') ?></td>
                <td>
                    <form method="post" action="?page=kpi&action=toggle">
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
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
