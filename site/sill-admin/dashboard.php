<?php
// dashboard.php — Tableau de bord admin SILL SA
// Included from layout.php inside admin-main div.
// Has access to: db(), query(), queryOne(), e(), csrfField(), csrfCheck(), flash(), $action, $id

$kpi_total  = (int) queryOne("SELECT COUNT(*) AS c FROM sill_kpi")['c'];
$kpi_public = (int) queryOne("SELECT COUNT(*) AS c FROM sill_kpi WHERE is_public = 1")['c'];
$pages_active = (int) queryOne("SELECT COUNT(*) AS c FROM sill_pages WHERE is_active = 1")['c'];
$publications_active = (int) queryOne("SELECT COUNT(*) AS c FROM sill_publications WHERE is_active = 1")['c'];
$menu_active = (int) queryOne("SELECT COUNT(*) AS c FROM sill_menu WHERE is_active = 1")['c'];
?>

<div class="page-header">
    <h1>Tableau de bord</h1>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-value"><?= $kpi_total ?></div>
        <div class="stat-label">KPIs total</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $kpi_public ?></div>
        <div class="stat-label">KPIs visibles (homepage)</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $pages_active ?></div>
        <div class="stat-label">Pages actives</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $publications_active ?></div>
        <div class="stat-label">Publications actives</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $menu_active ?></div>
        <div class="stat-label">Éléments de menu actifs</div>
    </div>
</div>
