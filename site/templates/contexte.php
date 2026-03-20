<?php
// templates/contexte.php — Page dédiée Contexte économique
// Données : sill_kpi categories 'marche', 'energie', 'sill'

$kpiMarche = query(
    "SELECT * FROM sill_kpi WHERE category IN ('marche','energie') AND is_public = 1 ORDER BY sort_order"
);

$kpiSill = query(
    "SELECT * FROM sill_kpi WHERE category = 'sill' AND is_public = 1 ORDER BY sort_order"
);

// Répartition locative — depuis BDD (fusionné pour affichage graphique)
$repartRaw = query("SELECT * FROM sill_repartition_locative ORDER BY nb_logements DESC");
$repartChart = [
    'LLM'       => 0,
    'LLA'       => 0,  // inclut LLA-protégé
    'LM'        => 0,  // = LML
    'Etudiants' => 0,
    'Activite'  => 0,
];
foreach ($repartRaw as $r) {
    $a = $r['affectation'];
    if ($a === 'LLM')                          $repartChart['LLM']       += (int) $r['nb_logements'];
    elseif ($a === 'LLA' || $a === 'LLA - protégé') $repartChart['LLA'] += (int) $r['nb_logements'];
    elseif ($a === 'LML')                      $repartChart['LM']        += (int) $r['nb_logements'];
    elseif ($a === 'Etudiants')                $repartChart['Etudiants'] += (int) $r['nb_logements'];
    elseif ($a === 'Activité')                 $repartChart['Activite']  += (int) $r['nb_logements'];
}
$repartTotal = array_sum($repartChart);
$repartLUP   = $repartChart['LLM'] + $repartChart['LLA'];
$repartLUPpct = $repartTotal > 0 ? round($repartLUP / $repartTotal * 100) : 0;

// Regrouper marché par catégorie
$marcheItems = $energieItems = [];
foreach ($kpiMarche as $k) {
    if ($k['category'] === 'energie') {
        $energieItems[] = $k;
    } else {
        $marcheItems[] = $k;
    }
}

// Sous-groupes marché
$locatifKeys = ['vacance_lausanne', 'loyer_moyen_m2', 'hausse_loyers'];
$tauxKeys    = ['taux_reference', 'taux_directeur', 'inflation_ch'];

// Sous-groupes SILL
$sillSocialKeys  = ['sill_loyer_net', 'sill_logements_lup', 'sill_charges'];
$sillEnergieKeys = ['sill_idc', 'sill_co2'];
$sillPatriKeys   = ['sill_surface'];

// KPIs avec préfixe "+"
$haussePrefixKeys = ['hausse_loyers', 'gaz_sil', 'inflation_ch'];

// Helper: render a marche datum
function renderMarcheDatum($k, $haussePrefixKeys = []) {
    $fmt = kpiFormat($k['value_num'] ?? null);
    $prefix = in_array($k['kpi_key'] ?? '', $haussePrefixKeys) ? '+' : '';
    ?>
    <div class="marche-datum reveal">
        <span class="marche-value"
              <?php if ($k['value_num'] !== null): ?>
              data-count="<?= e($fmt['formatted']) ?>"
              <?php if ($fmt['decimals'] > 0): ?>data-decimals="<?= $fmt['decimals'] ?>"<?php endif; ?>
              <?php if ($prefix): ?>data-prefix="<?= $prefix ?>"<?php endif; ?>
              <?php endif; ?>
        ><?= ($k['value_num'] !== null) ? '0' : e($k['value_text'] ?? '') ?></span>
        <span class="marche-unit"><?= e($k['unit'] ?? '') ?></span>
        <span class="marche-label"><?= e($k['label'] ?? '') ?></span>
        <?php if (!empty($k['value_text']) && $k['value_num'] !== null): ?>
            <span class="marche-context"><?= e($k['value_text']) ?></span>
        <?php endif; ?>
    </div>
    <?php
}
?>

