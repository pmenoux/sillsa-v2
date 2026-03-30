<?php
// templates/quartiers.php — Les quartiers de développement SILL SA
// Données issues du Rapport d'activité 2024 et recherches publiques

// Immeubles SILL par quartier (pour photos et liens)
$immeubles = query(
    'SELECT slug, nom, quartier, nb_logements, annee_livraison, chapeau
     FROM sill_immeubles WHERE is_active = 1 ORDER BY quartier, sort_order'
);
$parQuartier = [];
foreach ($immeubles as $im) {
    $q = $im['quartier'] ?? 'Autre';
    $parQuartier[$q][] = $im;
}

// Données des quartiers — structurées à partir du RA 2024 et sources publiques
$quartiers = [
    // 01 — Fiches-Nord (2015–2019)
    [
        'id'    => 'fiches-nord',
        'nom'   => 'Les Fiches-Nord',
        'dbKey' => 'Les Fiches-Nord',
        'intro' => 'Le quartier des Fiches-Nord, situé à proximité de la station de métro M2 Fourmi, est un écoquartier de 5 hectares développé à partir de 2008. La charte urbanistique, signée par l\'ensemble des propriétaires en 2011, a été établie par Architram SA, lauréat du concours d\'urbanisme. Le quartier compte une dizaine de lots livrés entre 2015 et 2019.',
        'logements_sill'  => 316,
        'logements_total' => '670',
        'etape'           => 'Achevé (2015–2019)',
        'investisseurs' => [
            ['nom' => 'SILL SA',               'type' => 'Société de la Ville'],
            ['nom' => 'CPCL',                   'type' => 'Caisse de pension communale'],
            ['nom' => 'ECA Vaud',                'type' => 'Institutionnel'],
            ['nom' => 'FLCL',                   'type' => 'Fondation de la Ville'],
            ['nom' => 'Vaudoise Assurances',     'type' => 'Institutionnel'],
            ['nom' => 'Fonds de Prévoyance CA Indosuez', 'type' => 'Institutionnel'],
            ['nom' => 'Sofidim SA',              'type' => 'Privé'],
            ['nom' => 'Ville de Lausanne',       'type' => 'Collectivité publique (école)'],
        ],
        'architectes' => [
            ['bureau' => 'Architram SA',               'projet' => 'Urbanisme et charte du quartier'],
            ['bureau' => 'NB.ARCH',                    'projet' => 'SILL — Lots 8 & 9'],
            ['bureau' => 'Ferrari Architectes',         'projet' => 'SILL — Lot 11'],
            ['bureau' => 'Bonnard Woeffray',            'projet' => 'ECA Vaud — Lot 2'],
            ['bureau' => 'Richter Dahl Rocha (RDR)',    'projet' => 'CPCL — Lot 7 Sud + CA Indosuez — Lot 3'],
            ['bureau' => 'Bütikofer de Oliveira Vernay','projet' => 'CPCL — Lot 7 Nord'],
            ['bureau' => 'Züst Gübeli Gambetti',        'projet' => 'Vaudoise — Lot 4'],
            ['bureau' => 'Giovanoli Mozer',             'projet' => 'FLCL — Lot 6'],
        ],
    ],
    // 02 — En Cojonnex (2018–2020)
    [
        'id'    => 'en-cojonnex',
        'nom'   => 'En Cojonnex',
        'dbKey' => 'En Cojonnex',
        'intro' => 'Le quartier En Cojonnex se situe au Chalet-à-Gobet, à 900 mètres d\'altitude, en bordure de la forêt du Jorat et à proximité de l\'École hôtelière de Lausanne. Le Plan Partiel d\'Affectation, adopté en 2012, a permis la construction de logements en bois certifiés Minergie-P-Eco. Les bâtiments <strong>SILL</strong>, conçus par MPH Architectes et livrés en 2018, définissent des "clairières" servant d\'espaces de vie et de rencontre.',
        'logements_sill'  => 102,
        'logements_total' => '~200',
        'etape'           => 'Achevé (2018–2020)',
        'investisseurs' => [
            ['nom' => 'SILL SA',                 'type' => 'Société de la Ville'],
            ['nom' => 'SCHL',                     'type' => 'Coopérative'],
            ['nom' => 'Fondation du Denantou',     'type' => 'Fondation'],
        ],
        'architectes' => [
            ['bureau' => 'MPH Architectes',  'projet' => 'SILL — "Clairières" (concours SIA 142, 2014)'],
            ['bureau' => 'RDR architectes',  'projet' => 'SCHL / Fondation du Denantou'],
        ],
    ],
    // 03 — Les Falaises (2016–2020)
    [
        'id'    => 'falaises',
        'nom'   => 'Les Falaises',
        'dbKey' => 'Les Falaises',
        'intro' => 'Le projet des Falaises s\'inscrit sur un site exceptionnel, à la jonction de l\'avenue de la Sallaz et de la rue du Bugnon, surplombant la vallée du Flon. Issu d\'un concours public lancé en 2012 par la Ville de Lausanne avec la <strong>SILL</strong> et la SCILMO, le projet "CLIFF" de MPH Architectes a été lauréat. Réalisé entre 2016 et 2020, il a reçu le Prix Bilan de l\'Immobilier 2020.',
        'logements_sill'  => 94,
        'logements_total' => '194',
        'etape'           => 'Achevé (2016–2020)',
        'investisseurs' => [
            ['nom' => 'SILL SA',    'type' => 'Société de la Ville'],
            ['nom' => 'SCILMO',     'type' => 'Coopérative (fondée en 1903)'],
        ],
        'architectes' => [
            ['bureau' => 'MPH Architectes', 'projet' => 'Projet unitaire "CLIFF" — Bâtiments A, B et C'],
        ],
    ],
    // 04 — Les Plaines-du-Loup (2022–)
    [
        'id'    => 'plaines-du-loup',
        'nom'   => 'Les Plaines-du-Loup',
        'dbKey' => 'Les Plaines-du-Loup',
        'intro' => 'Le quartier des Plaines-du-Loup, situé au nord de Lausanne, constitue l\'un des projets d\'écoquartier les plus ambitieux de Suisse. Issu du programme Métamorphose lancé en 2007, ce nouveau morceau de ville de 30 hectares accueillera à terme environ 8\'000 habitants et 3\'000 emplois. Les premiers résidents se sont installés en juin 2022.',
        'logements_sill'  => 198,
        'logements_total' => '~1\'100',
        'etape'           => 'PA1 livré / PA2 en développement',
        'investisseurs' => [
            // PU A
            ['nom' => 'SILL SA',                'type' => 'Société de la Ville — PU A, B, D'],
            ['nom' => 'Cité Derrière',          'type' => 'Coopérative d\'habitants — PU A'],
            ['nom' => 'Swiss Life SA',           'type' => 'Institutionnel — PU A, B'],
            // PU B
            ['nom' => 'CODHA',                   'type' => 'Coopérative — PU B'],
            ['nom' => 'Retraites Populaires',    'type' => 'Institutionnel — PU B'],
            // PU C
            ['nom' => 'FLCL',                    'type' => 'Fondation de la Ville — PU C'],
            ['nom' => 'SCHL',                    'type' => 'Coopérative — PU C'],
            ['nom' => 'FPHL',                    'type' => 'Fondation Pro Habitat — PU C'],
            // PU D
            ['nom' => 'Coopérative Ecopolis',    'type' => 'Coopérative d\'habitants — PU D'],
            ['nom' => 'Coopérative C-Arts-Ouches','type' => 'Coopérative d\'habitants — PU D'],
            ['nom' => 'Fondation Bois-Gentil',   'type' => 'EMS — PU D'],
            ['nom' => 'Fondation de l\'Orme',    'type' => 'EMS — PU D'],
            ['nom' => 'Ville de Lausanne',       'type' => 'Service des écoles — PU D'],
            // PU E
            ['nom' => 'Logement Idéal',          'type' => 'Coopérative — PU E'],
            ['nom' => 'Jaguar Realestate SA',    'type' => 'Privé — PU E'],
            ['nom' => 'CIEPP',                   'type' => 'Institutionnel — PU E'],
            ['nom' => 'Coopérative Le Bled',     'type' => 'Coopérative d\'habitants — PU E'],
            ['nom' => 'Coopérative La Meute',    'type' => 'Coopérative d\'habitants — PU E'],
        ],
        'architectes' => [
            // PU A
            ['bureau' => 'Bunq architectes + J.-J. Borgeaud', 'projet' => 'PU A — Cité Derrière / SILL / Swiss Life (148 log.)'],
            // PU B
            ['bureau' => 'Pont12 + Oxalis',                    'projet' => 'PU B — forme urbaine + lot Swiss Life'],
            ['bureau' => 'Meier + associés',                   'projet' => 'PU B — lots SILL, CODHA, Retraites Pop. (Rte PDL 51a-53)'],
            // PU C
            ['bureau' => 'Nicolas de Courten',                 'projet' => 'PU C — FLCL / SCHL / FPHL (155 log.)'],
            // PU D
            ['bureau' => 'Bütikofer de Oliveira Vernay',       'projet' => 'PU D — SILL subventionnés (Rte PDL 47a-47b)'],
            ['bureau' => 'Costea Missonnier Fioroni',          'projet' => 'PU D — SILL PPE (Ch. des Bossons 44)'],
            ['bureau' => 'Aeby Perneger + Hüsler',            'projet' => 'PU D — école + EMS + concept d\'ensemble'],
            ['bureau' => 'atba architectes',                   'projet' => 'PU D — Coopérative Ecopolis'],
            ['bureau' => 'O. Rochat architectes',              'projet' => 'PU D — C-Arts-Ouches'],
            // PU E
            ['bureau' => 'cBmM architectes',                   'projet' => 'PU E — Logement Idéal'],
            ['bureau' => 'L-Architectes',                      'projet' => 'PU E — Jaguar Realestate'],
            ['bureau' => 'LRS Architectes',                    'projet' => 'PU E — CIEPP'],
            ['bureau' => 'Tribu architecture',                 'projet' => 'PU E — Coopérative Le Bled'],
            ['bureau' => 'Lx1 Architecture',                   'projet' => 'PU E — Coopérative La Meute'],
        ],
    ],
];

