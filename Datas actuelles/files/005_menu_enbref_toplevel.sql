-- 005_menu_enbref_toplevel.sql
-- Promouvoir "En bref" au premier niveau de navigation (entre Accueil et Contexte)
-- Date : 2026-03-21

-- 1. Récupérer le sort_order de "Accueil" pour placer "En bref" juste après
-- On décale tous les items top-level dont sort_order > celui d'Accueil
UPDATE sill_menu
SET sort_order = sort_order + 1
WHERE parent_id = 0 AND is_active = 1 AND sort_order > (
    SELECT sort_order FROM (SELECT sort_order FROM sill_menu WHERE target_value = '' AND parent_id = 0 LIMIT 1) AS t
);

-- 2. Insérer "En bref" au premier niveau, juste après Accueil
INSERT INTO sill_menu (label, target_value, parent_id, sort_order, is_active)
SELECT 'En bref', 'en-bref', 0,
    (SELECT sort_order FROM (SELECT sort_order FROM sill_menu WHERE target_value = '' AND parent_id = 0 LIMIT 1) AS t2) + 1,
    1
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM sill_menu WHERE target_value = 'en-bref' AND parent_id = 0 AND is_active = 1);

-- 3. Désactiver l'ancienne entrée "En bref" si elle était sous-menu de Contexte
UPDATE sill_menu SET is_active = 0
WHERE target_value = 'contexte/en-bref' AND parent_id > 0;