<!-- Page header -->
<section class="page-header">
    <div class="container">
        <nav class="breadcrumb"><a href="/">Accueil</a> / Contexte économique</nav>
        <h1>Contexte économique</h1>
        <p class="page-chapeau">Indicateurs clés du marché immobilier — Suisse romande</p>
        <p class="page-update">Données au 31 décembre 2025 — Mise à jour mars 2026</p>
    </div>
</section>

<!-- ════════════════════════════════════════════════════════════════
     SECTION 1 : Indicateurs conjoncturels — 3 colonnes
     ════════════════════════════════════════════════════════════════ -->
<section class="section-marche section-marche--page">
    <div class="container">
        <div class="marche-grid">
            <!-- Colonne : Marché locatif -->
            <div class="marche-col">
                <span class="marche-col-label">Marché locatif</span>
                <?php foreach ($marcheItems as $k):
                    if (in_array($k['kpi_key'] ?? '', $locatifKeys)):
                        renderMarcheDatum($k, $haussePrefixKeys);
                    endif;
                endforeach; ?>
            </div>

            <!-- Colonne : Taux et financement -->
            <div class="marche-col">
                <span class="marche-col-label">Taux et financement</span>
                <?php foreach ($marcheItems as $k):
                    if (in_array($k['kpi_key'] ?? '', $tauxKeys)):
                        renderMarcheDatum($k, $haussePrefixKeys);
                    endif;
                endforeach; ?>
            </div>

            <!-- Colonne : Énergie -->
            <div class="marche-col">
                <span class="marche-col-label">Énergie</span>
                <?php foreach ($energieItems as $k):
                    renderMarcheDatum($k, $haussePrefixKeys);
                endforeach; ?>
            </div>
        </div>
        <p class="marche-sources">Sources : Comparis, BNS, OFL, Services industriels de Lausanne (SiL)</p>
    </div>
</section>

<!-- ════════════════════════════════════════════════════════════════
     SECTION 1b : Graphiques d'évolution — indices de référence
     ════════════════════════════════════════════════════════════════ -->
<section class="section-charts">
    <div class="container">
        <h2 class="charts-section-title">Évolution des indices de référence</h2>
        <div class="charts-grid">
            <div class="chart-card reveal">
                <h3 class="chart-title">Taux hypothécaire de référence</h3>
                <p class="chart-subtitle">OFL — depuis l'introduction en 2008</p>
                <div class="chart-canvas-wrap">
                    <canvas id="chartTauxRef"></canvas>
                </div>
            </div>
            <div class="chart-card reveal">
                <h3 class="chart-title">Indice des prix de la construction</h3>
                <p class="chart-subtitle">OFS — Hochbau, base octobre 2020 = 100</p>
                <div class="chart-canvas-wrap">
                    <canvas id="chartCRB"></canvas>
                </div>
            </div>
        </div>
        <p class="marche-sources">Sources : Office fédéral du logement (OFL) — Office fédéral de la statistique (OFS)</p>
    </div>
</section>

<!-- ════════════════════════════════════════════════════════════════
     SECTION 2 : Positionnement SILL SA
     ════════════════════════════════════════════════════════════════ -->
