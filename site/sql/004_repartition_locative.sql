-- 004_repartition_locative.sql
-- Répartition locative par affectation (source: Samuel Varone, données 31.12.2025)
-- La table est auto-créée par repartition.php, ce script insère les données initiales.

INSERT INTO sill_repartition_locative (affectation, nb_logements, surface_m2, loyer_annuel_net) VALUES
    ('Activité',      27,  7683.1,  2230836),
    ('Etudiants',    141,  9004.7,  2405666),
    ('LLA',          277, 21115.8,  5277036),
    ('LLA - protégé', 28,  1804.4,   438216),
    ('LLM',          303, 22464.3,  5085768),
    ('LML',           58,  4883.6,  1339224)
ON DUPLICATE KEY UPDATE
    nb_logements     = VALUES(nb_logements),
    surface_m2       = VALUES(surface_m2),
    loyer_annuel_net = VALUES(loyer_annuel_net);
