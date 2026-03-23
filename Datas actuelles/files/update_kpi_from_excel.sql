-- ================================================================
-- MISE À JOUR KPI depuis Dashboard KPI SILL SA.xlsx
-- Données au 31.12.2025 / État locatif 2026
-- Généré le 2026-03-12
-- ================================================================

-- Vider les KPI existants pour repartir proprement
DELETE FROM sill_kpi;

-- ────────────────────────────────────────────────────────────────
-- PATRIMOINE (catégorie patrimoine)
-- ────────────────────────────────────────────────────────────────
INSERT INTO sill_kpi (kpi_key, label, value_num, value_text, unit, icon, category, sort_order, is_public) VALUES
('nb_immeubles',       'Immeubles',                   10,    NULL,              NULL,      'buildings',   'patrimoine',  1,  1),
('nb_lots',            'Logements et lots',           834,    NULL,              NULL,      'building',    'patrimoine',  2,  1),
('surface_sup',        'Surface SUP totale',        66956,    NULL,              'm²',      'ruler',       'patrimoine',  3,  0),
('valeur_comptable',   'Valeur comptable',          279.4,    NULL,              'M CHF',   'banknotes',   'patrimoine',  4,  0),
('valeur_dcf',         'Valeur patrimoniale DCF',   326.7,    NULL,              'M CHF',   'chart-line',  'patrimoine',  5,  1),
('valeur_eca',         'Valeur ECA 2026',           298.1,    NULL,              'M CHF',   'shield',      'patrimoine',  6,  0),
('cout_construction',  'Coût de construction',      292.7,    NULL,              'M CHF',   'hammer',      'patrimoine',  7,  0),

-- ────────────────────────────────────────────────────────────────
-- RENDEMENT (catégorie finance)
-- ────────────────────────────────────────────────────────────────
('etat_locatif',       'État locatif net 2026',      15.1,    NULL,              'M CHF',   'receipt',     'finance',     10, 1),
('rdt_cout_constr',    'Rendement / coût constr.',    5.15,   NULL,              '%',       'percent',     'finance',     11, 0),
('rdt_val_comptable',  'Rendement / val. comptable',  5.39,   NULL,              '%',       'percent',     'finance',     12, 0),
('rdt_dcf',            'Rendement / DCF',             4.61,   NULL,              '%',       'percent',     'finance',     13, 0),
('valeur_theo_35',     'Valeur théorique à 3.5%',   430.7,    NULL,              'M CHF',   'calculator',  'finance',     14, 0),
('charges_moy_m2',     'Charges moy. /m² SUP',       60.11,   NULL,              'CHF',     'coins',       'finance',     15, 0),
('charges_pct_el',     'Charges / état locatif',      23.2,   NULL,              '%',       'percent',     'finance',     16, 0),

-- ────────────────────────────────────────────────────────────────
-- FINANCEMENT (catégorie finance)
-- ────────────────────────────────────────────────────────────────
('dette_hypo',         'Dette hypothécaire',         222.2,    NULL,              'M CHF',   'bank',        'finance',     20, 0),
('taux_avance_dcf',    'Taux d''avance moy. / DCF',  76.0,    NULL,              '%',       'percent',     'finance',     21, 0),
('cedules_total',      'Cédules totales',            254.6,    NULL,              'M CHF',   'document',    'finance',     22, 0),

-- ────────────────────────────────────────────────────────────────
-- ENVIRONNEMENT (catégorie environnement)
-- ────────────────────────────────────────────────────────────────
('conso_energie_m2',   'Conso. énergétique moy.',    77.05,   NULL,              'kWh/m²',  'lightning',   'environnement', 30, 0),

-- ────────────────────────────────────────────────────────────────
-- SOCIAL / AFFECTATION (catégorie social)
-- ────────────────────────────────────────────────────────────────
('loyer_net_moy_m2',   'Loyer net moyen',           251.69,   NULL,              'CHF/m²',  'home',        'social',      40, 0),

-- ────────────────────────────────────────────────────────────────
-- GOUVERNANCE (catégorie gouvernance)
-- ────────────────────────────────────────────────────────────────
('annee_creation',     'Année de création',           NULL,   '2009',            NULL,      'calendar',    'gouvernance', 50, 0),
('capital_social',     'Capital social',              52.5,    NULL,              'M CHF',   'banknotes',   'gouvernance', 51, 0);

-- ================================================================
-- RÉSUMÉ : 21 KPI importés
-- 4 affichés (is_public=1) : immeubles, lots, valeur DCF, état locatif
-- 17 masqués (is_public=0) : activables depuis l'admin
-- ================================================================
