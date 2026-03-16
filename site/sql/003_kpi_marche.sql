-- 003_kpi_marche.sql
-- Ajout des KPI marché et conjoncture
-- À exécuter sur sillsa_v2

-- 1. Étendre l'ENUM category pour accueillir 'marche' et 'energie'
ALTER TABLE sill_kpi
  MODIFY COLUMN category
  ENUM('patrimoine','finance','social','environnement','gouvernance','marche','energie')
  DEFAULT 'patrimoine';

-- 2. Insérer les indicateurs marché locatif
INSERT INTO sill_kpi (kpi_key, label, value_num, value_text, unit, category, sort_order, is_public) VALUES
('vacance_lausanne',  'Taux de vacance — District de Lausanne', 0.63,  NULL,               '%',        'marche', 110, 1),
('loyer_median',      'Loyer médian Lausanne',                  3359,  NULL,               'CHF/mois', 'marche', 120, 1),
('hausse_loyers',     'Hausse loyers proposés CH',              2.2,   'Prévision +1,5 % en 2026', '%', 'marche', 130, 1);

-- 3. Insérer les indicateurs taux et financement
INSERT INTO sill_kpi (kpi_key, label, value_num, value_text, unit, category, sort_order, is_public) VALUES
('taux_reference',    'Taux de référence hypothécaire',         1.25,  'Stable',           '%',        'marche', 140, 1),
('taux_directeur',    'Taux directeur BNS',                     0.00,  'Stable depuis juin 2025', '%', 'marche', 150, 1),
('inflation_ch',      'Inflation CH — prévision 2026',          0.8,   NULL,               '%',        'marche', 160, 1);

-- 4. Insérer les indicateurs énergie
INSERT INTO sill_kpi (kpi_key, label, value_num, value_text, unit, category, sort_order, is_public) VALUES
('elec_sil',          'Électricité SiL — prix énergie',         9.88,  '−13,1 % vs 2025', 'ct/kWh',   'energie', 170, 1),
('cad_sil',           'Chauffage à distance (CAD)',              16.09, 'Stable',           'ct/kWh',   'energie', 180, 1),
('gaz_sil',           'Gaz naturel SiL',                        NULL,  '+6,7 % dès oct. 2025', NULL,   'energie', 190, 1);
