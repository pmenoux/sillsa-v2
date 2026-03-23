-- 006_pellet_replace_gaz.sql
-- Remplace le KPI gaz_sil (non utilisé par SILL) par pellet_ch
-- Source prix : pelletpreis.ch mars 2026 — 508,8 CHF/t ≈ 10,4 ct/kWh

DELETE FROM sill_kpi WHERE kpi_key = 'gaz_sil';

INSERT INTO sill_kpi (kpi_key, label, value_num, value_text, unit, category, sort_order, is_public) VALUES
('pellet_ch', 'Pellets bois — prix moyen CH', 10.4, '+6,4 % vs 2025', 'ct/kWh', 'energie', 190, 1);
