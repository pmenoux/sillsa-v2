# SILL SA — Contexte Projet

## Répertoire de travail
Tout le travail se fait EXCLUSIVEMENT dans SILL_SA/SILLwebsite2026/site/

## Stack technique
- PHP vanilla + MySQL (MariaDB via PDO, BDD `sillsa_v2`)
- CSS vanilla — zéro framework
- JS vanilla — zéro dépendance (Chart.js optionnel pour graphiques)
- Hébergement : Infomaniak
- Site de dev : 26.sillsa.ch

## Design System Swiss Style
- Rouge SILL : #FF0000 (C0 M100 J100 N0 — rouge helvétique pur)
- Noir : #000000
- Blanc : #FFFFFF
- Gris perlé chaud : #F5F0EB
- Bleu Swiss : #0047BB
- Jaune Swiss : #FFD700
- Typographie : Helvetica Neue, lettres capitales, espacées
- Navbar : gris perlé #F5F0EB, hauteur 100px, bordure inférieure 2px noir
- Logo hauteur : 70px

## Figma
- Fichier : https://www.figma.com/design/uXwv2csyvL2CFNr2xVIRDw/SILL-SA-Refonte-2026

## Règles
- Ne jamais travailler hors de ce répertoire
- Toujours mettre à jour CHANGELOG.md après chaque modification

---

## Structure des dossiers

```
site/
├── .htaccess              # Rewrite → index.php, cache, sécurité, gzip
├── robots.txt             # Allow all + sitemap
├── config.php             # Constantes BDD + SITE_URL + connexion PDO
├── index.php              # Front controller / routeur principal
├── sitemap.php            # Sitemap XML dynamique
├── CLAUDE.md
├── CHANGELOG.md
│
├── includes/
│   ├── header.php         # <!DOCTYPE>, <head>, navbar dynamique (menu BDD)
│   ├── footer.php         # Footer 3 colonnes + chargement JS
│   └── functions.php      # Helpers : query(), queryOne(), setting(), getMenu(),
│                          #   getPage(), mediaUrl(), e(), isActive()
│
├── templates/
│   ├── accueil.php        # Homepage : hero + KPI animés + timeline filtrable
│   ├── page.php           # Template générique (contenu depuis sill_pages)
│   ├── ca.php             # Conseil d'administration (grille membres)
│   ├── portefeuille.php   # Carte SVG interactive + grille mobile
│   ├── immeuble.php       # Fiche détail immeuble (/portefeuille/{slug})
│   ├── publications.php   # Grille PDF filtrables par type
│   ├── location.php       # Contact location + contenu BDD
│   ├── chronologie.php    # Redirect 301 → /#chronologie
│   └── 404.php            # Page erreur 404
│
├── assets/
│   ├── css/
│   │   └── style.css      # 1333 lignes — CSS complet mobile-first
│   ├── js/
│   │   ├── main.js        # Nav mobile, scroll reveal, compteurs KPI,
│   │   │                  #   carte SVG, dropdowns, smooth scroll
│   │   └── chart-config.js # Module Chart.js : doughnut, line, bar
│   └── img/
│       ├── logo_sill.svg  # Logo SVG (utilisé dans header.php)
│       ├── logo_sill.png  # Logo PNG fallback
│       └── carte-lausanne.svg # Carte SVG de Lausanne
│
└── uploads/               # Medias migrés depuis WordPress
    ├── 2012/ à 2025/      # Structure année/mois héritée de WP
    ├── photo-gallery/      # Galerie photos
    └── (1361 fichiers : 1114 JPG, 96 PDF, 79 PNG)
```

## Tokens CSS (style.css :root)

### Couleurs
| Variable | Valeur | Usage |
|---|---|---|
| `--color-bg` | #FFFFFF | Fond principal |
| `--color-bg-warm` | #FAFAF8 | Fond sections alternées (KPI, etc.) |
| `--color-heading` | #1A1A1A | Titres |
| `--color-body` | #333333 | Texte courant |
| `--color-accent` | #FF0000 | Rouge SILL — liens, KPI, dots timeline |
| `--color-accent-hover` | #CC0000 | Rouge hover |
| `--color-border` | #E0E0E0 | Bordures, séparateurs |
| `--color-muted` | #999999 | Texte secondaire |
| `--color-white` | #FFFFFF | Blanc |
| `--color-dark` | #1A1A1A | Footer, tooltips |

### Typographie (Golden Ratio, base 14.67px)
| Variable | Valeur | Usage |
|---|---|---|
| `--font-body` | 'Lato', sans-serif | Corps de texte |
| `--font-heading` | 'Inter', sans-serif | Titres, nav, labels |
| `--fs-base` / `--lh-base` | 14.67px / 23.7px | Texte courant |
| `--fs-chapeau` / `--lh-chapeau` | 23.7px / 38.4px | Introductions |
| `--fs-h4` / `--lh-h4` | 18px / 27px | Sous-titres |
| `--fs-h3` / `--lh-h3` | 23.7px / 32px | Titres section |
| `--fs-h2` / `--lh-h2` | 38.4px / 46.6px | Titres page |
| `--fs-h1` / `--lh-h1` | 62.1px / 75.3px | Titre hero |

