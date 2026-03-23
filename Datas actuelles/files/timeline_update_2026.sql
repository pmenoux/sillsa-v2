-- ================================================================
-- MISE À JOUR TIMELINE SILL SA — Mars 2026
-- Source : timeline_SILL_SA.xlsx (compilé 23.03.2026)
-- 6 UPDATE + 1 DELETE + 17 INSERT
-- ================================================================

-- ────────────────────────────────────────────────────────────────
-- PARTIE 1 : MISES À JOUR d'événements existants
-- ────────────────────────────────────────────────────────────────

-- 1. Création SILL : date corrigée (23 juin 2009, pas octobre)
UPDATE sill_timeline
SET event_date = '2009-06-23',
    title = '2009 — Création officielle de la SILL SA',
    description = 'Le 23 juin 2009, le Conseil communal adopte le rapport-préavis N° 2008/59 et crée la Société Immobilière Lausannoise pour le Logement SA. Capital initial : 1 million de francs. Héritière de la Coopérative Colosa, la SILL reprend la mission de maintenir une offre de logements à loyers modérés à Lausanne.'
WHERE slug = '2009-creation-de-la-sill';

-- 2. Calvaire-Falaises : enrichir avec concours et détails
UPDATE sill_timeline
SET description = 'Collaboration entre la SILL et la SCILMO (Société Coopérative Immobilière La Maison Ouvrière). Concours d''architecture sur sélection en 2012, remporté par MPH Architectes. Trois bâtiments totalisant 194 logements, une crèche, un APEMS, une bibliothèque médicale, un fitness et un restaurant. Certification Minergie-P-Eco.'
WHERE slug = '2016-projet-calvaire-falaises-collaboration-sill-scilmo';

-- 3. Clairières : enrichir avec concours SIA 142
UPDATE sill_timeline
SET description = 'En 2014, la SILL lance un concours ouvert SIA 142 pour environ 200 chambres étudiantes sur la parcelle En Cojonnex, à proximité de l''École Hôtelière de Lausanne. 50 projets reçus de plusieurs pays. Le bureau MPH Architectes remporte le concours avec le projet « Clairières ». Mise en location fin 2017 : 98 logements modulaires accueillant quelque 255 étudiants.'
WHERE slug = '2017-residence-clairieres-logements-etudiants-en-cojonnex';

-- 4. Pièce Urbaine D : enrichir avec phasage
UPDATE sill_timeline
SET description = 'Pièce urbaine D de l''écoquartier des Plaines-du-Loup, caractérisée par la générosité de ses espaces publics. La SILL y développe deux lots distincts : logements subventionnés et PPE. Travaux prévus dans le cadre des phases suivantes du projet Métamorphose.'
WHERE slug = '2018-2023-piece-urbaine-d-lots-sill-subventionnes-et-ppe';

-- 5. Publication ESG : enrichir
UPDATE sill_timeline
SET description = 'La SILL publie une synthèse de sa performance environnementale, sociale et de gouvernance. Le portefeuille, construit entre 2014 et 2023, répond à des exigences énergétiques strictes. Des documents de suivi réalisés par un bureau d''ingénieurs attestent d''une baisse significative de la consommation énergétique.'
WHERE slug = '2025-publication-performance-esg';

-- 6. Politique foncière : préavis approuvé (mars 2026)
UPDATE sill_timeline
SET title = '2026 — Acquisition de 112 logements en droits de superficie',
    slug = '2026-acquisition-112-logements-droits-de-superficie',
    event_date = '2026-03-15',
    description = 'Le Conseil communal approuve le préavis N° 2025/41 autorisant la revente de trois immeubles (112 logements) à la SILL en droits de superficie : avenue de Béthusy 86-88, avenue Jomini et environs. Assainissement énergétique garanti. Le portefeuille atteint 10 immeubles et 796 appartements.'
WHERE slug = '2025-politique-fonciere-acquisitions-par-preemption';

-- ────────────────────────────────────────────────────────────────
-- PARTIE 2 : SUPPRESSION de l'événement générique 2026+
-- ────────────────────────────────────────────────────────────────

DELETE FROM sill_timeline
WHERE slug = '2026-perspectives-et-developpement-futur';

-- ────────────────────────────────────────────────────────────────
-- PARTIE 3 : INSERTION de 17 nouveaux événements
-- ────────────────────────────────────────────────────────────────

INSERT INTO sill_timeline (slug, title, event_date, category, description, link_url, sort_order, is_active) VALUES

