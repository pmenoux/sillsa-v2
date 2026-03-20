<?php
// templates/en-bref.php — Fiche signalétique AMAS (ex-SFAMA) — Page publique
// Benchmark : FIR, La Foncière, Patrimonium — hero KPIs + graphiques + benchmark
// Principe éditorial : mission sociale + solidité, pas de rendements

// ─── Chargement données ──────────────────────────────────────────────
$kpis = query("SELECT kpi_key, label, value_num, value_text, unit FROM sill_kpi ORDER BY sort_order");
$kpiMap = [];
foreach ($kpis as $k) {
    $kpiMap[$k['kpi_key']] = $k;
}

// Immeubles actifs (triés par année pour le graphique d'évolution)
$immeubles = query("SELECT nom, nb_logements, annee_livraison FROM sill_immeubles WHERE is_active = 1 ORDER BY annee_livraison ASC");
$nbImmeubles = count($immeubles);

// Répartition locative
$repart = query("SELECT * FROM sill_repartition_locative ORDER BY loyer_annuel_net DESC");
$totalLoyer = array_sum(array_column($repart, 'loyer_annuel_net'));
$totalSurface = array_sum(array_column($repart, 'surface_m2'));
$totalLogements = array_sum(array_column($repart, 'nb_logements'));

// LUP = LLM + LLA + LLA-protégé
$loyerLUP = 0;
foreach ($repart as $r) {
    if (in_array($r['affectation'], ['LLM', 'LLA', 'LLA - protégé'])) {
        $loyerLUP += (float) $r['loyer_annuel_net'];
    }
}
$pctLUP = $totalLoyer > 0 ? $loyerLUP / $totalLoyer * 100 : 0;

// Loyer moyen SILL vs marché (pour benchmark)
$loyerMoyenSILL = $totalSurface > 0 ? $totalLoyer / $totalSurface : 0;
$loyerMoyenMarche = isset($kpiMap['loyer_moyen_m2']) ? (float) $kpiMap['loyer_moyen_m2']['value_num'] : 320;

// Données graphique évolution du parc (cumul logements par année)
$parcData = [];
$cumul = 0;
foreach ($immeubles as $im) {
    $annee = (int) $im['annee_livraison'];
    if ($annee <= 0) continue;
    $cumul += (int) $im['nb_logements'];
    $parcData[$annee] = $cumul;
}

// Données répartition pour doughnut
$doughnutLabels = [];
$doughnutValues = [];
$doughnutColors = ['#FF0000', '#CC0000', '#FFD700', '#0047BB', '#333333', '#999999'];
foreach ($repart as $i => $r) {
    $part = $totalLoyer > 0 ? (float) $r['loyer_annuel_net'] / $totalLoyer * 100 : 0;
    $doughnutLabels[] = $r['affectation'];
    $doughnutValues[] = round($part, 1);
}

// Helper : valeur KPI formatée ou tiret
function kvPublic($kpiMap, $key) {
    if (!isset($kpiMap[$key]) || $kpiMap[$key]['value_num'] === null) return '—';
    $fmt = kpiFormat($kpiMap[$key]['value_num']);
    return number_format((float) $kpiMap[$key]['value_num'], $fmt['decimals'], '.', "\u{2019}") . ($kpiMap[$key]['unit'] ? ' ' . e($kpiMap[$key]['unit']) : '');
}
?>

<!-- Print-only header (logo + titre) -->
<div class="enbref-print-header">
    <img src="<?= SITE_URL ?>/assets/img/logo_sill_2026.svg" alt="SILL SA" height="40">
    <div>
        <strong>En bref — Chiffres cl&eacute;s au 31.12.2025</strong><br>
        <span>SILL SA — Soci&eacute;t&eacute; Immobili&egrave;re Lausannoise pour le Logement</span>
    </div>
</div>

