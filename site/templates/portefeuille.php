<?php
// templates/portefeuille.php — Portefeuille immobilier with interactive SVG map

// ─── Data query ────────────────────────────────────────────────────
$immeubles = query(
    'SELECT i.*, m.filepath
     FROM sill_immeubles i
     LEFT JOIN sill_medias m ON i.image_id = m.id
     WHERE i.is_active = 1
     ORDER BY i.sort_order'
);

// ─── Position map: slug => [x, y] on the 800x600 SVG viewBox ─────
$positions = [
    'bonne-esperance-32'                        => ['x' => 520, 'y' => 340],
    'chemin-de-la-prairie-5a-et-5c'             => ['x' => 250, 'y' => 380],
    'place-de-la-sallaz-4-et-5'                 => ['x' => 480, 'y' => 250],
    'fiches-nord-lots-8-9'                      => ['x' => 420, 'y' => 150],
    'fiches-nord-lot-11'                        => ['x' => 450, 'y' => 170],
    'falaises'                                  => ['x' => 460, 'y' => 270],
    'les-fiches-nord'                           => ['x' => 400, 'y' => 130],
    'rue-elisabeth-jeanne-de-cerjat-2-4'        => ['x' => 380, 'y' => 180],
    'route-des-plaines-du-loup-51a-51b-et-53'  => ['x' => 360, 'y' => 200],
    'route-des-plaines-du-loup-47a-47b'         => ['x' => 340, 'y' => 190],
    'encogenet-chalet-a-gobet'                   => ['x' => 720, 'y' => 150],
];
?>

<!-- ════════════════════════════════════════════════════════════════
     PAGE HEADER
     ════════════════════════════════════════════════════════════════ -->
<section class="page-header">
  <div class="container">
    <h1>Portefeuille immobilier</h1>
    <p class="chapeau">Notre patrimoine de <?= count($immeubles) ?> immeubles a Lausanne</p>
  </div>
</section>

<!-- ════════════════════════════════════════════════════════════════
     DESKTOP: Interactive SVG Map + Info Panel
     ════════════════════════════════════════════════════════════════ -->