-- Pré-SILL (2005-2008)
('2005-preavis-3000-logements',
 '2005 — Préavis n° 2005/45 : 3''000 nouveaux logements',
 '2005-11-01',
 'collaborations_publiques',
 'La Ville de Lausanne adopte un programme cadre définissant les parcelles constructibles pour 3''000 nouveaux logements. Cette décision politique fondatrice est à l''origine directe de la création de la SILL.',
 'https://www.lausanne.ch',
 10, 1),

('2008-concours-avenue-de-provence',
 '2008 — Concours Avenue de Provence : bureau Farra & Zoumboulakis',
 '2008-05-01',
 'concours_architecturaux',
 'Le bureau Farra & Zoumboulakis remporte le concours d''architecture pour l''opération Avenue de Provence / chemin de la Prairie (projet Zenith). Ce concours marque le lancement de la première réalisation de la future SILL.',
 NULL,
 20, 1),

('2008-rapport-preavis-2008-59',
 '2008 — Rapport-préavis N° 2008/59',
 '2008-11-01',
 'collaborations_publiques',
 'La Municipalité de Lausanne propose de recapitaliser la CPCL, de dissoudre la Coopérative Colosa et de créer une nouvelle société immobilière municipale. Ce rapport-préavis pose les fondations institutionnelles de la SILL.',
 'https://sillsa.ch/a-propos/',
 30, 1),

-- Fondation et premiers pas (2009-2012)
('2009-dissolution-colosa',
 '2009 — Dissolution Colosa et transfert du patrimoine',
 '2009-12-15',
 'gouvernance',
 'Dissolution effective de la Coopérative Colosa et transfert notarial de plus de 400 actes immobiliers à la SILL. Création simultanée du Parking du Relais. Cette opération juridique d''envergure assure la continuité du patrimoine locatif lausannois.',
 NULL,
 50, 1),

('2011-preavis-avenue-de-provence',
 '2011 — Préavis N° 2011/12 : Avenue de Provence',
 '2011-02-09',
 'collaborations_publiques',
 'Le Conseil communal autorise la SILL à acquérir les bâtiments A et C de l''opération Avenue de Provence : 52 logements dont 28 destinés aux aînés, 24 à loyers contrôlés et un APEMS. Cautionnement municipal de 5,3 millions de francs.',
 NULL,
 60, 1),

('2012-concours-falaises-calvaire',
 '2012 — Concours Falaises / Calvaire',
 '2012-06-01',
 'concours_architecturaux',
 'Concours d''architecture sur sélection pour 194 logements au chemin des Falaises, en partenariat SILL et SCILMO. Le bureau MPH Architectes est désigné lauréat pour ce projet d''envergure qui combinera habitat, services et certification Minergie-P-Eco.',
 'https://sillsa.ch/falaises/',
 70, 1),

-- Expansion (2013-2016)
('2013-inauguration-chemin-de-la-prairie',
 '2013 — Inauguration chemin de la Prairie 5A–5C',
 '2013-06-15',
 'livraisons_emblematiques',
 'Première réalisation de la SILL : 52 logements certifiés Minergie à l''avenue de Provence. 28 appartements pour aînés et 24 à loyers contrôlés. Architecture : bureau Farra & Zoumboulakis (projet Zenith).',
 'https://sillsa.ch/chemin-de-la-prairie-5a-et-5c/',
 80, 1),

('2014-resultats-concours-en-cojonnex',
 '2014 — Résultats concours En Cojonnex : projet « Clairières »',
 '2014-08-11',
 'concours_architecturaux',
 'Le bureau MPH Architectes remporte le concours ouvert SIA 142 avec le projet « Clairières ». 50 projets reçus de plusieurs pays pour la réalisation d''environ 200 chambres étudiantes sur la parcelle communale En Cojonnex, à proximité de l''École Hôtelière de Lausanne.',
 NULL,
 90, 1),

('2016-inauguration-fiches-nord',
 '2016 — Inauguration Fiches Nord lots 8 et 9',
 '2016-11-15',
 'livraisons_emblematiques',
 'Huit bâtiments conçus par le bureau NB.ARCH (projet Calisto) au chemin de Bérée : 131 logements dont 47 subventionnés, 52 à loyers contrôlés et 32 en PPE. Tous certifiés Minergie-P-Eco. Inauguration en présence du Syndic Daniel Brélaz.',
 'https://sillsa.ch/fiches-nord-lots-8-9/',
 110, 1),

