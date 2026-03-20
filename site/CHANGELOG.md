# CHANGELOG — SILL SA Refonte 2026

## [2026-03-20] — Répartition locative admin + correction Dashboard KPI

### Page admin Répartition locative
- Nouvelle page `sill-admin/repartition.php` — tableau de contrôle par affectation
- Table `sill_repartition_locative` (auto-créée) : affectation, nb_logements, surface, loyer
- Parts calculées automatiquement sur le nombre de logements (pas le loyer)
- Import CSV + édition manuelle + ajout de lignes
- Navigation ajoutée dans la topbar admin (entre KPIs et Pages)
- Script SQL `004_repartition_locative.sql` avec données initiales (6 affectations, 834 logements)

### Correction Dashboard KPI SILL SA.xlsx
- Etudiants : 42 → 141 logements (Berne 309/311/313 comptés comme 1 lot au lieu de 38/37/27)
- Fusion « activités » (6 lots) dans « Activité » (21 → 27 lots) — erreur de casse
- Colonne « Part du loyer » recalculée en « Part (logements) » — base = nb logements

## [2026-03-19] — Authentification Azure AD + fix backend URLs

### Authentification Microsoft Azure AD (Entra ID)
- Connexion SSO via Microsoft pour tous les collaborateurs SILL SA
- Deux groupes de sécurité : `SILL-Backend-Admin` (CRUD + suppression) / `SILL-Backend-Editeur` (CRUD sans suppression)
- Auto-provisioning des utilisateurs au premier login Microsoft
- Login mot de passe conservé en fallback (compte pmenoux)
- Bouton "Se connecter avec Microsoft" sur la page login
- Badge rôle (admin/editor) affiché dans la topbar
- Boutons de suppression masqués + guards serveur pour le rôle editor
- Fichiers impactés : auth.php, login.php, index.php, layout.php, azure-config.php (NEW)
- Protection canDelete() ajoutée dans : immeubles, pages, publications, timeline, menu, kpi

### Fix backend URLs
- settings.php : `?section=settings` corrigé en `?page=settings`
- menu.php : 18 URLs corrigées — slash manquant + `?section=menu` → `?page=menu`
- `.gitignore` : ajout `site/sill-admin/azure-config.php`

## [2026-03-18] — Acquisitions par préemption + Timeline harmonisée

### 3 immeubles acquis par préemption (préavis N° 2025/41)
- Béthusy 86-88 (22 logements), Jomini 10-12-14 (60 logements), Égralets 1-3 (30 logements)
- Insérés en BDD avec `is_active = 0` — activation prévue le 1er avril 2026
- Colonne `type_projet` ajoutée à `sill_immeubles` (construction / renovation)
- Table `sill_journal` créée pour le futur journal de rénovation
- Positions SVG ajoutées sur la carte du portefeuille
- Label quartier « Béthusy » ajouté sur la carte
- Répertoires media créés sur le serveur