### Spacing & Layout
| Variable | Valeur | Usage |
|---|---|---|
| `--grid-max` | 1200px | Largeur max container |
| `--narrow-max` | 720px | Largeur contenu texte |
| `--gutter` | 24px | Gouttière grille |
| `--margin-mobile` | 16px | Padding latéral mobile |
| `--margin-tablet` | 24px | Padding latéral tablette |
| `--margin-desktop` | 48px | Padding latéral desktop |
| `--header-h` | 72px | Hauteur header (A MIGRER → 100px) |

### Divers
| Variable | Valeur |
|---|---|
| `--radius` | 8px |
| `--radius-sm` | 4px |
| `--shadow` | 0 2px 8px rgba(0,0,0,0.08) |
| `--shadow-lg` | 0 4px 20px rgba(0,0,0,0.10) |
| `--transition` | 0.3s ease |

## Référencement des assets

- **CSS/JS/Images** : chemins absolus via `SITE_URL` (défini dans config.php)
  - `<?= SITE_URL ?>/assets/css/style.css`
  - `<?= SITE_URL ?>/assets/js/main.js`
  - `<?= SITE_URL ?>/assets/img/logo_sill.svg`
- **Medias uploadés** : via `mediaUrl($id)` qui convertit les chemins WP
  - `/wp-content/uploads/` → `/uploads/`
  - Retourne `SITE_URL . $path`
- **Fonts** : Google Fonts CDN (Inter + Lato)
  - `fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Lato:wght@300;400;700`

## Architecture PHP

### Routeur (index.php)
- Front controller : `.htaccess` redirige tout vers `index.php?route=...`
- Parse `$_GET['route']` en segments → `$page` + `$slug`
- Table de routes statiques : accueil, la-societe, portefeuille, publications, location, etc.
- Route dynamique : `/portefeuille/{slug}` → `templates/immeuble.php`
- API AJAX : `/api/immeuble/{slug}` → HTML partiel pour panneau carte
- Chaîne d'inclusion : `header.php` → `template` → `footer.php`

### Tables BDD identifiées
| Table | Usage |
|---|---|
| `sill_settings` | Paramètres site (tagline, contact, meta) |
| `sill_menu` | Items navigation (parent_id, sort_order, is_active) |
| `sill_pages` | Pages statiques (slug, title, content, meta) |
| `sill_immeubles` | Immeubles (slug, nom, adresse, nb_logements, coordonnées carte) |
| `sill_medias` | Medias (filepath, alt_text, credit) |
| `sill_kpi` | Chiffres clés homepage (value_num, value_text, unit, label) |
| `sill_timeline` | Événements chronologie (event_date, category, image_id) |
| `sill_membres_ca` | Membres conseil administration (prenom, nom, fonction, bio) |
| `sill_publications` | Publications PDF (annee, type, pdf_path, cover_image_id) |

### Classes CSS actives (navigation)
- `is-active` : item de nav actif (pas `active`)
- `is-open` : menu mobile ouvert, dropdown ouvert
- `revealed` : élément apparu (scroll reveal)
- `filter-btn.is-active` : filtre timeline/publications sélectionné

## Icônes et éléments graphiques

- **Aucune bibliothèque d'icônes** (pas de Font Awesome, Heroicons, etc.)
- Icônes en SVG inline :
  - Chevron scroll hero : `<svg>` inline dans `accueil.php` (polyline 6,9 → 12,15 → 18,9)
  - Flèche retour : CSS `::before` avec caractère Unicode `\2190` (←)
  - Flèche lien : CSS `::after` avec caractère Unicode `\2192` (→)
  - Bouton fermer panneau : caractère HTML `&times;` (×)
- Hamburger menu : 3 `<span>` stylés en CSS dans `.nav-toggle`
- Points carte : cercles CSS (`.point-circle` + `.point-inner`)
- Timeline dots : cercles CSS (`.timeline-dot`)

## Breakpoints responsive
| Breakpoint | Cible |
|---|---|
| < 768px | Mobile (défaut) |
| >= 768px | Tablette |
| >= 1024px | Desktop (nav horizontale, carte interactive) |
| >= 1200px | Large desktop |

## Écarts design system actuel vs cible Swiss Style
- `--header-h` : actuellement 72px → cible 100px
- Fond header : actuellement `--color-white` → cible `#F5F0EB`
- Bordure header : actuellement 1px `--color-border` → cible 2px `#000000`
- Logo : actuellement 40px → cible 70px
- Fonts nav : actuellement Inter → cible Helvetica Neue
- Couleurs absentes du CSS : `#0047BB` (Bleu Swiss), `#FFD700` (Jaune Swiss)