<!-- Page header -->
<section class="page-header">
    <div class="container">
        <nav class="breadcrumb"><a href="<?= SITE_URL ?>/">Accueil</a> / <a href="<?= SITE_URL ?>/contexte">Contexte</a> / En bref</nav>
        <h1>En bref</h1>
        <p class="page-chapeau">Fiche signal&eacute;tique — Chiffres cl&eacute;s SILL SA</p>
        <p class="page-update">Donn&eacute;es au 31 d&eacute;cembre 2025 — Comptes annuels audit&eacute;s</p>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════
     HERO KPIs — Gros chiffres en bandeau (pattern FIR / La Foncière)
     ═══════════════════════════════════════════════════════════════ -->
<section class="enbref-hero">
    <div class="container">
        <div class="enbref-hero-grid">
            <div class="enbref-hero-kpi">
                <span class="enbref-hero-value" data-count="<?= $nbImmeubles ?>">0</span>
                <span class="enbref-hero-label">d&eacute;veloppements</span>
            </div>
            <div class="enbref-hero-kpi">
                <span class="enbref-hero-value" data-count="<?= $totalLogements ?>">0</span>
                <span class="enbref-hero-label">logements &amp; lots</span>
            </div>
            <div class="enbref-hero-kpi">
                <span class="enbref-hero-value" data-count="<?= round($totalSurface) ?>"
                      data-suffix="m&sup2;">0</span>
                <span class="enbref-hero-label">surface locative</span>
            </div>
            <div class="enbref-hero-kpi enbref-hero-kpi--accent">
                <span class="enbref-hero-value" data-count="<?= round($pctLUP, 1) ?>"
                      data-decimals="1" data-suffix="%">0</span>
                <span class="enbref-hero-label">logements d'utilit&eacute; publique</span>
            </div>
            <div class="enbref-hero-kpi">
                <span class="enbref-hero-value" data-count="<?= round($totalLoyer / 1e6, 1) ?>"
                      data-decimals="1" data-suffix="M&nbsp;CHF">0</span>
                <span class="enbref-hero-label">&eacute;tat locatif net annuel</span>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════
     SECTION 1 : Informations générales + Portefeuille
     ═══════════════════════════════════════════════════════════════ -->
<section class="section-enbref">
    <div class="container">

        <div class="enbref-block">
            <h2 class="enbref-section-title">Informations g&eacute;n&eacute;rales</h2>
            <table class="enbref-table">
                <tbody>
                    <tr><td class="enbref-label">Raison sociale</td><td class="enbref-value">Soci&eacute;t&eacute; Immobili&egrave;re Lausannoise pour le Logement SA</td></tr>
                    <tr><td class="enbref-label">Forme juridique</td><td class="enbref-value">Soci&eacute;t&eacute; anonyme de droit priv&eacute;</td></tr>
                    <tr><td class="enbref-label">Si&egrave;ge</td><td class="enbref-value">Lausanne</td></tr>
                    <tr><td class="enbref-label">Ann&eacute;e de cr&eacute;ation</td><td class="enbref-value"><?= kvPublic($kpiMap, 'annee_creation') ?></td></tr>
                    <tr><td class="enbref-label">Capital social</td><td class="enbref-value"><?= kvPublic($kpiMap, 'capital_social') ?></td></tr>
                    <tr><td class="enbref-label">Actionnaire</td><td class="enbref-value">Ville de Lausanne (100&nbsp;%)</td></tr>
                    <tr><td class="enbref-label">Segment</td><td class="enbref-value">R&eacute;sidentiel — logements d'utilit&eacute; publique</td></tr>
                </tbody>
            </table>
        </div>

        <div class="enbref-block">
            <h2 class="enbref-section-title">Portefeuille immobilier</h2>
            <table class="enbref-table">
                <tbody>
                    <tr><td class="enbref-label">Nombre de d&eacute;veloppements</td><td class="enbref-value"><?= $nbImmeubles ?></td></tr>
                    <tr><td class="enbref-label">Nombre de logements et lots</td><td class="enbref-value"><?= number_format($totalLogements, 0, '.', "\u{2019}") ?></td></tr>
                    <tr><td class="enbref-label">Surface locative totale (SUP)</td><td class="enbref-value"><?= number_format($totalSurface, 0, '.', "\u{2019}") ?> m&sup2;</td></tr>
                    <tr><td class="enbref-label">&Eacute;tat locatif net annuel</td><td class="enbref-value"><?= number_format($totalLoyer / 1e6, 1, '.', "\u{2019}") ?>&nbsp;M&nbsp;CHF</td></tr>
                    <tr><td class="enbref-label">Loyer net moyen</td><td class="enbref-value"><?= $totalSurface > 0 ? number_format($totalLoyer / $totalSurface, 0, '.', "\u{2019}") : '—' ?>&nbsp;CHF/m&sup2;/an</td></tr>
                    <tr><td class="enbref-label">Part logements d'utilit&eacute; publique</td><td class="enbref-value enbref-value--accent"><?= number_format($pctLUP, 1) ?>&nbsp;%</td></tr>
                    <tr><td class="enbref-label">Taux de vacance</td><td class="enbref-value enbref-value--accent">&lt; 0.5&nbsp;%</td></tr>
                </tbody>
            </table>
        </div>

    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════
     SECTION 2 : Graphiques — Benchmark + Évolution (fond alterné)
     ═══════════════════════════════════════════════════════════════ -->
