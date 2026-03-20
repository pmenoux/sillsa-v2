<?php
// en-bref.php — Fiche signalétique SFAMA / AMAS — Vue backend de contrôle
// Included from layout.php inside admin-main div.
// Directive : AMAS (ex-SFAMA) — Publication des chiffres clés pour les fonds immobiliers suisses

// ─── Chargement données ──────────────────────────────────────────────
$kpis = query("SELECT kpi_key, label, value_num, value_text, unit, category FROM sill_kpi ORDER BY category, sort_order");
$kpiMap = [];
foreach ($kpis as $k) {
    $kpiMap[$k['kpi_key']] = $k;
}

// Immeubles actifs
$immeubles = query("SELECT * FROM sill_immeubles WHERE is_active = 1 ORDER BY annee_livraison DESC");
$nbImmeubles = count($immeubles);

// Répartition locative
$repart = query("SELECT * FROM sill_repartition_locative ORDER BY loyer_annuel_net DESC");
$totalLoyer = array_sum(array_column($repart, 'loyer_annuel_net'));
$totalSurface = array_sum(array_column($repart, 'surface_m2'));
$totalLogements = array_sum(array_column($repart, 'nb_logements'));

// LUP = LLM + LLA + LLA-protégé
$loyerLUP = 0;
$logementsLUP = 0;
foreach ($repart as $r) {
    if (in_array($r['affectation'], ['LLM', 'LLA', 'LLA - protégé'])) {
        $loyerLUP += (float) $r['loyer_annuel_net'];
        $logementsLUP += (int) $r['nb_logements'];
    }
}
$pctLUP = $totalLoyer > 0 ? $loyerLUP / $totalLoyer * 100 : 0;

// Helper
function kv($kpiMap, $key, $fallback = '—') {
    if (!isset($kpiMap[$key])) return '<span style="color:#CC0000" title="KPI manquant : ' . htmlspecialchars($key) . '">' . $fallback . ' ⚠</span>';
    $k = $kpiMap[$key];
    if ($k['value_num'] !== null) {
        $fmt = kpiFormat($k['value_num']);
        return number_format((float) $k['value_num'], $fmt['decimals'], '.', "'") . ' ' . e($k['unit'] ?? '');
    }
    return e($k['value_text'] ?? $fallback);
}

// Contrôle de cohérence
$warnings = [];
$kpiEtatLocatif = isset($kpiMap['etat_locatif']) ? (float) $kpiMap['etat_locatif']['value_num'] * 1e6 : null;
if ($kpiEtatLocatif && abs($kpiEtatLocatif - $totalLoyer) / $totalLoyer > 0.05) {
    $warnings[] = "Écart état locatif : KPI = " . number_format($kpiEtatLocatif, 0, '.', "'") . " CHF vs répartition = " . number_format($totalLoyer, 0, '.', "'") . " CHF";
}
$kpiNbLots = isset($kpiMap['nb_lots']) ? (int) $kpiMap['nb_lots']['value_num'] : null;
if ($kpiNbLots && $kpiNbLots !== $totalLogements) {
    $warnings[] = "Écart logements : KPI nb_lots = $kpiNbLots vs répartition = $totalLogements";
}
$kpiSurface = isset($kpiMap['surface_sup']) ? (float) $kpiMap['surface_sup']['value_num'] : null;
if ($kpiSurface && abs($kpiSurface - $totalSurface) > 100) {
    $warnings[] = "Écart surface : KPI = " . number_format($kpiSurface, 0, '.', "'") . " m² vs répartition = " . number_format($totalSurface, 0, '.', "'") . " m²";
}
?>

<div class="page-header">
    <h1>En bref — Fiche signalétique AMAS</h1>
    <div style="display:flex; gap:8px; align-items:center;">
        <span style="font-size:12px; color:#999;">Directive AMAS (ex-SFAMA) — Données au 31.12.2025</span>
    </div>
</div>

<?php if ($warnings): ?>
<div style="background:#FFF3CD; border:1px solid #FFD700; padding:12px 16px; margin-bottom:20px; border-radius:4px;">
    <strong style="color:#856404;">Contrôles de cohérence</strong>
    <ul style="margin:8px 0 0; padding-left:20px; font-size:13px; color:#856404;">
        <?php foreach ($warnings as $w): ?>
            <li><?= e($w) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<p class="admin-info">
    Vue de contrôle avant publication. Toutes les données proviennent de la base de données (KPIs + répartition locative).<br>
    Les valeurs manquantes sont signalées en <span style="color:#CC0000">rouge ⚠</span>.
    <a href="?page=kpi">Gérer les KPIs</a> — <a href="?page=repartition">Gérer la répartition</a>
