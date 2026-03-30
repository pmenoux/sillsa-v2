-- 007_update_kpi_mars2026.sql
-- Mise à jour KPI marché — données mars 2026
-- Sources : OFS (IPC fév. 2026), Homegate/SMG (indice loyers fév. 2026)

-- Inflation CH : 0,8 % (prévision) → 0,1 % (IPC réel fév. 2026, glissement annuel)
UPDATE sill_kpi
SET value_num  = 0.1,
    label      = 'Inflation CH — IPC février 2026',
    value_text = 'Glissement annuel (OFS)'
WHERE kpi_key = 'inflation_ch';

-- Hausse loyers proposés : +2,2 % (janv.) → +2,4 % (fév. 2026, glissement annuel)
UPDATE sill_kpi
SET value_num  = 2.4,
    value_text = 'Glissement annuel fév. 2026'
WHERE kpi_key = 'hausse_loyers';
