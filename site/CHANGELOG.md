# CHANGELOG — SILL SA Refonte 2026

## [2026-03-12]
### Fix — Menu mobile dropdown "À propos"
- Fix mismatch classe CSS `is-open` vs JS `dropdown-open` — le JS utilisait `dropdown-open` mais le CSS attendait `is-open` sur `.has-dropdown`
- Fix seuil mobile `isMobile()` : 768px → 1024px pour correspondre au breakpoint CSS de la nav horizontale
- Le menu dropdown fonctionne maintenant correctement sur mobile et tablette

### KPI — Injection données Excel
- Extraction de 21 KPIs depuis `Dashboard KPI SILL SA.xlsx` (données 31.12.2025 / état locatif 2026)
- Création du script SQL `Datas actuelles/files/update_kpi_from_excel.sql`
- Injection en production via SSH paramiko (session unique pour éviter ban Infomaniak)
- 4 KPIs publics (is_public=1) : Immeubles (10), Lots (834), Valeur DCF (326.7 M CHF), État locatif (15.1 M CHF)
- 17 KPIs masqués (is_public=0) : activables depuis future interface admin
- Fix template accueil.php : nettoyage des valeurs DECIMAL(15,2) → entiers sans `.00`
- Déploiement du template corrigé sur le serveur

### Infrastructure
- Découverte tenant Infomaniak SILL SA (hixn) distinct du tenant Next Level (gfeu)
- Configuration SSH user `hixn_sillclaude` avec mot de passe
- Mise à jour mémoire credentials Infomaniak (2 tenants documentés)
- Mise à jour page Notion avec credentials SSH/DB SILL SA

## [2026-03-07]
### Initialisation
- Mise en place projet PHP/MySQL Swiss Style
- Définition design system complet (palette, typo, spacing)
- Création navbar.html — premier prototype navbar
- Configuration CLAUDE.md global WEBDESIGN + projet

### Documentation technique
- Audit complet de la structure site/ (18 fichiers PHP, 1 CSS, 2 JS, 3 images, 1361 uploads)
- CLAUDE.md enrichi : arborescence, tokens CSS, architecture PHP, tables BDD, référencement assets, icônes, breakpoints
- Identification des écarts design system actuel vs cible Swiss Style
