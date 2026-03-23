# KPI Marche et conjoncture — Transmission pour 26.sillsa.ch

Source : Notion — KPI PMx — Tableau de bord strategique
Date d'extraction : 16 mars 2026
Prochaine mise a jour prevue : 20 mars 2026 (post-decision BNS)

---

## 1. Taux et politique monetaire

| Indicateur | Valeur | Tendance | Source / Date |
|---|---|---|---|
| Taux directeur BNS | 0,00 % | Stable depuis juin 2025 | BNS — prochaine decision 20.03.2026 |
| SARON (fixing) | −0,05 % | Stable | BNS, 12.03.2026 |
| Taux de reference hypothecaire (bail) | 1,25 % | Stable (taux moyen 1,32 %) | OFL, confirme 02.03.2026 |
| Hypo fixe 10 ans (indicatif) | 1,50 – 2,05 % | Stable a legerement baissier | Comparis / UBS, mars 2026 |
| Hypo SARON (marge) | SARON + des 1,05 % | Stable | PostFinance, mars 2026 |
| Inflation CH (prevision 2026) | 0,8 % | Bas de la fourchette cible 0–2 % | Prevision BNS |

## 2. Marche immobilier et locatif

| Indicateur | Valeur | Tendance | Source / Date |
|---|---|---|---|
| Taux de vacance — Canton de Vaud | 0,89 % | En baisse = penurie (−0,07 pt vs 2024) | Etat de Vaud, 1er juin 2025 |
| Taux de vacance — District de Lausanne | 0,63 % | Tendu (seuil equilibre : 1,5 %) | Etat de Vaud, 1er juin 2025 |
| Taux de vacance — Ouest lausannois | 0,47 % | Tres tendu | Etat de Vaud, 1er juin 2025 |
| Loyer median Lausanne (appartement) | CHF 3 359 /mois | Hausse moderee (+2,2 % sur 12 mois) | Homegate / SMG, janv. 2026 |
| Hausse loyers proposes CH (annuelle) | +2,2 % | Ralentissement (prevision +1,5 % en 2026) | ZKB / Homegate, janv. 2026 |
| Prix logements en propriete CH | +3,5 % (2025) | Prevision +3 % en 2026 | UBS, 2026 |

## 3. Construction et reglementation

| Indicateur | Valeur | Tendance | Source / Date |
|---|---|---|---|
| Indice prix construction (CRB) | 116,2 pts (base oct. 2020 = 100) | +0,3 % / 6 mois, +0,9 % / 12 mois | OFS, octobre 2025 |
| Programme Batiments VD 2026 | 74 M CHF (+22 % vs 2025) | 34 M Canton + 40 M Confederation | Etat de Vaud, 2026 |
| Subvention Pronovo PV | ~30 % cout investissement | Stable | Pronovo |

## 4. Energie — Tarifs SiL et fournisseurs regionaux

| Energie | Fournisseur | Tarif 2026 | Evolution |
|---|---|---|---|
| Electricite — prix energie | SiL Lausanne | 9,88 ct/kWh | −13,1 % vs 2025 |
| Electricite — utilisation reseau | SiL Lausanne | 12,10 ct/kWh | −24,1 % vs 2025 |
| Chauffage a distance (CAD) | SiL Lausanne | 16,09 ct/kWh | Stable |
| Gaz naturel | SiL Lausanne | Tarif janv. 2026 | +6,7 % des oct. 2025 |
| Electricite | Romande Energie | ~CHF 747.– /an (2000 kWh) | −1,6 % vs 2025 |

Synthese : electricite en baisse significative (−7,7 % SiL), CAD stable, gaz en hausse (+6,7 %). Le CAD reste competitif face au gaz.

---

## Integration proposee pour 26.sillsa.ch

### Table cible : `sill_kpi`

Schema existant : `value_num`, `value_text`, `unit`, `label`

### Donnees a inserer (selection pour le site public)

Les indicateurs ci-dessous sont les plus pertinents pour le site institutionnel SILL SA.
Ils positionnent la societe dans son contexte de marche.

```sql
-- KPI Marche — a inserer dans sill_kpi
-- Categorie suggeree : 'marche' (a ajouter si absente)

INSERT INTO sill_kpi (label, value_num, value_text, unit, category, sort_order) VALUES
('Taux de vacance — Canton de Vaud',      0.89,  NULL,             '%',       'marche', 10),
('Taux de vacance — District de Lausanne', 0.63,  NULL,             '%',       'marche', 20),
('Taux de vacance — Ouest lausannois',     0.47,  NULL,             '%',       'marche', 30),
('Loyer median Lausanne',                  3359,  NULL,             'CHF/mois','marche', 40),
('Hausse loyers proposes CH',              2.2,   NULL,             '%',       'marche', 50),
('Taux de reference hypothecaire',         1.25,  NULL,             '%',       'marche', 60),
('Taux directeur BNS',                     0.00,  'Stable',        '%',       'marche', 70),
('Indice prix construction CRB',           116.2, 'base oct. 2020', 'pts',    'marche', 80),
('Electricite SiL — prix energie',         9.88,  '−13,1 % vs 2025','ct/kWh', 'energie', 90),
('CAD SiL',                                16.09, 'Stable',        'ct/kWh',  'energie', 100);
```

### Affichage suggere

Section "Contexte de marche" sur la page d'accueil ou sur une page dediee :
- Compteurs animes (coherent avec les KPI existants sur accueil.php)
- 3 blocs : Marche locatif | Taux et financement | Energie
- Sources citees en pied de section (BNS, OFL, OFS, Etat de Vaud, SiL, Homegate)
- Mention "Derniere mise a jour : mars 2026"

### Schema additionnel eventuel

Si la table `sill_kpi` ne contient pas de colonne `category`, ajouter :

```sql
ALTER TABLE sill_kpi ADD COLUMN category VARCHAR(50) DEFAULT 'general' AFTER unit;
ALTER TABLE sill_kpi ADD COLUMN source VARCHAR(255) DEFAULT NULL AFTER category;
ALTER TABLE sill_kpi ADD COLUMN updated_at DATE DEFAULT NULL AFTER source;
```

---

Sources : BNS, OFL/BWO, OFS, Comparis, UBS, Etat de Vaud, Homegate/SMG, SiL Lausanne, Romande Energie