</p>

<!-- ── 1. Informations générales ── -->
<h2 class="form-section-title" style="margin-top:24px;">1. Informations générales</h2>
<div class="table-wrapper">
    <table class="admin-table">
        <tbody>
            <tr><td style="width:50%; font-weight:600">Raison sociale</td><td>Société Immobilière Lausannoise pour le Logement SA</td></tr>
            <tr><td style="font-weight:600">Forme juridique</td><td>Société anonyme de droit privé</td></tr>
            <tr><td style="font-weight:600">Siège</td><td>Lausanne</td></tr>
            <tr><td style="font-weight:600">Année de création</td><td><?= kv($kpiMap, 'annee_creation') ?></td></tr>
            <tr><td style="font-weight:600">Capital social</td><td><?= kv($kpiMap, 'capital_social') ?></td></tr>
            <tr><td style="font-weight:600">Actionnaires</td><td>Ville de Lausanne (100 %)</td></tr>
        </tbody>
    </table>
</div>

<!-- ── 2. Portefeuille immobilier ── -->
<h2 class="form-section-title" style="margin-top:32px;">2. Portefeuille immobilier</h2>
<div class="table-wrapper">
    <table class="admin-table">
        <tbody>
            <tr><td style="width:50%; font-weight:600">Nombre de développements</td><td><?= $nbImmeubles ?></td></tr>
            <tr><td style="font-weight:600">Nombre de logements et lots</td><td><?= number_format($totalLogements, 0, '.', "'") ?></td></tr>
            <tr><td style="font-weight:600">Surface locative totale (SUP)</td><td><?= number_format($totalSurface, 0, '.', "'") ?> m²</td></tr>
            <tr><td style="font-weight:600">État locatif net annuel</td><td><?= number_format($totalLoyer / 1e6, 2, '.', "'") ?> M CHF</td></tr>
            <tr><td style="font-weight:600">Loyer net moyen par m² SUP</td><td><?= $totalSurface > 0 ? number_format($totalLoyer / $totalSurface, 2, '.', "'") : '—' ?> CHF/m²/an</td></tr>
            <tr><td style="font-weight:600">Part LUP (LLM + LLA)</td><td style="font-weight:700; color:var(--admin-accent, #0047BB)"><?= number_format($pctLUP, 1) ?> % du loyer annuel net</td></tr>
        </tbody>
    </table>
</div>

<!-- ── 3. Évaluation & rendements ── -->
<h2 class="form-section-title" style="margin-top:32px;">3. Évaluation et rendements</h2>
<div class="table-wrapper">
    <table class="admin-table">
        <tbody>
            <tr><td style="width:50%; font-weight:600">Valeur vénale (DCF)</td><td><?= kv($kpiMap, 'valeur_dcf') ?></td></tr>
            <tr><td style="font-weight:600">Valeur comptable</td><td><?= kv($kpiMap, 'valeur_comptable') ?></td></tr>
            <tr><td style="font-weight:600">Valeur ECA</td><td><?= kv($kpiMap, 'valeur_eca') ?></td></tr>
            <tr><td style="font-weight:600">Coût de construction</td><td><?= kv($kpiMap, 'cout_construction') ?></td></tr>
            <tr><td style="font-weight:600">Rendement / coût de construction</td><td><?= kv($kpiMap, 'rdt_cout_constr') ?></td></tr>
            <tr><td style="font-weight:600">Rendement / valeur comptable</td><td><?= kv($kpiMap, 'rdt_val_comptable') ?></td></tr>
            <tr><td style="font-weight:600">Rendement / DCF</td><td><?= kv($kpiMap, 'rdt_dcf') ?></td></tr>
            <tr><td style="font-weight:600">Valeur théorique à 3.5 %</td><td><?= kv($kpiMap, 'valeur_theo_35') ?></td></tr>
        </tbody>
    </table>
</div>

<!-- ── 4. Financement ── -->
<h2 class="form-section-title" style="margin-top:32px;">4. Financement</h2>
<div class="table-wrapper">
    <table class="admin-table">
        <tbody>
            <tr><td style="width:50%; font-weight:600">Dette hypothécaire</td><td><?= kv($kpiMap, 'dette_hypo') ?></td></tr>
            <tr><td style="font-weight:600">Cédules hypothécaires totales</td><td><?= kv($kpiMap, 'cedules_total') ?></td></tr>
            <tr><td style="font-weight:600">Taux d'avance moyen / DCF</td><td><?= kv($kpiMap, 'taux_avance_dcf') ?></td></tr>
        </tbody>
    </table>