<?php if ($kpiSill): ?>
<section class="section-sill-position">
    <div class="container">
        <div class="sill-position-header">
            <span class="marche-rule"></span>
            <h2>Positionnement SILL SA</h2>
            <p class="page-chapeau">Notre portefeuille face au marché — Données 31.12.2025 / Signa-Terre 2024</p>
        </div>

        <div class="marche-grid">
            <!-- Colonne : Social & loyers -->
            <div class="marche-col">
                <span class="marche-col-label">Social & loyers</span>
                <?php foreach ($kpiSill as $k):
                    if (in_array($k['kpi_key'] ?? '', $sillSocialKeys)):
                        renderMarcheDatum($k);
                    endif;
                endforeach; ?>
            </div>

            <!-- Colonne : Énergie & climat -->
            <div class="marche-col">
                <span class="marche-col-label">Énergie & climat</span>
                <?php foreach ($kpiSill as $k):
                    if (in_array($k['kpi_key'] ?? '', $sillEnergieKeys)):
                        renderMarcheDatum($k);
                    endif;
                endforeach; ?>
            </div>

            <!-- Colonne : Patrimoine -->
            <div class="marche-col">
                <span class="marche-col-label">Patrimoine</span>
                <?php foreach ($kpiSill as $k):
                    if (in_array($k['kpi_key'] ?? '', $sillPatriKeys)):
                        renderMarcheDatum($k);
                    endif;
                endforeach; ?>
            </div>
        </div>

        <!-- ──────────────────────────────────────────────────────────
             Répartition des types de loyers — LLM / LLA / LM
             Source : États locatifs lot par lot 2026
             ────────────────────────────────────────────────────────── -->
        <div class="loyer-types reveal">
            <h3 class="loyer-types-title">Répartition des types de loyers</h3>
            <p class="loyer-types-subtitle"><?= number_format($repartTotal - $repartChart['Activite'], 0, '.', "'") ?> logements + <?= $repartChart['Activite'] ?> lots d'activités — État locatif 2026</p>

            <!-- Camembert global + Barres par projet -->
            <div class="loyer-charts-grid">
                <div class="loyer-chart-donut">
                    <canvas id="chartLoyerDonut"></canvas>
                    <p class="loyer-types-note"><abbr title="Logements d'utilité publique">LUP</abbr> (<abbr title="Loyer libre modéré">LLM</abbr> + <abbr title="Loyer libre abordable">LLA</abbr>) : <?= number_format($repartLUP, 0, '.', "'") ?> logements — <?= $repartLUPpct ?> % du parc</p>
                </div>
                <div class="loyer-chart-bars">
                    <canvas id="chartLoyerProjets"></canvas>
                </div>
            </div>
        </div>

        <p class="marche-sources">Rapport de surveillance énergétique : Signa-Terre SA / PwC (ISAE 3000) — Données financières : RA 2025 — États locatifs 2026</p>
    </div>
</section>
<?php endif; ?>