-- Maturité (2018-2021)
('2018-inauguration-parc-du-loup',
 '2018 — Inauguration du Parc du Loup',
 '2018-06-16',
 'metamorphose',
 'Inauguration officielle du parc central au cœur du futur écoquartier des Plaines-du-Loup. Cet espace vert de 2,5 hectares constitue le poumon du quartier et le premier aménagement visible du projet Métamorphose.',
 NULL,
 130, 1),

('2020-prix-bilan-immobilier-falaises',
 '2020 — Prix Bilan de l''Immobilier : Les Falaises',
 '2020-09-15',
 'communication_transparence',
 'Le projet Les Falaises (MPH Architectes) reçoit une double distinction au Prix Bilan de l''Immobilier 2020 : prix de la catégorie résidentiel locatif et prix spécial durabilité et écologie. Une reconnaissance de la qualité architecturale et environnementale du portefeuille SILL.',
 'https://www.bilan.ch/immobilier/prix-de-limmobilier-romand-les-laureats-2020',
 150, 1),

('2021-portes-ouvertes-chantier-pdl',
 '2021 — Portes ouvertes chantier Plaines-du-Loup',
 '2021-10-02',
 'communication_transparence',
 'Quelque 250 personnes visitent le chantier de l''écoquartier lors d''une journée portes ouvertes. Pose de la première pierre de l''école des Plaines-du-Loup, qui accueillera 320 élèves.',
 NULL,
 160, 1),

-- Récent (2022-2025)
('2022-inauguration-ecoquartier-pdl',
 '2022 — Inauguration de l''écoquartier des Plaines-du-Loup',
 '2022-08-13',
 'livraisons_emblematiques',
 'Arrivée des 200 premiers habitants du 1er secteur de l''écoquartier. Cérémonie officielle au Parc du Loup. La SILL livre les immeubles route des Plaines-du-Loup 51a, 51b et 53, totalisant 104 logements.',
 'https://www.lausanne.ch/officiel/grands-projets/metamorphose/participer/demarches-2016-2022.html',
 170, 1),

('2024-inauguration-piece-urbaine-a',
 '2024 — Inauguration Pièce Urbaine A aux Plaines-du-Loup',
 '2024-06-27',
 'livraisons_emblematiques',
 'Cérémonie officielle en présence du Syndic Grégoire Junod et de la Conseillère d''État Christelle Luisier Brodard. 148 logements réalisés en partenariat : Coopérative Cité Derrière (49 %), SILL (31 %) et Swiss Life (20 %). Fitness urbain, centre médico-social et policlinique universitaire complètent l''ensemble.',
 'https://citederriere.ch/inauguration-de-la-piece-urbaine-a-dans-lecoquartier-des-plaines-du-loup/',
 190, 1),

('2025-inauguration-ecole-pdl',
 '2025 — Inauguration de l''école et des espaces publics',
 '2025-09-26',
 'livraisons_emblematiques',
 'Fin officielle de la première étape de l''écoquartier des Plaines-du-Loup : 2 500 habitants sont installés. Inauguration de l''école (320 élèves) et de la place Buissonnière. Le 7e forum participatif du quartier se tient le lendemain.',
 'https://www.lausanne.ch/apps/actualites/Next/serve.php?id=16765',
 210, 1),

-- 2026
('2026-15-investisseurs-2e-secteur-pdl',
 '2026 — 15 investisseurs retenus pour le 2e secteur',
 '2026-02-11',
 'metamorphose',
 'La Ville de Lausanne annonce les 15 investisseurs retenus pour 16 bâtiments du 2e secteur des Plaines-du-Loup : 1 200 logements et 30 000 m² d''activités. La SILL figure parmi les investisseurs sélectionnés. Début des chantiers prévu en 2028.',
 'https://www.lausanne.ch/apps/actualites/index.php?actu_id=86250',
 220, 1);

-- NB : l'événement 112 logements est couvert par l'UPDATE de l'ancien événement politique_fonciere (slug renommé)

-- Désactiver l'ancien doublon Fiches Nord (remplacé par l'événement plus précis 2016-inauguration-fiches-nord)
UPDATE sill_timeline SET is_active = 0 WHERE slug = '2017-2019-fiches-nord-lots-8-9-inauguration-livraisons';

-- ────────────────────────────────────────────────────────────────
-- PARTIE 4 : RESÉQUENCER sort_order (chronologique)
-- ────────────────────────────────────────────────────────────────

SET @row_number = 0;
UPDATE sill_timeline
SET sort_order = (@row_number := @row_number + 1)
ORDER BY event_date ASC, id ASC;

-- ================================================================
-- RÉSUMÉ : 37 événements actifs après migration
-- Période couverte : 2005 – mars 2026
-- ================================================================