// Total logements SILL
$totalSill = 0;
foreach ($quartiers as $q) $totalSill += $q['logements_sill'];

// Images quartiers (extraites du RA 2024)
$quartierImages = [
    'plaines-du-loup' => SITE_URL . '/media/quartiers/plaines-du-loup.jpg',
    'fiches-nord'     => SITE_URL . '/media/quartiers/fiches-nord.jpg',
    'falaises'        => SITE_URL . '/media/quartiers/falaises.jpg',
    'en-cojonnex'     => SITE_URL . '/media/quartiers/en-cojonnex.jpg',
];
?>

<!-- Page header -->
<section class="page-header">
    <div class="container">
        <nav class="breadcrumb"><a href="<?= SITE_URL ?>">Accueil</a> / <a href="<?= SITE_URL ?>/portefeuille">Portefeuille</a> / Quartiers</nav>
        <h1>Nos quartiers</h1>
        <p class="page-chapeau">La <strong>SILL SA</strong> a développé <?= $totalSill ?> logements au sein de quatre quartiers lausannois, aux côtés de coopératives, fondations et investisseurs institutionnels.</p>

        <!-- Mini-carte statique — position des 4 quartiers -->
        <div class="quartiers-minicarte reveal">
            <svg viewBox="0 0 800 600" xmlns="http://www.w3.org/2000/svg" aria-label="Carte de Lausanne — Quartiers SILL SA">
                <style>
                    .mc-lac { fill: #E8F4F8; }
                    .mc-shore { stroke: #C4DDE6; stroke-width: 0.8; fill: none; }
                    .mc-parc { fill: #E8F0E4; opacity: 0.6; }
                    .mc-road { stroke: #E4E4E4; stroke-width: 1.2; fill: none; }
                    .mc-auto { stroke: #D0D0D0; stroke-width: 2; fill: none; }
                    .mc-rail { stroke: #D0D0D0; stroke-width: 0.8; fill: none; stroke-dasharray: 5,3; }
                    .mc-dot { fill: #FF0000; }
                    .mc-ring { fill: none; stroke: #FF0000; stroke-width: 1.5; opacity: 0.3; }
                    .mc-label { font-family: 'Helvetica Neue', Helvetica, sans-serif; font-size: 13px; fill: #1A1A1A; font-weight: 500; letter-spacing: 0.06em; }
                    .mc-repere { font-family: 'Helvetica Neue', Helvetica, sans-serif; font-size: 10px; fill: #999999; letter-spacing: 0.04em; }
                    .mc-lac-label { font-family: 'Helvetica Neue', Helvetica, sans-serif; font-size: 12px; fill: #7FAFC4; letter-spacing: 0.15em; font-weight: 300; }
                    .mc-link { cursor: pointer; }
                    .mc-link:hover .mc-dot { fill: #CC0000; }
                    .mc-link:hover .mc-ring { opacity: 0.6; }
                </style>

                <!-- Lac -->
                <path class="mc-lac" d="M 0,510 C 80,485 180,472 280,468 C 380,464 480,463 580,468 C 660,472 740,482 800,490 L 800,600 L 0,600 Z"/>
                <path class="mc-shore" d="M 0,510 C 80,485 180,472 280,468 C 380,464 480,463 580,468 C 660,472 740,482 800,490"/>

                <!-- Parcs principaux -->
                <ellipse class="mc-parc" cx="400" cy="185" rx="40" ry="25"/>
                <ellipse class="mc-parc" cx="650" cy="60" rx="80" ry="50"/>

                <!-- Routes principales -->
                <path class="mc-road" d="M 0,350 C 150,340 300,335 400,340 C 500,345 600,355 800,380"/>
                <path class="mc-road" d="M 400,200 C 410,260 420,320 430,380 C 440,420 445,450 450,480"/>
                <path class="mc-auto" d="M 0,285 C 160,268 300,273 400,285 C 500,295 620,278 800,250"/>

                <!-- Rail -->
                <path class="mc-rail" d="M 0,370 C 200,345 400,340 600,360 C 700,370 750,380 800,400"/>

                <!-- Métro M2 (Ouchy → Gare → Flon → Riponne → Bessières → Sallaz → Fourmi/Fiches-Nord → Croisettes) -->
                <!-- Ligne continue : Ouchy → Gare → Falaises → Fiches-Nord → mi-chemin En Cojonnex -->
                <path d="M 400,475 C 399,455 398,435 397,415 C 396,395 395,375 394,355 L 390,340 C 388,330 390,320 395,312 C 402,302 415,292 430,280 C 445,268 458,258 470,250 C 485,240 500,225 510,210 C 520,195 530,180 540,170 C 548,162 553,155 560,145 C 567,135 575,125 585,112 C 585,108 587,105 588,103" stroke="#E2001A" stroke-width="2" fill="none" stroke-linecap="round" opacity="0.5"/>
                <rect x="495" y="210" width="18" height="11" rx="3" fill="#E2001A" opacity="0.7"/>
                <text x="504" y="218" text-anchor="middle" font-family="Helvetica Neue, sans-serif" font-size="8" fill="#FFF" font-weight="600">M2</text>

                <!-- Repères urbains -->
                <text class="mc-repere" x="380" y="358">Gare CFF</text>
                <text class="mc-repere" x="380" y="478">Ouchy</text>
                <text class="mc-lac-label" x="340" y="540">LAC LÉMAN</text>

                <!-- Les 4 quartiers SILL -->
                <a href="#plaines-du-loup" class="mc-link">
                    <circle class="mc-ring" cx="260" cy="180" r="24"/>
                    <circle class="mc-dot" cx="260" cy="180" r="7"/>
                    <text class="mc-label" x="188" y="210">Plaines-du-Loup</text>
                </a>

                <a href="#fiches-nord" class="mc-link">
                    <circle class="mc-ring" cx="545" cy="165" r="20"/>
                    <circle class="mc-dot" cx="545" cy="165" r="7"/>
                    <text class="mc-label" x="490" y="152">Fiches-Nord</text>
                </a>

                <a href="#falaises" class="mc-link">
                    <circle class="mc-ring" cx="440" cy="268" r="18"/>
                    <circle class="mc-dot" cx="440" cy="268" r="7"/>
                    <text class="mc-label" x="395" y="290">Falaises</text>
                </a>

                <a href="#en-cojonnex" class="mc-link">
                    <circle class="mc-ring" cx="630" cy="40" r="20"/>
                    <circle class="mc-dot" cx="630" cy="40" r="7"/>
                    <text class="mc-label" x="580" y="75">En Cojonnex</text>
                </a>
            </svg>
        </div>
    </div>
</section>

<!-- ════════════════════════════════════════════════════════════════
     Cadre de mixité — 4 quarts + 3 tiers
     ════════════════════════════════════════════════════════════════ -->
<section class="section-mixite">
    <div class="container">
        <div class="mixite-intro reveal">
            <h2>La mixité par la multiplicité des acteurs</h2>
            <p>La Ville de Lausanne structure ses grands projets urbains selon deux principes complémentaires visant à garantir la diversité sociale et architecturale de chaque quartier.</p>
        </div>

        <div class="mixite-grid">
            <div class="mixite-card reveal">
                <span class="mixite-label">Diversité d'investisseurs</span>
                <span class="mixite-title">Les 4 quarts</span>
                <div class="mixite-quarts">
                    <div class="quart"><span class="quart-pct">25 %</span><span class="quart-text">Société et fondation de la Ville</span></div>
                    <div class="quart"><span class="quart-pct">25 %</span><span class="quart-text">Coopératives classiques</span></div>
                    <div class="quart"><span class="quart-pct">25 %</span><span class="quart-text">Coopératives d'habitants</span></div>
                    <div class="quart"><span class="quart-pct">25 %</span><span class="quart-text">Investisseurs institutionnels et privés</span></div>
                </div>
            </div>

            <div class="mixite-card reveal">
                <span class="mixite-label">Diversité de loyers</span>
                <span class="mixite-title">Les 3 tiers</span>
                <div class="mixite-tiers">
                    <div class="tiers-list">
                        <div class="tiers-item"><span class="tiers-pct tiers-pct-sub">~30 %</span><span class="tiers-text">Logements subventionnés</span></div>
                        <div class="tiers-item"><span class="tiers-pct tiers-pct-ctrl">~40 %</span><span class="tiers-text">Loyers contrôlés</span></div>
                        <div class="tiers-item"><span class="tiers-pct tiers-pct-libre">~30 %</span><span class="tiers-text">Marché libre</span></div>
                    </div>
                    <div class="tiers-bar-compact">
                        <span class="bar-sub"></span>
                        <span class="bar-ctrl"></span>
                        <span class="bar-libre"></span>
                    </div>
                </div>
            </div>
        </div>

        <p class="mixite-note">C'est la multiplicité des investisseurs au sein de chaque pièce urbaine qui génère la mixité — et non une simple ségrégation par zone.</p>
    </div>
</section>

<!-- ════════════════════════════════════════════════════════════════
     Les 4 quartiers
     ════════════════════════════════════════════════════════════════ -->
<?php foreach ($quartiers as $i => $q):
    $bgClass = ($i % 2 === 0) ? '' : ' section-alt';
    $qImmeubles = $parQuartier[$q['dbKey']] ?? [];
    $totalLogQuartier = 0;
    foreach ($qImmeubles as $im) $totalLogQuartier += (int)$im['nb_logements'];
?>
<section class="section-quartier<?= $bgClass ?>" id="<?= $q['id'] ?>">
    <div class="container">

        <!-- Image hero quartier -->
        <?php if (isset($quartierImages[$q['id']])): ?>
        <div class="quartier-hero reveal">
            <img src="<?= $quartierImages[$q['id']] ?>" alt="Vue aérienne — <?= e($q['nom']) ?>" loading="lazy">
            <span class="quartier-hero-credit">© SILL-PMx</span>
        </div>
        <?php endif; ?>

        <!-- Header quartier -->
        <div class="quartier-header reveal">
            <span class="quartier-numero"><?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?></span>
            <h2><?= e($q['nom']) ?></h2>
            <span class="quartier-etape"><?= e($q['etape']) ?></span>
        </div>

        <!-- Intro + KPI strip -->
        <div class="quartier-intro reveal">
            <p class="quartier-texte"><?= $q['intro'] ?></p>
            <div class="quartier-kpi-strip">
                <div class="quartier-kpi">
                    <span class="quartier-kpi-value"><?= $q['logements_sill'] ?></span>
                    <span class="quartier-kpi-label">Logements SILL</span>
                </div>
                <div class="quartier-kpi">
                    <span class="quartier-kpi-value"><?= $q['logements_total'] ?></span>
                    <span class="quartier-kpi-label">Logements quartier</span>
                </div>
                <div class="quartier-kpi">
                    <span class="quartier-kpi-value"><?= count($q['investisseurs']) ?></span>
                    <span class="quartier-kpi-label">Investisseurs</span>
                </div>
                <div class="quartier-kpi">
                    <span class="quartier-kpi-value"><?= count($q['architectes']) ?></span>
                    <span class="quartier-kpi-label">Bureaux d'architecture</span>
                </div>
            </div>
        </div>

        <!-- 2 colonnes : Investisseurs + Architectes -->
        <div class="quartier-detail-grid">

            <div class="quartier-col reveal">
                <h3>Investisseurs</h3>
                <table class="quartier-table">
                    <thead>
                        <tr><th>Entité</th><th>Catégorie</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($q['investisseurs'] as $inv): ?>
                        <tr<?= ($inv['nom'] === 'SILL SA') ? ' class="row-sill"' : '' ?>>
                            <td><?= e($inv['nom']) ?></td>
                            <td><?= e($inv['type']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="quartier-col reveal">
                <h3>Architecture</h3>
                <table class="quartier-table">
                    <thead>
                        <tr><th>Bureau</th><th>Projet</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($q['architectes'] as $arch): ?>
                        <tr>
                            <td><?= e($arch['bureau']) ?></td>
                            <td><?= e($arch['projet']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </div>

        <!-- Photos immeubles SILL dans ce quartier -->
        <?php if ($qImmeubles): ?>
        <div class="quartier-immeubles reveal">
            <h3>Réalisations SILL</h3>
            <div class="quartier-cards">
                <?php foreach ($qImmeubles as $im):
                    $coverUrl = immeubleCoverUrl($im['slug']);
                ?>
                <a href="<?= SITE_URL ?>/portefeuille/<?= e($im['slug']) ?>" class="quartier-card">
                    <div class="quartier-card-img">
                        <img src="<?= $coverUrl ?>" alt="<?= e($im['nom']) ?>" loading="lazy">
                    </div>
                    <div class="quartier-card-body">
                        <span class="quartier-card-nom"><?= e($im['nom']) ?></span>
                        <span class="quartier-card-meta"><?= (int)$im['nb_logements'] ?> logements<?= $im['annee_livraison'] ? ' — ' . e($im['annee_livraison']) : '' ?></span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
</section>
<?php endforeach; ?>

<!-- Sources -->
<section class="section-sources">
    <div class="container">
        <p class="marche-sources">Sources : Rapport d'activité SILL SA 2024 — Ville de Lausanne, programme Métamorphose — Office fédéral du logement — Espazium</p>
    </div>
</section>