<section class="section-portefeuille reveal">
  <div class="container">

    <div class="portefeuille-layout">
      <div class="carte-container">
        <div class="carte-wrapper" style="position: relative;">

          <!-- Inline SVG map of Lausanne -->
          <svg viewBox="0 0 800 600" class="carte-lausanne" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Carte interactive du portefeuille immobilier SILL SA a Lausanne">
            <defs>
              <style>
                .carte-lac { fill: #E8F4F8; }
                .carte-route { stroke: #DDD; stroke-width: 1; fill: none; }
                .carte-route-major { stroke: #CCC; stroke-width: 1.5; fill: none; }
                .carte-rail { stroke: #BBB; stroke-width: 1; fill: none; stroke-dasharray: 6,3; }
                .carte-metro { stroke-width: 2.5; fill: none; stroke-linecap: round; }
                .carte-metro-m1 { stroke: #009FE3; }
                .carte-metro-m2 { stroke: #E2001A; }
                .carte-label { font-family: 'Inter', 'Helvetica Neue', sans-serif; font-size: 10px; fill: #AAA; letter-spacing: 0.05em; text-transform: uppercase; }
                .carte-label-lac { font-family: 'Inter', 'Helvetica Neue', sans-serif; font-size: 12px; fill: #A8CEE0; font-style: italic; letter-spacing: 0.1em; }
                .carte-label-transport { font-family: 'Inter', 'Helvetica Neue', sans-serif; font-size: 8px; font-weight: 700; letter-spacing: 0.03em; }
                .carte-gare { fill: #666; }
                .carte-shoreline { stroke: #C8DFE8; stroke-width: 1; fill: none; }
              </style>
            </defs>

            <!-- Lake Leman -->
            <path class="carte-lac" d="
              M 0,510
              C 80,485 180,472 280,468
              C 380,464 480,463 580,468
              C 660,472 740,482 800,490
              L 800,600 L 0,600 Z
            "/>
            <path class="carte-shoreline" d="
              M 0,510 C 80,485 180,472 280,468 C 380,464 480,463 580,468 C 660,472 740,482 800,490
            "/>

            <!-- Major routes (simplified street grid) -->
            <path class="carte-route-major" d="M 100,350 C 200,340 300,335 400,340 C 500,345 600,360 700,380"/>
            <path class="carte-route-major" d="M 380,100 C 385,200 390,300 395,400 C 398,440 400,460 402,468"/>
            <path class="carte-route" d="M 200,300 C 280,310 340,330 380,340"/>
            <path class="carte-route" d="M 400,340 C 440,320 470,290 500,260"/>
            <path class="carte-route" d="M 350,200 C 360,250 370,290 385,330"/>
            <path class="carte-route" d="M 430,170 C 440,220 450,270 460,310"/>
            <path class="carte-route" d="M 300,380 C 330,400 360,420 390,440"/>

            <!-- Railway line (gare) -->
            <path class="carte-rail" d="M 0,370 C 100,355 200,345 300,340 C 370,337 420,340 500,350 C 580,360 660,375 800,400"/>

            <!-- Gare de Lausanne -->
            <rect x="388" y="330" width="24" height="14" rx="2" class="carte-gare"/>
            <text x="400" y="340" text-anchor="middle" fill="#FFF" style="font-family: Inter, sans-serif; font-size: 8px; font-weight: 700;">CFF</text>
            <text class="carte-label" x="418" y="340" fill="#888">Gare</text>

            <!-- Metro M1 (TSOL: Flon → Renens) -->
            <path class="carte-metro carte-metro-m1" d="M 370,345 C 340,355 300,368 260,378 C 220,388 180,395 140,400"/>
            <rect x="358" y="350" width="18" height="11" rx="2" fill="#009FE3"/>
            <text x="367" y="358" text-anchor="middle" class="carte-label-transport" fill="#FFF">M1</text>

            <!-- Metro M2 (Ouchy → Croisettes) -->
            <path class="carte-metro carte-metro-m2" d="M 400,480 C 398,450 395,420 390,390 C 385,360 380,330 375,300 C 370,270 365,230 360,190 C 355,160 350,130 345,110"/>
            <rect x="345" y="270" width="18" height="11" rx="2" fill="#E2001A"/>
            <text x="354" y="278" text-anchor="middle" class="carte-label-transport" fill="#FFF">M2</text>

            <!-- Route to Chalet-a-Gobet (east) -->
            <path class="carte-route" d="M 500,260 C 560,230 620,200 700,160"/>

            <!-- District labels -->
            <text class="carte-label" x="355" y="365">Centre</text>
            <text class="carte-label" x="330" y="170">Plaines-du-Loup</text>
            <text class="carte-label" x="475" y="255">Sallaz</text>
            <text class="carte-label" x="200" y="395">Malley</text>
            <text class="carte-label" x="510" y="340">Faverges</text>
            <text class="carte-label" x="370" y="120">Fiches-Nord</text>
            <text class="carte-label" x="520" y="310">Bonne-Esp.</text>
            <text class="carte-label" x="700" y="140">Chalet-a-Gobet</text>

            <!-- Lake label -->
            <text class="carte-label-lac" x="380" y="540" text-anchor="middle">Lac Leman</text>
          </svg>

          <!-- Building points (absolutely positioned over the SVG) -->
          <?php foreach ($immeubles as $im): ?>
            <?php $pos = $positions[$im['slug']] ?? ['x' => 400, 'y' => 300]; ?>
            <button class="map-point"
                    data-slug="<?= e($im['slug']) ?>"
                    data-label="<?= e($im['nom']) ?> — <?= (int)$im['nb_logements'] ?> logements"
                    style="left: <?= round($pos['x'] / 800 * 100, 2) ?>%; top: <?= round($pos['y'] / 600 * 100, 2) ?>%;"
                    aria-label="<?= e($im['nom']) ?>">
              <span class="point-circle"><span class="point-inner"></span></span>
            </button>
          <?php endforeach; ?>

          <!-- Tooltip (shown on hover via main.js) -->
          <div class="map-tooltip">
            <span class="tooltip-text"></span>
          </div>

        </div><!-- /.carte-wrapper -->
      </div><!-- /.carte-container -->

      <!-- Side panel for AJAX-loaded building detail -->
      <aside class="map-info-panel">
        <p class="panel-placeholder">Cliquez sur un immeuble pour voir ses details</p>
      </aside>

    </div><!-- /.portefeuille-layout -->

    <!-- ════════════════════════════════════════════════════════════
         MOBILE: Card grid fallback
         ════════════════════════════════════════════════════════════ -->
    <div class="portefeuille-grid-mobile">
      <?php foreach ($immeubles as $im): ?>
        <a href="<?= SITE_URL ?>/portefeuille/<?= e($im['slug']) ?>" class="immeuble-card">
          <?php if (!empty($im['filepath'])): ?>
            <?php $imgPath = str_replace('/wp-content/uploads/', '/uploads/', $im['filepath']); ?>
            <img src="<?= SITE_URL . e($imgPath) ?>"
                 alt="<?= e($im['nom']) ?>"
                 loading="lazy">
          <?php endif; ?>
          <div class="immeuble-card-info">
            <h4><?= e($im['nom']) ?></h4>
            <p><?= (int)$im['nb_logements'] ?> logements</p>
            <?php if (!empty($im['chapeau'])): ?>
              <p><?= e($im['chapeau']) ?></p>
            <?php endif; ?>
          </div>
        </a>
      <?php endforeach; ?>
    </div>

  </div><!-- /.container -->
</section>
