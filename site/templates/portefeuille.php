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
                .carte-outline { stroke: #E0E0E0; stroke-width: 1; fill: none; }
                .carte-quartier { stroke: #EFEFEF; stroke-width: 0.5; fill: none; }
                .carte-route { stroke: #F0F0F0; stroke-width: 0.75; fill: none; }
                .carte-label { font-family: 'Inter', 'Helvetica Neue', sans-serif; font-size: 9px; fill: #CCCCCC; letter-spacing: 0.05em; text-transform: uppercase; }
                .carte-label-lac { font-family: 'Inter', 'Helvetica Neue', sans-serif; font-size: 11px; fill: #B8D8E8; font-style: italic; letter-spacing: 0.08em; }
              </style>
            </defs>

            <!-- Lake Leman -->
            <path class="carte-lac" d="
              M 0,520
              C 80,490 160,475 240,470
              C 320,465 400,462 480,465
              C 560,468 640,478 720,488
              L 800,495
              L 800,600
              L 0,600
              Z
            "/>

            <!-- Shoreline -->
            <path class="carte-quartier" d="
              M 0,520
              C 80,490 160,475 240,470
              C 320,465 400,462 480,465
              C 560,468 640,478 720,488
              L 800,495
            " stroke-width="0.75"/>

            <!-- City outline -->
            <path class="carte-outline" d="
              M 180,80
              C 200,70 260,55 320,60
              C 380,65 420,58 460,62
              C 520,68 570,75 600,90
              C 630,105 650,130 660,160
              C 675,200 680,250 670,300
              C 660,340 640,380 610,410
              C 580,440 540,455 480,462
              C 420,460 360,458 300,462
              C 240,468 200,470 160,460
              C 130,440 110,400 100,360
              C 90,310 95,260 105,210
              C 115,170 130,130 155,100
              C 165,88 175,82 180,80
              Z
            "/>

            <!-- District zones -->
            <ellipse cx="390" cy="350" rx="50" ry="35" fill="#F8F8F8" opacity="0.6"/>
            <ellipse cx="380" cy="170" rx="65" ry="45" fill="#F8F8F8" opacity="0.5"/>
            <ellipse cx="480" cy="260" rx="35" ry="25" fill="#F8F8F8" opacity="0.5"/>

            <!-- Major routes -->
            <line class="carte-route" x1="380" y1="300" x2="395" y2="462"/>
            <path class="carte-route" d="M 390,340 C 420,320 450,290 490,260"/>
            <path class="carte-route" d="M 370,350 C 330,365 290,375 250,385"/>
            <path class="carte-route" d="M 385,310 C 380,270 375,220 370,170"/>
            <path class="carte-route" d="M 350,340 L 430,340" stroke-dasharray="3,3"/>

            <!-- District labels -->
            <text class="carte-label" x="355" y="355">Centre</text>
            <text class="carte-label" x="348" y="160">Plaines-du-Loup</text>
            <text class="carte-label" x="465" y="250">Sallaz</text>
            <text class="carte-label" x="220" y="385">Malley</text>
            <text class="carte-label" x="505" y="335">Faverges</text>
            <text class="carte-label" x="380" y="125">Fiches-Nord</text>

            <!-- Lake label -->
            <text class="carte-label-lac" x="370" y="530">Lac Leman</text>
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