<!-- Chart.js + graphiques marché -->
<script>
function initMarcheCharts() {
  if (typeof Chart === 'undefined') return;

  var fontHeading = "'Inter', sans-serif";
  var fontBody = "'Lato', sans-serif";
  var colorRed = '#FF0000';
  var colorDark = '#1A1A1A';
  var colorMuted = '#999999';
  var colorBorder = '#E0E0E0';

  var sharedScaleX = {
    border: { display: false },
    grid: { display: false },
    ticks: { font: { family: fontBody, size: 11 }, color: colorMuted }
  };
  var sharedScaleY = {
    border: { display: false },
    grid: { color: colorBorder, lineWidth: 1 },
    ticks: { font: { family: fontBody, size: 11 }, color: colorMuted }
  };

  /* ── Taux hypothécaire de référence ── */
  var tauxDates = ['09.2008','03.2009','09.2009','12.2010','06.2012','09.2013','06.2014','03.2015','06.2017','03.2020','06.2023','12.2023','03.2025','09.2025'];
  var tauxRates = [3.50, 3.25, 3.00, 2.75, 2.50, 2.25, 2.00, 1.75, 1.50, 1.25, 1.50, 1.75, 1.50, 1.25];

  new Chart(document.getElementById('chartTauxRef'), {
    type: 'line',
    data: {
      labels: tauxDates,
      datasets: [{
        label: 'Taux de référence',
        data: tauxRates,
        borderColor: colorRed,
        backgroundColor: 'rgba(255, 0, 0, 0.08)',
        borderWidth: 2,
        pointBackgroundColor: colorRed,
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        pointRadius: 3,
        pointHoverRadius: 5,
        stepped: 'before',
        fill: true
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      scales: {
        x: sharedScaleX,
        y: Object.assign({}, sharedScaleY, {
          min: 0,
          max: 4,
          ticks: {
            font: { family: fontBody, size: 11 },
            color: colorMuted,
            callback: function (v) { return v.toFixed(2) + ' %'; }
          }
        })
      },
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: colorDark,
          titleFont: { family: fontHeading, size: 13 },
          bodyFont: { family: fontBody, size: 12 },
          padding: 12,
          cornerRadius: 2,
          callbacks: {
            label: function (ctx) { return ctx.parsed.y.toFixed(2) + ' %'; }
          }
        }
      }
    }
  });

  /* ── Indice des prix de la construction (CRB) ── */
  var crbYears = ['2015','2016','2017','2018','2019','2020','2021','2022','2023','2024','2025'];
  var crbValues = [100.7, 99.7, 99.4, 100.0, 100.4, 100.0, 104.6, 113.2, 114.8, 115.3, 116.2];

  new Chart(document.getElementById('chartCRB'), {
    type: 'line',
    data: {
      labels: crbYears,
      datasets: [{
        label: 'Indice CRB',
        data: crbValues,
        borderColor: colorDark,
        backgroundColor: 'rgba(26, 26, 26, 0.06)',
        borderWidth: 2,
        pointBackgroundColor: colorDark,
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        pointRadius: 3,
        pointHoverRadius: 5,
        tension: 0.3,
        fill: true
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      scales: {
        x: sharedScaleX,
        y: Object.assign({}, sharedScaleY, {
          min: 95,
          max: 120,
          ticks: {
            font: { family: fontBody, size: 11 },
            color: colorMuted,
            callback: function (v) { return v.toFixed(1); }
          }
        })
      },
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: colorDark,
          titleFont: { family: fontHeading, size: 13 },
          bodyFont: { family: fontBody, size: 12 },
          padding: 12,
          cornerRadius: 2,
          callbacks: {
            label: function (ctx) { return 'Indice : ' + ctx.parsed.y.toFixed(1) + ' pts'; }
          }
        }
      }
    }
  });

  /* ══════════════════════════════════════════════════════════════
     SECTION 2 : Camembert global + Barres par projet
     ══════════════════════════════════════════════════════════════ */

  var colorLLM  = '#E8C547';      // jaune Swiss doux — LLM
  var colorLLA  = '#AAAAAA';      // gris moyen — LLA
  var colorLM   = '#555555';      // gris foncé — LM
  var colorEtud = '#D8D8D8';      // gris clair — Étudiants
  var colorAct  = '#1A1A1A';      // noir — Activités

  /* ── Camembert (donut) — répartition globale (données BDD) ── */
  var donutData = <?= json_encode(array_values($repartChart)) ?>;
  var donutTotal = <?= $repartTotal ?>;
  var donutCtx = document.getElementById('chartLoyerDonut');
  if (donutCtx) {
    new Chart(donutCtx, {
      type: 'doughnut',
      data: {
        labels: ['LLM — Loyer modéré', 'LLA — Loyer abordable', 'LM — Loyer libre', 'Étudiants', 'Activités'],
        datasets: [{
          data: donutData,
          backgroundColor: [colorLLM, colorLLA, colorLM, colorEtud, colorAct],
          borderWidth: 2,
          borderColor: '#fff',
          hoverOffset: 6
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        cutout: '55%',
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              font: { family: fontHeading, size: 11, weight: 500 },
              color: colorDark,
              padding: 12,
              usePointStyle: true,
              pointStyleWidth: 10
            }
          },
          tooltip: {
            backgroundColor: colorDark,
            titleFont: { family: fontHeading, size: 13 },
            bodyFont: { family: fontBody, size: 12 },
            padding: 12,
            cornerRadius: 2,
            callbacks: {
              label: function (ctx) {
                var v = ctx.parsed;
                var pct = ((v / donutTotal) * 100).toFixed(1);
                var unit = (ctx.dataIndex === 4) ? ' lots' : ' logements';
                return ctx.label + ' : ' + v + unit + ' (' + pct + ' %)';
              }
            }
          },
          datalabels: {
            color: function (ctx) {
              var idx = ctx.dataIndex;
              return (idx === 3 || idx === 1) ? colorDark : '#fff';
            },
            font: { family: fontHeading, size: 12, weight: 600 },
            formatter: function (value) {
              var pct = ((value / donutTotal) * 100).toFixed(0);
              return pct + '%';
            },
            display: function (ctx) {
              return ctx.dataset.data[ctx.dataIndex] > 20;
            }
          }
        }
      }
    });
  }

  /* ── Barres empilées horizontales — par projet ── */
  var barsCtx = document.getElementById('chartLoyerProjets');
  if (barsCtx) {
    var projets = [
      'PDL 51-53', 'PDL 47', 'Cerjat 2-4', 'En Cojonnex',
      'Falaises', 'Fiches-Nord 11', 'Fiches-Nord 8/9',
      'Sallaz 4-5', 'Bonne-Esp.', 'Prairie 5a-5c'
    ];
    // Force container height: 40px per bar + 80px for axis/legend
    var barsWrap = barsCtx.parentElement;
    barsWrap.style.height = (projets.length * 40 + 80) + 'px';
    new Chart(barsCtx, {
      type: 'bar',
      data: {
        labels: projets,
        datasets: [
          { label: 'LLM',       data: [69,33,21, 0,27,88,47, 0,18, 0], backgroundColor: colorLLM },
          { label: 'LLA',       data: [35, 0,29, 0,36,53,52,29,19,52], backgroundColor: colorLLA },
          { label: 'LM',        data: [ 0, 0,11, 0,18,29, 0, 0, 0, 0], backgroundColor: colorLM },
          { label: 'Étudiants', data: [ 0, 0, 0,102,24,15, 0, 0, 0, 0], backgroundColor: colorEtud },
          { label: 'Activités', data: [ 8, 0, 0, 0, 7, 2, 1, 3, 0, 6], backgroundColor: colorAct }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        layout: { padding: { left: 0, right: 8 } },
        scales: {
          x: {
            stacked: true,
            border: { display: false },
            grid: { color: colorBorder, lineWidth: 1 },
            ticks: {
              font: { family: fontBody, size: 11 },
              color: colorMuted
            }
          },
          y: {
            stacked: true,
            border: { display: false },
            grid: { display: false },
            ticks: {
              font: { family: fontHeading, size: 11, weight: 500 },
              color: colorDark
            }
          }
        },
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              font: { family: fontHeading, size: 11, weight: 500 },
              color: colorDark,
              padding: 12,
              usePointStyle: true,
              pointStyleWidth: 10
            }
          },
          tooltip: {
            backgroundColor: colorDark,
            titleFont: { family: fontHeading, size: 13 },
            bodyFont: { family: fontBody, size: 12 },
            padding: 12,
            cornerRadius: 2,
            callbacks: {
              label: function (ctx) {
                if (ctx.parsed.x === 0) return null;
                var unit = (ctx.dataset.label === 'Activités') ? ' lots' : ' logements';
                return ctx.dataset.label + ' : ' + ctx.parsed.x + unit;
              }
            }
          },
          datalabels: {
            color: function (ctx) {
              var dsIndex = ctx.datasetIndex;
              return (dsIndex === 3 || dsIndex === 1) ? colorDark : '#fff';
            },
            font: { family: fontHeading, size: 10, weight: 600 },
            formatter: function (value) {
              return value > 0 ? value : '';
            },
            display: function (ctx) {
              return ctx.dataset.data[ctx.dataIndex] >= 15;
            }
          }
        }
      }
    });
  }
}

(function loadChartJS() {
  var src = 'https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js';
  var dlSrc = 'https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2/dist/chartjs-plugin-datalabels.min.js';

  function loadDatalabels() {
    var dl = document.createElement('script');
    dl.src = dlSrc;
    dl.onload = function () {
      Chart.register(ChartDataLabels);
      initMarcheCharts();
    };
    dl.onerror = function () { initMarcheCharts(); };
    document.head.appendChild(dl);
  }

  var s = document.createElement('script');
  s.src = src;
  s.onload = loadDatalabels;
  s.onerror = function () {
    var fb = document.createElement('script');
    fb.src = 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.7/chart.umd.min.js';
    fb.onload = loadDatalabels;
    fb.onerror = function () {};
    document.head.appendChild(fb);
  };
  document.head.appendChild(s);
})();
</script>
