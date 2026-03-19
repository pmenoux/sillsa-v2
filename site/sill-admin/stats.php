<?php
// sill-admin/stats.php — Analytics dashboard
// Included from layout.php

// ── Ensure table exists ──
try {
    db()->exec("CREATE TABLE IF NOT EXISTS sill_analytics (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        page_path VARCHAR(255) NOT NULL,
        visitor_hash CHAR(16) NOT NULL,
        is_mobile TINYINT(1) DEFAULT 0,
        geo VARCHAR(10) DEFAULT '',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_date (created_at),
        INDEX idx_page (page_path),
        INDEX idx_visitor (visitor_hash, created_at),
        INDEX idx_geo (geo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    // Add geo column if table existed before this update
    $cols = array_column(db()->query("SHOW COLUMNS FROM sill_analytics")->fetchAll(), 'Field');
    if (!in_array('geo', $cols)) {
        db()->exec("ALTER TABLE sill_analytics ADD COLUMN geo VARCHAR(10) DEFAULT '' AFTER is_mobile");
        db()->exec("ALTER TABLE sill_analytics ADD INDEX idx_geo (geo)");
    }
} catch (\Throwable $e) {}

// ── Period selection ──
$period = $_GET['period'] ?? '30';
$periods = ['7' => '7 jours', '30' => '30 jours', '90' => '3 mois', '365' => '12 mois'];
if (!isset($periods[$period])) $period = '30';

$since = date('Y-m-d', strtotime("-{$period} days"));

// ── Queries ──
$totalViews = (int)(queryOne("SELECT COUNT(*) AS c FROM sill_analytics WHERE created_at >= ?", [$since])['c'] ?? 0);
$uniqueVisitors = (int)(queryOne("SELECT COUNT(DISTINCT visitor_hash) AS c FROM sill_analytics WHERE created_at >= ?", [$since])['c'] ?? 0);
$mobileCount = (int)(queryOne("SELECT COUNT(*) AS c FROM sill_analytics WHERE is_mobile = 1 AND created_at >= ?", [$since])['c'] ?? 0);
$mobilePct = $totalViews > 0 ? round($mobileCount / $totalViews * 100) : 0;
$desktopPct = 100 - $mobilePct;

// Views per day (for chart)
$dailyViews = query(
    "SELECT DATE(created_at) AS day, COUNT(*) AS views, COUNT(DISTINCT visitor_hash) AS visitors
     FROM sill_analytics WHERE created_at >= ? GROUP BY DATE(created_at) ORDER BY day",
    [$since]
);

// Top pages
$topPages = query(
    "SELECT page_path, COUNT(*) AS views, COUNT(DISTINCT visitor_hash) AS visitors
     FROM sill_analytics WHERE created_at >= ?
     GROUP BY page_path ORDER BY views DESC LIMIT 15",
    [$since]
);

// Geo breakdown
$geoData = query(
    "SELECT geo, COUNT(*) AS views, COUNT(DISTINCT visitor_hash) AS visitors
     FROM sill_analytics WHERE created_at >= ? AND geo != ''
     GROUP BY geo ORDER BY views DESC LIMIT 20",
    [$since]
);

// Today
$today = date('Y-m-d');
$todayViews = (int)(queryOne("SELECT COUNT(*) AS c FROM sill_analytics WHERE DATE(created_at) = ?", [$today])['c'] ?? 0);
$todayVisitors = (int)(queryOne("SELECT COUNT(DISTINCT visitor_hash) AS c FROM sill_analytics WHERE DATE(created_at) = ?", [$today])['c'] ?? 0);

// Prepare chart data
$chartLabels = [];
$chartViews = [];
$chartVisitors = [];
foreach ($dailyViews as $d) {
    $chartLabels[] = date('d/m', strtotime($d['day']));
    $chartViews[] = (int)$d['views'];
    $chartVisitors[] = (int)$d['visitors'];
}
?>

<div class="admin-section-header">
    <h2>Statistiques</h2>
    <div style="display:flex; gap:6px;">
        <?php foreach ($periods as $val => $label): ?>
            <a href="?page=stats&period=<?= $val ?>" class="btn btn-sm <?= $period === $val ? 'btn-primary' : 'btn-secondary' ?>"><?= $label ?></a>
        <?php endforeach; ?>
    </div>
</div>

<!-- KPI Cards -->
<div class="stats-kpi-grid">
    <div class="stats-kpi-card">
        <span class="stats-kpi-value"><?= number_format($todayViews, 0, '.', ' ') ?></span>
        <span class="stats-kpi-label">Vues aujourd'hui</span>
    </div>
    <div class="stats-kpi-card">
        <span class="stats-kpi-value"><?= number_format($todayVisitors, 0, '.', ' ') ?></span>
        <span class="stats-kpi-label">Visiteurs aujourd'hui</span>
    </div>
    <div class="stats-kpi-card">
        <span class="stats-kpi-value"><?= number_format($totalViews, 0, '.', ' ') ?></span>
        <span class="stats-kpi-label">Vues (<?= $periods[$period] ?>)</span>
    </div>
    <div class="stats-kpi-card">
        <span class="stats-kpi-value"><?= number_format($uniqueVisitors, 0, '.', ' ') ?></span>
        <span class="stats-kpi-label">Visiteurs uniques</span>
    </div>
    <div class="stats-kpi-card">
        <span class="stats-kpi-value"><?= $mobilePct ?>%</span>
        <span class="stats-kpi-label">Mobile</span>
    </div>
    <div class="stats-kpi-card">
        <span class="stats-kpi-value"><?= $desktopPct ?>%</span>
        <span class="stats-kpi-label">Desktop</span>
    </div>
</div>

<!-- Chart -->
<div class="stats-chart-card">
    <h3>Fréquentation quotidienne</h3>
    <div style="height: 280px;">
        <canvas id="statsChart"></canvas>
    </div>
</div>

<!-- Geo + Top Pages side by side -->
<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px; align-items: start;">

<!-- Geo -->
<div class="stats-table-card">
    <h3>Origine géographique</h3>
    <?php if (empty($geoData)): ?>
        <p style="color:#999; font-size:13px;">Aucune donnée géographique pour cette période.</p>
    <?php else: ?>
        <?php
        // Map region codes to readable names
        $regionNames = [
            'CH-VD' => 'Vaud', 'CH-GE' => 'Genève', 'CH-VS' => 'Valais',
            'CH-FR' => 'Fribourg', 'CH-NE' => 'Neuchâtel', 'CH-JU' => 'Jura',
            'CH-BE' => 'Berne', 'CH-ZH' => 'Zurich', 'CH-BS' => 'Bâle-Ville',
            'CH-BL' => 'Bâle-Campagne', 'CH-AG' => 'Argovie', 'CH-SO' => 'Soleure',
            'CH-LU' => 'Lucerne', 'CH-TI' => 'Tessin', 'CH-SG' => 'Saint-Gall',
            'CH-GR' => 'Grisons', 'CH-TG' => 'Thurgovie', 'CH-ZG' => 'Zoug',
            'CH-SZ' => 'Schwyz', 'CH-SH' => 'Schaffhouse',
        ];
        ?>
        <table class="admin-table">
            <thead>
                <tr><th>Région</th><th style="text-align:right">Visiteurs</th></tr>
            </thead>
            <tbody>
                <?php foreach ($geoData as $g):
                    $code = $g['geo'];
                    $label = $regionNames[$code] ?? $code;
                    // For non-CH, show just country code
                    if (!str_starts_with($code, 'CH-')) {
                        $countryNames = ['FR' => 'France', 'DE' => 'Allemagne', 'IT' => 'Italie', 'US' => 'États-Unis', 'GB' => 'Royaume-Uni'];
                        $cc = explode('-', $code)[0];
                        $label = $countryNames[$cc] ?? strtoupper($cc);
                    }
                ?>
                    <tr>
                        <td><?= e($label) ?></td>
                        <td style="text-align:right"><?= number_format($g['visitors'], 0, '.', ' ') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Top Pages -->
<div class="stats-table-card">
    <h3>Pages les plus visitées</h3>
    <?php if (empty($topPages)): ?>
        <p style="color:#999; font-size:13px;">Aucune donnée pour cette période.</p>
    <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Page</th>
                    <th style="text-align:right">Vues</th>
                    <th style="text-align:right">Visiteurs</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($topPages as $tp): ?>
                    <tr>
                        <td><code>/<?= e($tp['page_path']) ?></code></td>
                        <td style="text-align:right"><?= number_format($tp['views'], 0, '.', ' ') ?></td>
                        <td style="text-align:right"><?= number_format($tp['visitors'], 0, '.', ' ') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

</div><!-- /grid geo+pages -->

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Chart === 'undefined') return;
    var ctx = document.getElementById('statsChart');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($chartLabels) ?>,
            datasets: [
                {
                    label: 'Pages vues',
                    data: <?= json_encode($chartViews) ?>,
                    backgroundColor: 'rgba(0, 71, 187, 0.15)',
                    borderColor: '#0047BB',
                    borderWidth: 1.5,
                    borderRadius: 2,
                    order: 2
                },
                {
                    label: 'Visiteurs uniques',
                    data: <?= json_encode($chartVisitors) ?>,
                    type: 'line',
                    borderColor: '#FF0000',
                    backgroundColor: 'transparent',
                    borderWidth: 2,
                    pointBackgroundColor: '#FF0000',
                    pointBorderColor: '#FFF',
                    pointBorderWidth: 2,
                    pointRadius: 3,
                    tension: 0.3,
                    order: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            scales: {
                x: { border: { display: false }, grid: { display: false }, ticks: { font: { size: 11 }, color: '#999' } },
                y: { border: { display: false }, grid: { color: '#EEE' }, beginAtZero: true, ticks: { font: { size: 11 }, color: '#999' } }
            },
            plugins: {
                legend: { position: 'bottom', labels: { font: { size: 12 }, padding: 16, usePointStyle: true, pointStyleWidth: 10 } },
                tooltip: { backgroundColor: '#1A1A1A', titleFont: { size: 13 }, bodyFont: { size: 12 }, padding: 12, cornerRadius: 2 }
            }
        }
    });
});
</script>
