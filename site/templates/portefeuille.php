<?php
// templates/portefeuille.php — Portefeuille immobilier with interactive SVG map

// ─── Data query ────────────────────────────────────────────────────
$immeubles = query(
    'SELECT * FROM sill_immeubles WHERE is_active = 1
     ORDER BY sort_order'
);

// Group by quartier
$parQuartier = [];
foreach ($immeubles as $im) {
    $q = $im['quartier'] ?? 'Autre';
    $parQuartier[$q][] = $im;
}

// ─── Position map: slug => [x, y] on the 800x600 SVG viewBox ─────
// Only real buildings — no neighborhood entries
// Positions recalibrées par coordonnées GPS réelles
// Projection non-linéaire : nord comprimé pour inclure Cojonnex
// Ref: Gare CFF (46.517°N, 6.629°E) ≈ SVG(400, 340)
$positions = [
    'en-cojonnex'                                => ['x' => 600, 'y' => 30],
    'fiches-nord-lots-8-9'                      => ['x' => 475, 'y' => 185],
    'fiches-nord-lot-11'                        => ['x' => 500, 'y' => 177],
    'rue-elisabeth-jeanne-de-cerjat-2-4'        => ['x' => 310, 'y' => 198],
    'route-des-plaines-du-loup-51a-51b-et-53'  => ['x' => 325, 'y' => 206],
    'route-des-plaines-du-loup-47a-47b'         => ['x' => 340, 'y' => 210],
    'place-de-la-sallaz-4-et-5'                 => ['x' => 476, 'y' => 244],
    'falaises'                                  => ['x' => 455, 'y' => 256],
    'chemin-de-la-prairie-5a-et-5c'             => ['x' => 240, 'y' => 350],
    'bonne-esperance-32'                        => ['x' => 510, 'y' => 320],
    // Acquisitions par préemption (DDP) — actifs dès le 1er avril 2026
    'bethusy-86-88'                             => ['x' => 480, 'y' => 380],
    'jomini-10-12-14'                           => ['x' => 500, 'y' => 248],
    'egralets-1-3'                              => ['x' => 520, 'y' => 255],
];

// Quartier label positions on the SVG (near their buildings, offset)
$quartierLabels = [
    'En Cojonnex'         => ['x' => 620, 'y' => 25],
    'Les Fiches-Nord'     => ['x' => 515, 'y' => 172],
    'Les Plaines-du-Loup' => ['x' => 210, 'y' => 185],
    'La Sallaz'           => ['x' => 496, 'y' => 237],
    'Les Falaises'        => ['x' => 472, 'y' => 270],
    'Sous-Gare'           => ['x' => 310, 'y' => 400],
];
?>

<!-- ════════════════════════════════════════════════════════════════
     PAGE HEADER
     ════════════════════════════════════════════════════════════════ -->
<section class="page-header">
  <div class="container">
    <h1>Portefeuille immobilier</h1>
    <p class="page-chapeau">Notre patrimoine de <?= count($immeubles) ?> développements réalisés à Lausanne</p>
  </div>
</section>

<!-- ════════════════════════════════════════════════════════════════
     DESKTOP: Interactive SVG Map + Info Panel
     ════════════════════════════════════════════════════════════════ -->
