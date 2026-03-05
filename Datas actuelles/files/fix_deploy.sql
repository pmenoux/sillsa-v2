-- ============================================================
-- SILL SA — Fix post-deployment
-- Re-inserts settings, menu, KPI data that was truncated
-- Fixes page slugs to match index.php routes
-- ============================================================

-- -----------------------------------------------------------
-- 1. SETTINGS (re-insert from schema)
-- -----------------------------------------------------------
DELETE FROM sill_settings;
INSERT INTO sill_settings (setting_key, setting_value) VALUES
('site_name',        'SILL SA'),
('site_tagline',     'Société Immobilière Lausannoise pour le Logement SA'),
('site_url',         'https://26.sillsa.ch'),
('contact_address',  'Rue Haldimand 17, 1003 Lausanne'),
('contact_email',    'info@sillsa.ch'),
('capital_social',   '52500000'),
('date_creation',    '2009'),
('meta_description', 'La SILL SA promeut des logements à loyers modérés à Lausanne, dans le respect du développement durable.');

-- -----------------------------------------------------------
-- 2. MENU (with target_values matching index.php routes)
-- -----------------------------------------------------------
DELETE FROM sill_menu;
INSERT INTO sill_menu (id, parent_id, label, target_type, target_value, sort_order) VALUES
(1,  0, 'Accueil',           'page', 'accueil',                 1),
(2,  0, 'A propos',          'page', 'la-societe',              2),
(3,  2, 'La Société',        'page', 'la-societe',              1),
(4,  2, 'Le CA',             'page', 'conseil-administration',  2),
(5,  2, 'L''organisation',   'page', 'organisation',            3),
(6,  2, 'Aspects sociétaux', 'page', 'aspects-societaux',       4),
(7,  2, 'Environnement',     'page', 'environnement',           5),
(8,  0, 'Portefeuille',      'section','portefeuille',           3),
(9,  0, 'Publications',      'page', 'publications',            4),
(10, 0, 'Location',          'page', 'location',                5);

-- -----------------------------------------------------------
-- 3. KPI (re-insert from schema)
-- -----------------------------------------------------------
DELETE FROM sill_kpi;
INSERT INTO sill_kpi (kpi_key, label, value_num, unit, icon, category, sort_order) VALUES
('capital_social',     'Capital social',           52.5,   'M CHF', 'banknotes',    'finance',        1),
('nb_logements',       'Logements gérés',          735,    NULL,    'building',     'patrimoine',     2),
('nb_immeubles',       'Immeubles',                10,     NULL,    'buildings',    'patrimoine',     3),
('valeur_dcf',         'Valeur patrimoniale DCF',  327,    'M CHF', 'chart-line',   'finance',        4),
('annee_creation',     'Année de création',         NULL,  NULL,    'calendar',     'gouvernance',    5);

UPDATE sill_kpi SET value_text = '2009' WHERE kpi_key = 'annee_creation';

-- -----------------------------------------------------------
-- 4. FIX PAGE SLUGS (match index.php routes)
-- -----------------------------------------------------------
UPDATE sill_pages SET slug = 'la-societe'             WHERE slug = 'a-propos';
UPDATE sill_pages SET slug = 'conseil-administration'  WHERE slug = 'a-propos-2';
UPDATE sill_pages SET slug = 'organisation'            WHERE slug = 'lorganisation';
UPDATE sill_pages SET slug = 'environnement'           WHERE slug = 'durabilite';
