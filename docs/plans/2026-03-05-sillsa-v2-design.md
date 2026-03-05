# Design Document — sillsa.ch v2

**Date** : 5 mars 2026
**Projet** : Migration sillsa.ch depuis WordPress vers PHP natif + MariaDB
**Domaine dev** : 26.sillsa.ch (Infomaniak)
**Production** : sillsa.ch (WordPress inchange pendant le dev)

---

## 1. Vision

Site vitrine elegant de la SILL SA (Societe Immobiliere Lausannoise pour le Logement SA).
ADN : Swiss Design moderne, rigueur typographique, technologie agile et efficace.
Le site doit projeter precision, transparence et solidite institutionnelle.

---

## 2. Identite visuelle

### Palette

| Token           | Valeur      | Usage                                    |
|-----------------|-------------|------------------------------------------|
| `--bg`          | `#FFFFFF`   | Fond principal                           |
| `--bg-warm`     | `#FAFAF8`   | Degrade subtil papier photo perle        |
| `--text`        | `#1A1A1A`   | Titres, texte principal                  |
| `--text-body`   | `#333333`   | Corps de texte                           |
| `--accent`      | `#FF0000`   | Rouge SILL / rouge suisse (C0 M100 J100) |
| `--accent-hover`| `#CC0000`   | Rouge hover                              |
| `--border`      | `#E0E0E0`   | Filets, separateurs                      |
| `--muted`       | `#999999`   | Texte secondaire                         |

### Typographie — Echelle doree (ratio 1.618)

| Niveau         | Taille  | px     | Line-height | Font          |
|----------------|---------|--------|-------------|---------------|
| Corps (base)   | 11pt    | 14.67px| 23.7px      | Lato Regular  |
| Chapeau        | 17.8pt  | 23.7px | 38.4px      | Lato Regular  |
| Titre H2       | 28.8pt  | 38.4px | 46.6px      | Inter SemiBold|
| Grand titre H1 | 46.6pt  | 62.1px | 75.3px      | Inter Bold    |

- Police titres : **Inter** (sans-serif geometrique)
- Police corps : **Lato** (sans-serif humaniste)
- Chargement : Google Fonts, variable fonts, `font-display: swap`

### Grille

- CSS Grid 12 colonnes
- Max-width : 1200px
- Marges laterales : 24px mobile, 48px desktop
- Gouttiere : 24px

### Micro-animations

- Revelation au scroll : `fade-in` sobre (opacity 0 -> 1, translateY 20px -> 0)
- Compteurs KPI : animation chiffree au scroll (IntersectionObserver)
- Pas de parallaxe, pas d'effets gratuits

---

## 3. Architecture des pages

### Arborescence

```
sillsa.ch/
|-- Accueil           /
|-- La Societe        /la-societe
|   |-- Le CA         /conseil-administration
|   |-- Organisation  /organisation
|   |-- Environnement /environnement
|   +-- Societal      /aspects-societaux
|-- Portefeuille      /portefeuille
|   +-- [Immeuble]    /portefeuille/{slug}
|-- Chronologie       /chronologie
|-- Location          /location
|-- Publications      /publications
+-- [Footer]          Coordonnees, mentions legales
```

### Page d'accueil — La timeline EST le fil d'accueil

La chronologie complete de la SILL SA constitue le coeur de la page d'accueil.
C'est le fil narratif qui accueille le visiteur et raconte l'histoire de la societe.

1. **Hero** : Photo Leica plein cadre + baseline forte
2. **KPI** : 4 chiffres cles animes (compteur au scroll) sur fond `--bg-warm`
3. **Timeline complete** : Les 22 jalons (2009-2026+) en timeline verticale, alternes gauche/droite, avec filtrage par categorie. C'est le contenu principal de l'accueil.
4. **Footer** : coordonnees, liens rapides

La page `/chronologie` redirige vers l'accueil (ancre `#chronologie`) — un seul endroit pour la timeline.

### Portefeuille — Carte interactive Lausanne

- Carte SVG stylisee de Lausanne (lignes fines, style Swiss Design)
- 10 points rouges (`--accent`) positionnes geographiquement (lat/lng BDD)
- Hover point : tooltip nom + nb logements
- Clic point : panneau lateral avec fiche detail (photo, chiffres, description)
- Fallback mobile : liste avec miniatures
- Pas de dependance externe (pas de Leaflet/Mapbox)

### Chronologie — Timeline verticale

- Axe central vertical, evenements alternes gauche/droite
- Revelation progressive au scroll (IntersectionObserver)
- Filtrage par categorie (boutons toggle en haut)
- Chaque jalon : annee, titre, description, image optionnelle
- 22 jalons (2009 -> 2026+)
- Mobile : evenements empiles a gauche de l'axe

### Location

- Vitrine pour premieres locations de developpements neufs
- Section surfaces d'activites (si applicable)
- Pas de formulaire de contact
- Coordonnees telephone/email pour les demandes

### Publications

- Grille de couvertures PDF (rapports annuels 2010-2024 + ESG)
- Clic = ouverture PDF dans nouvel onglet
- Filtre par type (rapport annuel, ESG, communique)