### Carte portefeuille
- Label « Gare CFF » sur fond rouge suisse (#FF0000) en remplacement de l'ancien label gris

### Timeline
- Événement ID 20 mis à jour : approbation du préavis N° 2025/41 (17 mars 2026)
- 22 textes de description harmonisés (~180-200 caractères, ton institutionnel uniforme)

### Script d'activation 1er avril
- `_activate_april1.php` prêt : active les 3 immeubles + met à jour les KPI (847 logements, 13 immeubles)

## [2026-03-18] — Environnement + Location redesign
### Page Environnement — template dédié
- Nouveau template `environnement.php` (sortie du template générique `page.php`)
- Image principale en en-tête 16:9 (`aspect-ratio: 16/9`, `object-fit: cover`)
- Graphique scatter affiché en taille complète (plus de troncature `max-height: 360px`)
- Override CSS `.env-chart img` : `max-height: none`, `object-fit: contain`

### Page Location — restructuration
- Suppression de la section "Premières locations" (plus nécessaire)
- Nouvelle section "Être locataire" (relation de proximité, candidatures)
- Section "Surfaces d'activités" conservée
- Layout 2 colonnes Swiss Design : sidebar contact + body
- CSS location complet : mobile-first, grid 1/3 + 2/3 sur desktop
- Sidebar sticky avec filet rouge, typographie Swiss

## [2026-03-17] — Audit graphique Swiss Design + fix carte mobile
### Analyse complète du site 26.sillsa.ch
- Audit graphique Frontend Designer : 8 pages analysées (captures dans `analysis/`)
- 3 diagrammes FigJam créés : architecture site, design system tokens, audit Swiss Design
- Rapport complet : `analysis/ANALYSE-GRAPHIQUE-26-SILLSA-CH.md`
- Constat : le design system Swiss Style est correctement implémenté (échelle typo Golden Ratio, palette disciplinée, Helvetica Neue uppercase)
- Les problèmes visuels initiaux (contenus invisibles, charts vides) étaient des artefacts de capture — tout fonctionne correctement avec scroll (IntersectionObserver)

### Fix : carte SVG portefeuille visible sur mobile
- CSS : `.portefeuille-layout` passe de `display: none` à `display: block` sur mobile
- CSS : `.map-info-panel` caché sur mobile (panneau latéral inutile en mobile)
- CSS : points carte agrandis à 36px sur mobile (cible touch)
- CSS : espacement ajouté entre carte et grille mobile
- JS : détection mobile dans map interaction — navigation directe vers fiche immeuble (pas de fetch AJAX)
- Desktop inchangé : layout flex + panneau latéral AJAX préservé

## [2026-03-16] — Session 4
### Harmonisation Portefeuille + Immeuble — style Swiss
- Portefeuille : `.chapeau` → `.page-chapeau` (cohérence avec contexte)
- Portefeuille mobile : gap 64px → 32px, cards ratio 16:9 au lieu de hauteur fixe 180px
- Quartier labels : ajout margin-top 8px pour respiration
- Carte SVG : suppression bordure, fond allégé #FAFAF8
- Immeuble hero : suppression border-radius, max-height responsive via clamp()
- Immeuble : layout 2 colonnes asymétriques Müller-Brockmann (1/3 meta + 2/3 texte) desktop
- Immeuble sidebar : meta empilées verticalement (légendes Swiss), border-top 2px rouge
- Meta labels : letter-spacing 0.05em → 0.12em (aligné sur contexte)
- Back-link : font-weight 500 → 400 (Swiss léger)
- Description : séparée du chapeau, font-size base au lieu de chapeau
- h1 immeuble : font-weight 300 explicite
- Ajout quartier dans les meta si disponible
- Tablette : sidebar 3 colonnes, cards ratio 3:2

### Fond gris perlé global + typographie Musica Viva
- Fond site entier : `--color-bg` #FFFFFF → #F5F0EB (gris perlé chaud, ref. Müller-Brockmann)
- Navigation : `--color-nav-bg` → #F5F0EB (unifié)
- Label énergie : fond vert → jaune Swiss #FFD700 (palette gris/rouge/jaune)
- Sidebar immeuble : border-top noir → rouge accent
- Rich-text immeuble : interligne 1.8, espacement paragraphes 1.6em
- Signature architecte : extraite du texte, rendue séparément — petites capitales, filet, letter-spacing 0.12em
- Images rich-text : suppression border-radius (Swiss = angles vifs)
- Navigation : +30px respiration verticale (padding 15px top/bottom)
- Texte Falaises : restructuré en 6 paragraphes thématiques (était un bloc monolithique)
- Signature architecte Falaises : "Pour MPH Architectes / Olaf Hunger" extraite et stylée
- Colonne `loyer_mix` JSON ajoutée à sill_immeubles — répartition types de loyers par projet
- Mini-barre CSS proportionnelle dans sidebar : rouge (subventionné), gris (contrôlé), jaune (libre), noir (étudiants)
- Légende mini avec dots colorés et nombre d'unités par type
- 10 immeubles peuplés avec données loyer_mix

## [2026-03-16] — Session 3
### Page /marche — Répartition des types de loyers
- Barre empilée CSS : LLM 303 lots (36%), LLA 305 lots (37%), LM 58 lots (7%), Étudiants 42 lots (5%)
- Légende chiffrée 4 colonnes avec dots colorés, nombre de lots et pourcentage
- Note LUP (LLM + LLA) : 608 lots — 73% du parc
- Source : Dashboard KPI SILL SA.xlsx → États locatifs agrégés 2026

### Page /marche — Graphiques d'évolution
- Chart.js 4 chargé depuis CDN (uniquement sur /marche)
- Graphique 1 : Taux hypothécaire de référence OFL — stepped line depuis 2008 (3.50% → 1.25%)
- Graphique 2 : Indice des prix de la construction CRB/OFS — Hochbau base oct. 2020 = 100 (2015-2025)
- Design Swiss : pas de légende, tooltips noirs, grille subtile, courbes fines
- Responsive : 1 colonne mobile, 2 colonnes desktop

## [2026-03-16] — Session 2
### Restructuration portefeuille par quartiers
- "Les Fiches-Nord" (id=7) désactivé — c'est un quartier, pas un immeuble
- Colonne `quartier` ajoutée à `sill_immeubles` (6 quartiers : Fiches-Nord, Plaines-du-Loup, Falaises, Sallaz, Sous-Gare, En Cojonnex)
- Carte SVG : labels quartier en rouge discret (11px, #CC0000, weight 400), suppression du point fictif
- Grille mobile : immeubles groupés par quartier avec header rouge
- Chapeau : "Notre patrimoine de 9 développements réalisés à Lausanne"

### Page /marche — Section Positionnement SILL SA
- 6 KPI SILL en BDD (category='sill') : loyer net 252 CHF/m²/an, IDC 218 MJ/m², CO₂ 4.56 kgCO₂/m², 73% logements utilité publique, 66'956 m², charges 60 CHF/m²/an
- Données énergie/CO₂ : rapport Signa-Terre 2024 (audité PwC ISAE 3000)
- Section séparée par filet noir 2px, 3 colonnes : Social & loyers / Énergie & climat / Patrimoine

### Corrections données marché
- Électricité SiL : 9.88 → 29.93 ct/kWh (tarif total TTC, pas énergie seule)
- CAD : 16.09 → 17.39 ct/kWh (TTC avec TVA 8.1%)
- Gaz : value_text → value_num 6.7% avec contexte "Hausse dès oct. 2025"
- Loyer : 3'359 CHF/mois → 320 CHF/m²/an (médian 3.5 pces Lausanne, Comparis 2023)
- Taux référence : affiche 1.25 (2 décimales) grâce à kpiFormat()
- Label "Hausse loyers proposés" → "Hausse des loyers à la relocation"
- Sources individuelles sous chaque chiffre en italique gris
- Préfixes +/− sur les variations (data-prefix dans JS)
- Séparateurs de milliers suisses (apostrophe ʼ) via swissFormat() dans main.js

### Revue typographique — échelle φ complète
- Échelle Golden Ratio : 11, 13, 16, 20, 26, 33, 42, 68px — aucune taille hors grille
- h1 : weight 200, h2 : weight 300, KPI values : weight 200
- Page intérieures h1 : fs-h2 (max 42px) au lieu de fs-h1 (120px)
- Suppression ombres, pills arrondies → bordures fines, angles vifs
- Navbar et footer : fond blanc pur
- kpiFormat() : détection automatique des décimales

## [2026-03-16]
### Page dédiée Marché & Conjoncture
- Nouvelle page `/marche` (templates/marche.php) — indicateurs conjoncturels en pleine page
- 3 colonnes : Marché locatif / Taux et financement / Énergie
- Route ajoutée dans index.php, entrée "Marché" dans sill_menu
- Accueil : teaser compact (3 KPI + lien "Voir les indicateurs") remplace la section complète

### Fix KPI accueil — séparation patrimoine / marché
- Requête KPI filtrée `category = 'patrimoine'` — ne montre plus les KPI marché dans la grille principale
- Suppression de la migration auto ALTER TABLE dans accueil.php (déjà appliquée en BDD)

### Revue typographique Swiss Design — finesse et élégance
- h1 : font-weight 700 → 200 (ultralight, la taille fait le travail)
- h2 : font-weight 700 → 300 (light)
- KPI values : font-weight 700 → 200, taille réduite clamp(36px→56px) au lieu de clamp(48px→80px)
- KPI labels : 12px → 11px, weight 500, letter-spacing 0.12em (plus de contraste hiérarchique)
- CA noms : font-weight 700 → 300 (les noms affirment sans crier)
- Timeline dates : 700 → 300, 24px → 20px
- Timeline cards : suppression box-shadow, remplacement par border-top filet fin
- Filter buttons : suppression border-radius 999px (pills) → 0 (angles vifs Swiss)
- Publication cards : suppression box-shadow → bordure fine, hover sur border-color
- Label énergie / publication type : suppression pills arrondies → rectangulaires
- Immeuble cards : suppression ombres → bordure fine
- btn-link : font-weight 600 → 400
- Principe : plus le texte est grand, plus il est fin (Müller-Brockmann)

### Section Contexte de marché — données initiales
- ENUM sill_kpi étendu : ajout catégories 'marche' et 'energie'
- 9 indicateurs : BNS, OFL, OFS, Homegate, SiL Lausanne (mars 2026)

## [2026-03-12]
### Refonte Swiss Design — Chantiers 1+2+3
- NAVBAR NOIRE : fond #000000, texte blanc, logo SVG, plus de bordure rouge
- TYPOGRAPHIE : Helvetica Neue system stack, suppression Google Fonts (Inter/Lato)
- Titres uppercase, letter-spacing 0.08em, tailles radicales (48-120px)
- Corps font-weight 300, line-height 1.6
- GRILLE : max-width 1400px, marges 80px desktop, gouttiere 20px
- KPI : stats editoriales (chiffre 80px noir, label 12px gris dessous)
- FOND : blanc pur, suppression gradient radial
- FOOTER : fond noir, texte blanc
- Suppression formes Bauhaus (cercle bleu, triangle jaune, carre rouge)
- Suppression border-radius (0 partout, angles vifs Swiss)
- Spacing scale multiples de 8px
- Logo unifie en SVG

### En Cojonnex — correction nom et slug
- Slug BDD corrige : encogenet-chalet-a-gobet → en-cojonnex
- Nom affiche : En Cojonnex (nom officiel RA2024)
- Label carte SVG mis a jour

### Donnees RA2024 — logements et adresses
- nb_logements mis a jour pour les 10 immeubles (total 796)
- Adresses officielles du rapport annuel 2024
- Fiches 8/9 : 99 (pas 131), Falaises : 94 (pas 194), En Cojonnex : 102

### Carte portefeuille — Correction tracé M2
- Tracé M2 corrigé : Ouchy → Gare → Flon → Riponne → Bessières → Sallaz → CHUV → Croisettes
- Le M2 passe maintenant par la Sallaz et monte vers les Fiches (réalité géographique)
- Ancien tracé allait vers Plaines-du-Loup (faux — ce serait le futur M3)
- Label M2 repositionné à côté de la Sallaz

### Carte portefeuille — Mosaïque vignettes dans panneau droit
- Panneau droit : mosaïque 3 colonnes des photos d'immeubles (état par défaut)
- Vignettes cliquables : chargent la fiche immeuble comme un clic sur la carte
- Effet hover : scale + suppression du filtre grayscale
- Bouton fermer restaure la mosaïque
- Zones urbaines (Vieille ville, Flon, Sous-Gare) agrandies x2-3 pour plus de présence

### Carte portefeuille — Vrai plan de Lausanne en fond
- SVG enrichi avec réseau de rues dense (axes principaux, rues secondaires, maillage fin)
- Parcs et espaces verts en vert très subtil (Sauvabelin, Mon-Repos, Montbenon, Milan, Jorat)
- Rivières/vallées historiques (Flon, Louve, Vuachère) en tirets bleus
- Autoroute A9 en traversée
- Repères urbains discrets (Cathédrale, Ouchy, Stade, Pully, Renens, Sauvabelin)
- Zones urbaines denses (Vieille ville, Flon) en aplats légers

### Carte portefeuille — Impact visuel Swiss
- Labels de quartiers SILL (Sallaz, Faverges, Fiches-Nord, Plaines-du-Loup, Bonne-Esp., Chalet-a-Gobet) en `.carte-label-district` : 14px, noir #1A1A1A, bold, letter-spacing 0.15em
- Labels secondaires (Centre, Malley) restent discrets en `.carte-label` : 11px, #999
- Label lac : 14px, bleu plus profond #7FAFC4, letter-spacing 0.2em
- Infrastructure adoucie (routes #E4E4E4, rail #C8C8C8) pour faire ressortir les labels et points
- Container carte `.carte-wrapper` : fond #F8F8F6, bordure, border-radius 8px, padding
- Animation pulse subtile sur les points immeubles (scale 1→1.1, 3s loop)

### Filet rouge Swiss sous la navbar
- Ajout `border-bottom: 3px solid var(--color-accent)` sur `.site-header`
- Filet rouge #FF0000 visible entre la navbar et le contenu, renforçant l'identité Swiss Design

### Refonte page "La Société" — Swiss Design layout
- Suppression du h2 "La Société" en doublon (déjà dans le h1 page-header)
- Nouveau template dédié `la-societe.php` remplaçant le générique `page.php`
- Layout 2 colonnes sur desktop : texte à gauche, image à droite (sticky)
- Premier paragraphe en style chapeau (taille élevée, font-weight 300)
- Section opérations avec bordure rouge accent à gauche
- Carrousel horizontal "Nos réalisations" avec photos des immeubles (scroll snap, swipe mobile)
- Responsive : 1 colonne mobile, 2 colonnes >= 1200px

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