<section class="section-enbref section-enbref--alt">
    <div class="container">

        <div class="enbref-charts-grid">

            <!-- Benchmark SILL vs Marché lausannois -->
            <div class="enbref-chart-block">
                <h2 class="enbref-section-title">SILL SA vs March&eacute; lausannois</h2>
                <canvas id="chart-benchmark" height="260"></canvas>
                <p class="enbref-sources">Sources : comptes SILL SA 2025 — OFS / Comparis 2025 — OFLP Lausanne</p>
            </div>

            <!-- Évolution du parc -->
            <div class="enbref-chart-block">
                <h2 class="enbref-section-title">&Eacute;volution du parc</h2>
                <canvas id="chart-evolution" height="260"></canvas>
                <p class="enbref-sources">Nombre cumul&eacute; de logements &amp; lots livr&eacute;s</p>
            </div>

        </div>

    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════
     SECTION 3 : Structure financière + Répartition
     ═══════════════════════════════════════════════════════════════ -->
<section class="section-enbref">
    <div class="container">

        <div class="enbref-block">
            <h2 class="enbref-section-title">Structure financi&egrave;re</h2>
            <table class="enbref-table">
                <tbody>
                    <tr><td class="enbref-label">Valeur v&eacute;nale (DCF)</td><td class="enbref-value"><?= kvPublic($kpiMap, 'valeur_dcf') ?></td></tr>
                    <tr><td class="enbref-label">Dette hypoth&eacute;caire</td><td class="enbref-value"><?= kvPublic($kpiMap, 'dette_hypo') ?></td></tr>
                    <tr><td class="enbref-label">Taux d'endettement / DCF</td><td class="enbref-value"><?= kvPublic($kpiMap, 'taux_avance_dcf') ?></td></tr>
                </tbody>
            </table>
        </div>

        <div class="enbref-block">
            <h2 class="enbref-section-title">R&eacute;partition par affectation</h2>
            <p class="enbref-sources" style="margin-bottom:12px;">Part du loyer annuel net — directives AMAS</p>

            <div class="enbref-repart-layout">
                <!-- Tableau -->
                <div class="enbref-repart-table">
                    <table class="enbref-table enbref-table--repartition">
                        <thead>
                            <tr>
                                <th>Affectation</th>
                                <th>Logements</th>
                                <th>Surface</th>
                                <th>Part</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($repart as $r):
                            $part = $totalLoyer > 0 ? (float) $r['loyer_annuel_net'] / $totalLoyer * 100 : 0;
                        ?>
                            <tr>
                                <td><strong><?= e($r['affectation']) ?></strong></td>
                                <td><?= number_format((int) $r['nb_logements'], 0, '.', "\u{2019}") ?></td>
                                <td><?= number_format((float) $r['surface_m2'], 0, '.', "\u{2019}") ?></td>
                                <td><?= number_format($part, 1) ?>&nbsp;%</td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td><strong>Total</strong></td>
                                <td><strong><?= number_format($totalLogements, 0, '.', "\u{2019}") ?></strong></td>
                                <td><strong><?= number_format($totalSurface, 0, '.', "\u{2019}") ?></strong></td>
                                <td><strong>100&nbsp;%</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Doughnut -->
                <div class="enbref-repart-chart">
                    <canvas id="chart-repartition" height="220"></canvas>
                </div>
            </div>
        </div>

    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════
     SECTION 4 : ESG + Sources + Disclaimer (fond alterné)
     ═══════════════════════════════════════════════════════════════ -->