### Conseil d'administration

- Liste des membres CA avec photo, nom, fonction
- Affichage public (decision validee)

### Pages statiques

- La Societe, Organisation, Environnement, Aspects societaux
- Contenu HTML depuis BDD (table `sill_pages`)
- Template unique `page.php`

---

## 4. Stack technique

| Composant      | Choix                        | Justification                        |
|----------------|------------------------------|--------------------------------------|
| Serveur        | Infomaniak mutualise         | Deja en place, PHP 8.2              |
| Backend        | PHP 8.2 natif (PDO)          | Zero framework = performance max     |
| BDD            | MariaDB 10.6                 | Schema 11 tables, donnees migrees    |
| Frontend       | HTML5 + CSS3 custom          | Controle total Swiss Design          |
| Typo           | Google Fonts (Inter + Lato)  | Variable fonts, optimise             |
| JS             | Vanilla JS                   | Timeline, compteurs, carte SVG       |
| Charts         | Chart.js 4.x                 | Remplace Power BI, 60kb gzip         |
| Carte          | SVG fait main                | Carte Lausanne minimaliste           |
| SEO            | Meta tags natifs             | Sitemap XML, robots.txt, Open Graph  |
| Routeur        | index.php + .htaccess        | URLs propres, rewrite Apache         |
| Admin          | Interface CRUD simple        | Login unique, pas de CMS             |

### Architecture fichiers

```
26.sillsa.ch/
|-- index.php              # Routeur principal (URL rewrite)
|-- config.php             # Connexion BDD + constantes
|-- includes/
|   |-- header.php         # Nav + meta + Google Fonts
|   |-- footer.php         # Footer + scripts
|   +-- functions.php      # Helpers (query, slugify, sanitize)
|-- templates/
|   |-- accueil.php        # Hero + KPI + Timeline resumee
|   |-- page.php           # Pages statiques generiques
|   |-- portefeuille.php   # Carte SVG interactive
|   |-- immeuble.php       # Fiche detail immeuble
|   |-- chronologie.php    # Timeline verticale complete
|   |-- location.php       # Premieres locations + surfaces
|   |-- publications.php   # Grille rapports PDF
|   +-- ca.php             # Conseil d'administration
|-- assets/
|   |-- css/style.css      # Swiss Design + echelle doree
|   |-- js/main.js         # Animations, compteurs, carte SVG
|   |-- js/chart-config.js # Configuration Chart.js
|   |-- img/               # Logo, favicons, carte SVG Lausanne
|   +-- fonts/             # Fallback local Inter/Lato
|-- uploads/               # Photos immeubles, PDF rapports
|-- admin/                 # Interface CRUD (Phase 5)
|   |-- index.php
|   |-- login.php
|   +-- edit.php
|-- .htaccess              # URL rewrite Apache
|-- robots.txt
+-- sitemap.xml
```

---

## 5. Schema BDD

11 tables MariaDB (schema existant `schema_sillsa_v2.sql`) :

1. `sill_settings` — Configuration generale (8 lignes)
2. `sill_pages` — Pages statiques (10)
3. `sill_immeubles` — Portefeuille immobilier (10) avec lat/lng
4. `sill_timeline` — Chronologie (22 jalons, 15 categories ENUM)
5. `sill_actualites` — Actualites (2)
6. `sill_medias` — Bibliotheque medias (85)
7. `sill_publications` — Rapports PDF (13)
8. `sill_kpi` — KPI publics (5+)
9. `sill_menu` — Navigation (10 entrees)
10. `sill_membres_ca` — Membres CA (a completer)
11. `sill_users` — Utilisateurs admin (1)

Scripts de migration disponibles :
- `schema_sillsa_v2.sql` (255 lignes)
- `migration_data.sql` (175 lignes)
- `migration_wp_to_sillv2.py` (reproductible)

---

## 6. Donnees existantes

- **1362 fichiers** dans wp-content/uploads/ (2012-2025)
- **85 medias** references en BDD
- **13 PDF** rapports annuels (2010-2024) + ESG
- **Photos Leica** haute qualite (credit : SILL SA / Pierre Menoux)

---

## 7. Decisions validees

| Question                          | Decision                                    |
|-----------------------------------|---------------------------------------------|
| Membres CA                        | Publics sur le site                         |
| Timeline                          | Vue exhaustive chronologique, verticale      |
| Page Location                     | Premieres locations + surfaces d'activites  |
| Formulaire de contact             | Non                                         |
| Brouillons WordPress              | Non migres                                  |
| Power BI embed                    | Remplace par Chart.js natif                 |
| Positionnement visuel             | Swiss Design moderne                        |
| Presentation portefeuille         | Carte interactive SVG Lausanne              |
| Couleur accent                    | Rouge suisse #FF0000 (C0 M100 J100)         |
| Fond                              | Blanc / degrade papier photo perle          |
| Hierarchie typo                   | Nombre d'or 1.618 comme facteur             |

---

## 8. Hors scope (v2)

- Espace membre / login public
- Formulaire de contact
- Newsletter / mailing
- Blog / articles
- Multilingue
- PWA / mode hors ligne
- Backend API REST