</div>

<!-- ── 5. Charges d'exploitation ── -->
<h2 class="form-section-title" style="margin-top:32px;">5. Charges d'exploitation</h2>
<div class="table-wrapper">
    <table class="admin-table">
        <tbody>
            <tr><td style="width:50%; font-weight:600">Charges moyennes par m² SUP</td><td><?= kv($kpiMap, 'charges_moy_m2') ?></td></tr>
            <tr><td style="font-weight:600">Ratio charges / état locatif</td><td><?= kv($kpiMap, 'charges_pct_el') ?></td></tr>
        </tbody>
    </table>
</div>

<!-- ── 6. Répartition par affectation ── -->
<h2 class="form-section-title" style="margin-top:32px;">6. Répartition par type d'affectation</h2>
<p style="font-size:12px; color:#999; margin-bottom:12px;">Part du loyer annuel net — conformément aux directives de calcul AMAS pour les fonds immobiliers suisses</p>
<div class="table-wrapper">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Affectation</th>
                <th style="text-align:right">Logements / lots</th>
                <th style="text-align:right">Surface m²</th>
                <th style="text-align:right">Loyer annuel net CHF</th>
                <th style="text-align:right">Loyer net /m²</th>
                <th style="text-align:right">Part (loyer)</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($repart as $r):
            $part = $totalLoyer > 0 ? (float) $r['loyer_annuel_net'] / $totalLoyer * 100 : 0;
            $loyerM2 = (float) $r['surface_m2'] > 0 ? (float) $r['loyer_annuel_net'] / (float) $r['surface_m2'] : 0;
        ?>
            <tr>
                <td><strong><?= e($r['affectation']) ?></strong></td>
                <td style="text-align:right"><?= number_format((int) $r['nb_logements'], 0, '.', "'") ?></td>
                <td style="text-align:right"><?= number_format((float) $r['surface_m2'], 1, '.', "'") ?></td>
                <td style="text-align:right"><?= number_format((float) $r['loyer_annuel_net'], 0, '.', "'") ?></td>
                <td style="text-align:right; color:#666"><?= number_format($loyerM2, 0, '.', "'") ?></td>
                <td style="text-align:right; font-weight:600; color:var(--admin-accent, #0047BB)"><?= number_format($part, 1) ?> %</td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="font-weight:700; border-top:2px solid #000;">
                <td>Total</td>
                <td style="text-align:right"><?= number_format($totalLogements, 0, '.', "'") ?></td>
                <td style="text-align:right"><?= number_format($totalSurface, 1, '.', "'") ?></td>
                <td style="text-align:right"><?= number_format($totalLoyer, 0, '.', "'") ?></td>
                <td style="text-align:right; color:#666"><?= $totalSurface > 0 ? number_format($totalLoyer / $totalSurface, 0, '.', "'") : '—' ?></td>
                <td style="text-align:right">100 %</td>
            </tr>
        </tfoot>
    </table>
</div>

<!-- ── 7. Performance énergétique & ESG ── -->
<h2 class="form-section-title" style="margin-top:32px;">7. Performance énergétique et ESG</h2>
<div class="table-wrapper">
    <table class="admin-table">
        <tbody>
            <tr><td style="width:50%; font-weight:600">Indice de dépense de chaleur (IDC)</td><td><?= kv($kpiMap, 'sill_idc') ?></td></tr>
            <tr><td style="font-weight:600">Émissions CO₂ scope 1+2</td><td><?= kv($kpiMap, 'sill_co2') ?></td></tr>
            <tr><td style="font-weight:600">Consommation énergétique moyenne</td><td><?= kv($kpiMap, 'conso_energie_m2') ?></td></tr>
            <tr><td style="font-weight:600">Rapport de surveillance</td><td>Signa-Terre SA / PwC (ISAE 3000)</td></tr>
        </tbody>
    </table>
</div>

<!-- ── Sources ── -->
<div style="margin-top:32px; padding:16px 20px; background:#F5F0EB; border-radius:4px; font-size:12px; color:#666;">
    <strong>Sources et méthodologie</strong><br>
    Rapport annuel 2025 — États locatifs au 31.12.2025 — Rapport Signa-Terre / PwC 2024<br>
    Répartition par affectation : proportion du loyer annuel net (directives de calcul AMAS pour les fonds immobiliers suisses)<br>
    Rendements calculés sur l'état locatif net annuel
</div>