<section class="section-enbref section-enbref--alt">
    <div class="container">

        <div class="enbref-block">
            <h2 class="enbref-section-title">Performance &eacute;nerg&eacute;tique &amp; ESG</h2>
            <table class="enbref-table">
                <tbody>
                    <tr><td class="enbref-label">Indice de d&eacute;pense de chaleur (IDC)</td><td class="enbref-value"><?= kvPublic($kpiMap, 'sill_idc') ?></td></tr>
                    <tr><td class="enbref-label">&Eacute;missions CO&#8322; scope 1+2</td><td class="enbref-value"><?= kvPublic($kpiMap, 'sill_co2') ?></td></tr>
                    <tr><td class="enbref-label">Consommation &eacute;nerg&eacute;tique moyenne</td><td class="enbref-value"><?= kvPublic($kpiMap, 'conso_energie_m2') ?></td></tr>
                    <tr><td class="enbref-label">Rapport de surveillance</td><td class="enbref-value">Signa-Terre SA / PwC (ISAE 3000)</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Sources -->
        <div class="enbref-sources-block">
            <strong>Sources et m&eacute;thodologie</strong><br>
            Rapport annuel 2025 — &Eacute;tats locatifs au 31.12.2025 — Rapport Signa-Terre / PwC 2024<br>
            R&eacute;partition par affectation : proportion du loyer annuel net (directives AMAS)<br>
            Benchmark march&eacute; : OFS, Comparis, OFLP Lausanne (donn&eacute;es 2025)
        </div>

        <!-- Disclaimer (en bas, pas en haut — pattern standard fonds) -->
        <div class="enbref-disclaimer">
            <p>Les informations pr&eacute;sent&eacute;es sur cette page ont un caract&egrave;re purement indicatif et informatif. Elles ne constituent en aucun cas une offre, une sollicitation ou une recommandation en mati&egrave;re de placement. SILL&nbsp;SA est une soci&eacute;t&eacute; anonyme d&eacute;tenue &agrave; 100&nbsp;% par la Ville de Lausanne dont les titres ne sont pas cot&eacute;s en bourse. La soci&eacute;t&eacute; d&eacute;cline toute responsabilit&eacute; quant &agrave; l'exactitude, l'exhaustivit&eacute; ou l'actualit&eacute; des donn&eacute;es publi&eacute;es. Les chiffres sont issus des comptes annuels audit&eacute;s et des &eacute;tats locatifs au 31&nbsp;d&eacute;cembre&nbsp;2025. Pour toute information compl&eacute;mentaire, veuillez vous r&eacute;f&eacute;rer aux <a href="<?= SITE_URL ?>/publications">Publications</a>.</p>
        </div>

        <!-- Actions -->
        <div class="enbref-actions">
            <button onclick="window.print()" class="btn-print">Exporter en PDF</button>
            <a href="<?= SITE_URL ?>/publications" class="btn-print">Rapports annuels</a>
        </div>

    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════
     Charts — Chart.js (même pattern que /contexte)
     ═══════════════════════════════════════════════════════════════ -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const swiss = (n) => n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, "\u2019");

    // ── Benchmark SILL vs Marché ──
    const ctxBench = document.getElementById('chart-benchmark');
    if (ctxBench) {
        new Chart(ctxBench, {
            type: 'bar',
            data: {
                labels: [
                    'Loyer net\nCHF/m²/an',
                    'Taux de\nvacance %',
                    'Logements\nd\'utilité publique %'
                ],
                datasets: [
                    {
                        label: 'SILL SA',
                        data: [<?= round($loyerMoyenSILL) ?>, 0.5, <?= round($pctLUP, 1) ?>],
                        backgroundColor: '#FF0000',
                        borderWidth: 0,
                        barPercentage: 0.6
                    },
                    {
                        label: 'Marché lausannois',
                        data: [<?= round($loyerMoyenMarche) ?>, 0.4, 25],
                        backgroundColor: '#E0E0E0',
                        borderWidth: 0,
                        barPercentage: 0.6
                    }
                ]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: { family: "'Helvetica Neue', Helvetica, Arial, sans-serif", size: 11 },
                            usePointStyle: true,
                            pointStyle: 'rect',
                            padding: 16
                        }
                    },
                    tooltip: {
                        backgroundColor: '#1A1A1A',
                        titleFont: { family: "'Helvetica Neue', Helvetica, Arial, sans-serif", size: 12 },
                        bodyFont: { family: "'Helvetica Neue', Helvetica, Arial, sans-serif", size: 12 }
                    }
                },
                scales: {
                    x: {
                        grid: { color: 'rgba(0,0,0,0.06)', drawBorder: false },
                        ticks: {
                            font: { family: "'Helvetica Neue', Helvetica, Arial, sans-serif", size: 11 }
                        }
                    },
                    y: {
                        grid: { display: false },
                        ticks: {
                            font: { family: "'Helvetica Neue', Helvetica, Arial, sans-serif", size: 11 },
                            autoSkip: false
                        }
                    }
                }
            }
        });
    }

    // ── Évolution du parc (bar chart cumulé) ──
    const ctxEvol = document.getElementById('chart-evolution');
    if (ctxEvol) {
        const parcData = <?= json_encode($parcData, JSON_NUMERIC_CHECK) ?>;
        const years = Object.keys(parcData);
        const values = Object.values(parcData);

        new Chart(ctxEvol, {
            type: 'bar',
            data: {
                labels: years,
                datasets: [{
                    data: values,
                    backgroundColor: '#FF0000',
                    borderWidth: 0,
                    barPercentage: 0.7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1A1A1A',
                        callbacks: {
                            label: (ctx) => swiss(ctx.raw) + ' logements'
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            font: { family: "'Helvetica Neue', Helvetica, Arial, sans-serif", size: 10 }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.06)', drawBorder: false },
                        ticks: {
                            font: { family: "'Helvetica Neue', Helvetica, Arial, sans-serif", size: 11 },
                            callback: (v) => swiss(v)
                        }
                    }
                }
            }
        });
    }

    // ── Doughnut répartition ──
    const ctxDonut = document.getElementById('chart-repartition');
    if (ctxDonut) {
        new Chart(ctxDonut, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($doughnutLabels) ?>,
                datasets: [{
                    data: <?= json_encode($doughnutValues) ?>,
                    backgroundColor: ['#FF0000', '#CC0000', '#990000', '#FFD700', '#0047BB', '#999999'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '55%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: { family: "'Helvetica Neue', Helvetica, Arial, sans-serif", size: 10 },
                            usePointStyle: true,
                            pointStyle: 'rect',
                            padding: 10
                        }
                    },
                    tooltip: {
                        backgroundColor: '#1A1A1A',
                        callbacks: {
                            label: (ctx) => ctx.label + ' : ' + ctx.raw + ' %'
                        }
                    }
                }
            }
        });
    }

    // ── Hero KPI count-up animation ──
    const heroValues = document.querySelectorAll('.enbref-hero-value[data-count]');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            const el = entry.target;
            const target = parseFloat(el.dataset.count);
            const decimals = parseInt(el.dataset.decimals || '0');
            const suffix = el.dataset.suffix || '';
            const duration = 1200;
            const start = performance.now();

            function animate(now) {
                const elapsed = now - start;
                const progress = Math.min(elapsed / duration, 1);
                const eased = 1 - Math.pow(1 - progress, 3);
                const current = target * eased;
                const formatted = decimals > 0
                    ? current.toFixed(decimals)
                    : swiss(Math.round(current));
                el.innerHTML = formatted + (suffix ? '&nbsp;' + suffix : '');
                if (progress < 1) requestAnimationFrame(animate);
            }
            requestAnimationFrame(animate);
            observer.unobserve(el);
        });
    }, { threshold: 0.3 });

    heroValues.forEach(el => observer.observe(el));
});
</script>