<section class="section-portefeuille">
  <div class="container">

    <div class="portefeuille-layout">
      <div class="carte-container">
        <div class="carte-wrapper" style="position: relative;">

          <!-- Inline SVG map of Lausanne -->
          <svg viewBox="0 0 800 600" class="carte-lausanne" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Carte interactive du portefeuille immobilier SILL SA a Lausanne">
            <defs>
              <style>
                .carte-lac { fill: #E8F4F8; }
                .carte-bg { stroke: #EFEFEF; stroke-width: 0.5; fill: none; }
                .carte-bg-secondary { stroke: #F3F3F3; stroke-width: 0.35; fill: none; }
                .carte-route { stroke: #E0E0E0; stroke-width: 0.75; fill: none; }
                .carte-route-major { stroke: #D0D0D0; stroke-width: 1.8; fill: none; }
                .carte-autoroute { stroke: #C8C8C8; stroke-width: 2.5; fill: none; }
                .carte-rail { stroke: #C8C8C8; stroke-width: 1; fill: none; stroke-dasharray: 6,3; }
                .carte-metro { stroke-width: 2.5; fill: none; stroke-linecap: round; }
                .carte-metro-m1 { stroke: #009FE3; }
                .carte-metro-m2 { stroke: #E2001A; }
                .carte-parc { fill: #F0F5ED; opacity: 0.6; }
                .carte-zone { fill: #F8F8F6; opacity: 0.4; }
                .carte-riviere { stroke: #D4E8F0; stroke-width: 1; fill: none; }
                .carte-label { font-family: 'Inter', 'Helvetica Neue', sans-serif; font-size: 11px; fill: #999; letter-spacing: 0.12em; text-transform: uppercase; font-weight: 500; }
                .carte-label-quartier { font-family: 'Helvetica Neue', Helvetica, sans-serif; font-size: 11px; fill: #CC0000; letter-spacing: 0.12em; text-transform: uppercase; font-weight: 400; }
                .carte-label-small { font-family: 'Inter', 'Helvetica Neue', sans-serif; font-size: 8px; fill: #C0C0C0; letter-spacing: 0.08em; text-transform: uppercase; }
                .carte-label-lac { font-family: 'Inter', 'Helvetica Neue', sans-serif; font-size: 14px; fill: #7FAFC4; font-style: italic; letter-spacing: 0.2em; }
                .carte-label-transport { font-family: 'Inter', 'Helvetica Neue', sans-serif; font-size: 8px; font-weight: 700; letter-spacing: 0.03em; }
                .carte-gare { fill: #666; }
                .carte-shoreline { stroke: #C8DFE8; stroke-width: 1.2; fill: none; }
              </style>
            </defs>

            <!-- ═══════════════════════════════════════════════
                 LAYER 1: Lac Leman
                 ═══════════════════════════════════════════════ -->
            <path class="carte-lac" d="
              M 0,510 C 80,485 180,472 280,468 C 380,464 480,463 580,468 C 660,472 740,482 800,490
              L 800,600 L 0,600 Z
            "/>
            <path class="carte-shoreline" d="
              M 0,510 C 80,485 180,472 280,468 C 380,464 480,463 580,468 C 660,472 740,482 800,490
            "/>
            <!-- Quai d'Ouchy — promenade du bord du lac -->
            <path class="carte-bg" d="M 200,478 C 280,472 360,468 440,468 C 500,468 540,470 600,475" stroke-width="0.6"/>

            <!-- ═══════════════════════════════════════════════
                 LAYER 2: Parcs et espaces verts
                 ═══════════════════════════════════════════════ -->
            <!-- Foret de Sauvabelin — entre Plaines-du-Loup et Fiches-Nord -->
            <ellipse class="carte-parc" cx="400" cy="185" rx="40" ry="25"/>
            <!-- Parc de Mon-Repos -->
            <ellipse class="carte-parc" cx="420" cy="295" rx="18" ry="12"/>
            <!-- Parc de Montbenon -->
            <ellipse class="carte-parc" cx="350" cy="365" rx="15" ry="10"/>
            <!-- Parc de Milan / Stade -->
            <ellipse class="carte-parc" cx="300" cy="310" rx="20" ry="14"/>
            <!-- Parc Bourget / Ouchy -->
            <ellipse class="carte-parc" cx="380" cy="455" rx="22" ry="10"/>
            <!-- Bois de la Vuachère (est, vers Pully) -->
            <ellipse class="carte-parc" cx="590" cy="380" rx="25" ry="18"/>
            <!-- Bois-Mermet -->
            <ellipse class="carte-parc" cx="280" cy="350" rx="12" ry="10"/>
            <!-- Foret du Jorat (nord-est) -->
            <path class="carte-parc" d="M 620,80 C 660,70 720,75 760,100 C 780,120 770,160 740,170 C 700,175 650,155 630,130 C 615,110 610,90 620,80 Z"/>

            <!-- ═══════════════════════════════════════════════
                 LAYER 3: Zones urbaines denses (texture subtile)
                 ═══════════════════════════════════════════════ -->
            <!-- Vieille ville / Cite -->
            <path class="carte-zone" d="M 340,290 C 355,270 395,260 430,272 C 450,285 455,315 440,340 C 420,355 375,360 350,345 C 332,330 328,305 340,290 Z" opacity="0.45"/>
            <!-- Flon / Centre commercial -->
            <rect class="carte-zone" x="335" y="330" width="80" height="50" rx="8" opacity="0.4"/>
            <!-- Zone Gare / Sous-Gare -->
            <ellipse class="carte-zone" cx="390" cy="400" rx="55" ry="35" opacity="0.3"/>

            <!-- ═══════════════════════════════════════════════
                 LAYER 4: Réseau de rues dense (plan de fond)
                 ═══════════════════════════════════════════════ -->

            <!-- === AXES PRINCIPAUX === -->
            <!-- Avenue d'Ouchy (N-S, axe principal vers le lac) -->
            <path class="carte-route-major" d="M 388,300 C 390,340 393,380 396,420 C 398,445 400,460 402,468"/>
            <!-- Avenue de la Gare -->
            <path class="carte-route-major" d="M 395,335 C 390,340 385,345 375,355"/>
            <!-- Grand-Pont -->
            <path class="carte-route-major" d="M 370,340 L 405,340"/>
            <!-- Route de Berne (vers Sallaz, est) -->
            <path class="carte-route-major" d="M 405,330 C 430,315 455,295 485,270 C 510,250 530,238 555,225"/>
            <!-- Avenue du Tribunal-Federal (vers ouest) -->
            <path class="carte-route-major" d="M 370,350 C 330,355 290,358 250,360 C 210,362 170,365 130,370"/>
            <!-- Avenue de Cour (le long du lac, ouest) -->
            <path class="carte-route-major" d="M 370,420 C 330,430 280,445 230,458 C 190,465 150,472 100,480"/>
            <!-- Route de Chailly / Avenue de Beaumont (nord-est) -->
            <path class="carte-route-major" d="M 410,310 C 430,290 455,270 480,255"/>
            <!-- Route des Plaines-du-Loup (nord) -->
            <path class="carte-route-major" d="M 385,300 C 380,260 375,220 370,180 C 367,155 365,135 362,110"/>
            <!-- Avenue de Provence (Fiches-Nord) -->
            <path class="carte-route-major" d="M 395,180 C 410,160 425,140 440,120"/>
            <!-- Avenue de Beaulieu -->
            <path class="carte-route-major" d="M 420,280 C 440,260 455,240 470,220"/>
            <!-- Route de la Sallaz vers Chalet-a-Gobet -->
            <path class="carte-route-major" d="M 500,260 C 540,240 580,210 630,175 C 665,155 700,140 740,130"/>
            <!-- Autoroute A9 (traversée est-ouest, au nord) -->
            <path class="carte-autoroute" d="M 0,285 C 80,275 160,268 240,270 C 300,273 340,280 380,290 C 420,300 450,305 500,300 C 560,292 620,278 700,260 C 740,252 770,248 800,245"/>

            <!-- === RUES SECONDAIRES — réseau dense === -->
            <!-- Quartier Sous-Gare / Ouchy -->
            <path class="carte-bg" d="M 360,400 C 370,395 385,390 400,395"/>
            <path class="carte-bg" d="M 365,415 C 378,410 390,408 405,412"/>
            <path class="carte-bg" d="M 370,430 C 382,425 395,422 410,428"/>
            <path class="carte-bg" d="M 375,380 L 380,440"/>
            <path class="carte-bg" d="M 395,375 L 400,445"/>
            <path class="carte-bg" d="M 415,380 L 418,435"/>
            <!-- Ouchy bord du lac -->
            <path class="carte-bg" d="M 340,455 C 360,450 380,448 400,450 C 420,452 440,456 460,462"/>

            <!-- Quartier Centre / Flon -->
            <path class="carte-bg" d="M 350,330 C 360,325 370,322 380,325"/>
            <path class="carte-bg" d="M 345,350 C 355,345 365,342 380,348"/>
            <path class="carte-bg" d="M 390,310 L 395,350"/>
            <path class="carte-bg" d="M 410,305 L 415,345"/>
            <path class="carte-bg" d="M 375,315 L 380,355"/>
            <path class="carte-bg" d="M 360,315 L 358,358"/>

            <!-- Vieille ville / Cite / Cathedrale -->
            <path class="carte-bg" d="M 375,300 C 385,295 395,292 405,298"/>
            <path class="carte-bg" d="M 380,308 C 388,303 398,301 408,306"/>
            <path class="carte-bg" d="M 385,292 L 388,318"/>
            <path class="carte-bg" d="M 398,290 L 400,315"/>
            <path class="carte-bg" d="M 370,295 L 372,322"/>

            <!-- Quartier Vallon / Pont Bessières -->
            <path class="carte-bg" d="M 408,310 C 415,315 422,322 425,330"/>
            <path class="carte-bg" d="M 418,300 C 425,308 430,318 432,328"/>

            <!-- Quartier Georgette / Tunnel -->
            <path class="carte-bg" d="M 340,320 C 350,315 360,312 370,316"/>
            <path class="carte-bg" d="M 335,335 C 345,330 355,328 365,332"/>
            <path class="carte-bg" d="M 345,310 L 348,345"/>
            <path class="carte-bg" d="M 330,315 L 332,348"/>

            <!-- Quartier Montbenon / Tribunal -->
            <path class="carte-bg" d="M 320,355 C 330,350 340,348 350,352"/>
            <path class="carte-bg" d="M 310,365 C 325,360 338,358 348,362"/>
            <path class="carte-bg" d="M 335,348 L 338,378"/>
            <path class="carte-bg" d="M 320,350 L 322,375"/>

            <!-- Quartier Malley / ouest -->
            <path class="carte-bg" d="M 180,370 C 200,365 220,362 240,366"/>
            <path class="carte-bg" d="M 170,385 C 195,380 215,376 240,380"/>
            <path class="carte-bg" d="M 190,355 L 195,400"/>
            <path class="carte-bg" d="M 215,352 L 218,395"/>
            <path class="carte-bg" d="M 240,350 L 242,392"/>
            <path class="carte-bg" d="M 160,375 C 175,370 190,368 210,372"/>
            <path class="carte-bg" d="M 155,395 C 175,390 195,388 218,392"/>

            <!-- Quartier Chailly / Faverges / Bonne-Esperance -->
            <path class="carte-bg" d="M 480,310 C 495,305 510,302 525,308"/>
            <path class="carte-bg" d="M 475,325 C 492,320 508,318 528,322"/>
            <path class="carte-bg" d="M 490,295 L 495,340"/>
            <path class="carte-bg" d="M 510,292 L 515,338"/>
            <path class="carte-bg" d="M 530,300 L 533,342"/>
            <path class="carte-bg" d="M 470,340 C 488,335 505,332 525,338"/>
            <path class="carte-bg" d="M 530,285 C 545,290 555,300 560,315"/>
            <path class="carte-bg" d="M 545,275 C 558,282 565,295 568,310"/>
            <path class="carte-bg" d="M 500,350 C 515,348 530,350 545,358"/>

            <!-- Quartier Sallaz -->
            <path class="carte-bg" d="M 460,250 C 472,245 485,242 498,248"/>
            <path class="carte-bg" d="M 455,265 C 468,260 482,258 496,262"/>
            <path class="carte-bg" d="M 468,238 L 472,278"/>
            <path class="carte-bg" d="M 485,235 L 488,275"/>
            <path class="carte-bg" d="M 500,240 C 505,255 508,268 510,280"/>

            <!-- Quartier Plaines-du-Loup / Fiches-Nord -->
            <path class="carte-bg" d="M 330,160 C 348,155 365,152 382,158"/>
            <path class="carte-bg" d="M 335,175 C 352,170 368,168 385,172"/>
            <path class="carte-bg" d="M 320,190 C 340,185 358,183 378,188"/>
            <path class="carte-bg" d="M 345,145 L 348,200"/>
            <path class="carte-bg" d="M 360,142 L 363,198"/>
            <path class="carte-bg" d="M 378,145 L 380,195"/>
            <path class="carte-bg" d="M 395,148 L 398,195"/>
            <!-- Fiches-Nord lots -->
            <path class="carte-bg" d="M 400,120 C 415,115 430,112 445,118"/>
            <path class="carte-bg" d="M 395,135 C 412,130 428,128 445,133"/>
            <path class="carte-bg" d="M 410,108 L 413,148"/>
            <path class="carte-bg" d="M 428,105 L 430,145"/>
            <path class="carte-bg" d="M 445,108 L 447,145"/>

            <!-- Quartier Beaulieu / Av. de Beaumont -->
            <path class="carte-bg" d="M 420,240 C 435,235 448,230 460,235"/>
            <path class="carte-bg" d="M 428,225 C 440,218 452,215 465,222"/>
            <path class="carte-bg" d="M 435,210 C 445,204 458,200 470,208"/>
            <path class="carte-bg" d="M 432,215 L 438,250"/>
            <path class="carte-bg" d="M 448,208 L 452,245"/>

            <!-- Quartier Montchoisi / Avenue de Cour -->
            <path class="carte-bg" d="M 320,400 C 335,395 350,393 365,398"/>
            <path class="carte-bg" d="M 300,415 C 318,410 335,408 352,413"/>
            <path class="carte-bg" d="M 280,430 C 300,425 318,422 338,427"/>
            <path class="carte-bg" d="M 310,390 L 314,435"/>
            <path class="carte-bg" d="M 335,388 L 338,432"/>

            <!-- Rues tertiaires — maillage fin quartier Pully/est -->
            <path class="carte-bg-secondary" d="M 560,340 C 575,338 590,340 605,348"/>
            <path class="carte-bg-secondary" d="M 555,360 C 572,356 590,355 608,362"/>
            <path class="carte-bg-secondary" d="M 565,330 L 568,375"/>
            <path class="carte-bg-secondary" d="M 585,328 L 588,372"/>
            <path class="carte-bg-secondary" d="M 545,380 C 560,378 575,380 590,388"/>

            <!-- Maillage fin quartier Prilly/Renens ouest -->
            <path class="carte-bg-secondary" d="M 100,360 C 120,355 140,354 160,358"/>
            <path class="carte-bg-secondary" d="M 95,380 C 118,375 138,374 158,378"/>
            <path class="carte-bg-secondary" d="M 120,348 L 123,395"/>
            <path class="carte-bg-secondary" d="M 142,346 L 145,393"/>

            <!-- Maillage Montelly / Bellevaux -->
            <path class="carte-bg-secondary" d="M 280,280 C 298,275 315,272 332,278"/>
            <path class="carte-bg-secondary" d="M 275,295 C 295,290 312,288 330,293"/>
            <path class="carte-bg-secondary" d="M 295,268 L 298,305"/>
            <path class="carte-bg-secondary" d="M 315,265 L 318,302"/>

            <!-- ═══════════════════════════════════════════════
                 LAYER 5: Rivières / Vallées
                 ═══════════════════════════════════════════════ -->
            <!-- Flon (rivière souterraine, vallée historique) -->
            <path class="carte-riviere" d="M 340,280 C 348,300 355,320 360,340 C 365,355 370,370 378,390" stroke-dasharray="4,3"/>
            <!-- Louve (rivière souterraine est) -->
            <path class="carte-riviere" d="M 430,260 C 425,280 418,305 412,330 C 408,350 405,370 400,395" stroke-dasharray="4,3"/>
            <!-- Vuachère (est, à ciel ouvert) -->
            <path class="carte-riviere" d="M 550,200 C 555,230 560,260 562,290 C 565,320 568,350 572,380" stroke-width="0.8"/>

            <!-- ═══════════════════════════════════════════════
                 LAYER 6: Railway
                 ═══════════════════════════════════════════════ -->
            <path class="carte-rail" d="M 0,370 C 100,355 200,345 300,340 C 370,337 420,340 500,350 C 580,360 660,375 800,400"/>

            <!-- Gare de Lausanne -->
            <rect x="370" y="328" width="60" height="16" rx="3" fill="#FF0000"/>
            <text x="400" y="339" text-anchor="middle" fill="#FFF" style="font-family: 'Helvetica Neue', Helvetica, sans-serif; font-size: 8px; font-weight: 700; letter-spacing: 0.05em;">Gare CFF</text>

            <!-- ═══════════════════════════════════════════════
                 LAYER 7: Metro
                 ═══════════════════════════════════════════════ -->
            <!-- Metro M1 (TSOL: Flon → Renens) -->
            <path class="carte-metro carte-metro-m1" d="M 370,345 C 340,355 300,368 260,378 C 220,388 180,395 140,400"/>
            <rect x="358" y="350" width="18" height="11" rx="3" fill="#009FE3"/>
            <text x="367" y="358" text-anchor="middle" class="carte-label-transport" fill="#FFF">M1</text>

            <!-- Metro M2 (Ouchy → Gare → Flon → Riponne → Bessières → Sallaz → CHUV → Croisettes) -->
            <path class="carte-metro carte-metro-m2" d="
              M 400,475
              C 399,455 398,435 397,415
              C 396,395 395,375 394,355
              L 390,340
              C 388,330 390,320 395,312
              C 402,302 415,292 430,280
              C 445,268 458,258 470,250
              C 478,242 482,230 480,218
              C 478,200 472,182 465,168
              C 458,155 452,145 448,135
            "/>
            <rect x="460" y="220" width="18" height="11" rx="3" fill="#E2001A"/>
            <text x="469" y="228" text-anchor="middle" class="carte-label-transport" fill="#FFF">M2</text>

            <!-- ═══════════════════════════════════════════════
                 LAYER 8: Repères urbains (très discrets)
                 ═══════════════════════════════════════════════ -->
            <text class="carte-label-small" x="385" y="305">Cathedrale</text>
            <text class="carte-label-small" x="370" y="455">Ouchy</text>
            <text class="carte-label-small" x="565" y="350">Pully</text>
            <text class="carte-label-small" x="70" y="390">Renens</text>
            <text class="carte-label-small" x="380" y="175">Sauvabelin</text>

            <!-- ═══════════════════════════════════════════════
                 LAYER 9: Labels — Quartiers SILL (rouge) + repères neutres
                 ═══════════════════════════════════════════════ -->
            <!-- Repères géographiques neutres -->
            <text class="carte-label" x="355" y="355">Centre</text>
            <text class="carte-label" x="130" y="388" style="fill:#777">Malley</text>

            <!-- Quartiers SILL SA -->
            <!-- Encart En Cojonnex (hors échelle — Chalet-à-Gobet) -->
            <rect x="570" y="10" width="180" height="50" rx="0" fill="none" stroke="#CC0000" stroke-width="0.5" stroke-dasharray="3,2"/>
            <text class="carte-label-quartier" x="580" y="30">En Cojonnex</text>
            <text class="carte-label-small" x="580" y="45" style="fill:#999">Chalet-à-Gobet</text>
            <line x1="570" y1="55" x2="530" y2="100" stroke="#CC0000" stroke-width="0.4" stroke-dasharray="2,2"/>

            <text class="carte-label-quartier" x="515" y="172">Les Fiches-Nord</text>
            <text class="carte-label-quartier" x="210" y="185">Les Plaines-du-Loup</text>
            <text class="carte-label-quartier" x="496" y="237">La Sallaz</text>
            <text class="carte-label-quartier" x="472" y="270">Les Falaises</text>
            <text class="carte-label-quartier" x="310" y="400">Sous-Gare</text>
            <text class="carte-label-quartier" x="500" y="395">Béthusy</text>

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
        <div class="panel-mosaic">
          <p class="panel-mosaic-title">Notre portefeuille</p>
          <div class="panel-mosaic-grid">
            <?php foreach ($immeubles as $im): ?>
                <button class="mosaic-thumb" data-slug="<?= e($im['slug']) ?>" title="<?= e($im['nom']) ?>">
                  <img src="<?= e(immeubleCoverUrl($im['slug'])) ?>" alt="<?= e($im['nom']) ?>" loading="lazy">
                </button>
            <?php endforeach; ?>
          </div>
          <p class="panel-mosaic-hint">Cliquez sur une vignette ou un point sur la carte</p>
        </div>
      </aside>

    </div><!-- /.portefeuille-layout -->

    <!-- ════════════════════════════════════════════════════════════
         MOBILE: Card grid fallback
         ════════════════════════════════════════════════════════════ -->
    <div class="portefeuille-grid-mobile">
      <?php foreach ($parQuartier as $quartier => $ims): ?>
        <div class="quartier-group">
          <h3 class="quartier-label"><?= e($quartier) ?></h3>
          <?php foreach ($ims as $im): ?>
            <a href="<?= SITE_URL ?>/portefeuille/<?= e($im['slug']) ?>" class="immeuble-card">
              <img src="<?= e(immeubleCoverUrl($im['slug'])) ?>" alt="<?= e($im['nom']) ?>" loading="lazy">
              <div class="immeuble-card-info">
                <h4><?= e($im['nom']) ?></h4>
                <p><?= (int)$im['nb_logements'] ?> logements</p>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </div>

  </div><!-- /.container -->
</section>
