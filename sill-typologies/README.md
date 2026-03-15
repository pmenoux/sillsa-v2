# SILL Typologies — Plans d'étage, Fusion 360 & impression 3D

Outils de conversion, modélisation et impression 3D des typologies de logements
SILL SA. Pipeline complet: plans architecte → Fusion 360 → Bambu Lab X1 Carbon.

## Structure

```
sill-typologies/
├── sources/                     # Fichiers sources des architectes
│   ├── dwg/                     # Fichiers AutoCAD
│   │   ├── plans/               # Plans d'étage
│   │   ├── coupes/              # Coupes
│   │   └── facades/             # Façades
│   ├── dxf/                     # Fichiers DXF
│   │   ├── plans/
│   │   ├── coupes/
│   │   └── facades/
│   ├── pdf/                     # Plans PDF
│   │   ├── plans/
│   │   ├── coupes/
│   │   └── facades/
│   └── images/                  # Scans / photos de plans
│       ├── plans/
│       ├── coupes/
│       └── facades/
├── output/                      # Fichiers générés
│   ├── dxf-fusion/              # DXF optimisés pour Fusion 360
│   ├── step/                    # Modèles 3D STEP
│   ├── pdf-print/               # Plans PDF imprimables
│   └── svg/                     # Plans SVG (web)
├── catalog/                     # Données de référence
│   ├── immeubles.json           # 10 immeubles SILL
│   └── typologies-sia.json      # Typologies SIA (studio → 5 pièces)
├── scripts/                     # Scripts de traitement
│   ├── dxf_to_fusion.py         # Conversion DXF → Fusion 360
│   ├── pdf_plan_analyzer.py     # Extraction PDF → DXF
│   ├── fusion360_typology.py    # Script API Fusion 360 + standalone DXF
│   ├── generate_3d_print.py     # Générateur STL pour impression 3D
│   ├── bambu_project.py         # Générateur .3mf pour Bambu Studio
│   └── requirements.txt         # Dépendances Python
└── templates/
    └── fusion360/               # Templates Fusion 360
```

## Démarrage rapide

### 1. Installation

```bash
cd scripts
pip install -r requirements.txt
```

### 2. Convertir vos DXF pour Fusion 360

```bash
# Un seul fichier
python scripts/dxf_to_fusion.py -i sources/dxf/plans/etage-type.dxf -o output/dxf-fusion/

# Tout un dossier + rapport de surfaces
python scripts/dxf_to_fusion.py -i sources/dxf/plans/ -o output/dxf-fusion/ --report
```

### 3. Extraire les plans depuis des PDF

```bash
# Auto-détection de l'échelle
python scripts/pdf_plan_analyzer.py -i sources/pdf/plans/plan-etage.pdf -o output/dxf-fusion/

# Forcer l'échelle 1:50
python scripts/pdf_plan_analyzer.py -i sources/pdf/plans/ -o output/dxf-fusion/ -s 1:50 --report
```

### 4. Générer des typologies DXF (standalone)

```bash
# Toutes les typologies par défaut (3.5P, 2.5P, 4.5P)
python scripts/fusion360_typology.py -t all -o output/dxf-fusion/

# Une seule
python scripts/fusion360_typology.py -t 3.5P -o output/dxf-fusion/
```

### 5. Utiliser dans Fusion 360

1. **Importer les DXF** : Fichier → Ouvrir → sélectionner un DXF de `output/dxf-fusion/`
2. **Script paramétrique** : Scripts & Add-ins → "+" → copier `scripts/fusion360_typology.py`
3. **Exécuter** : le script génère les murs 3D, cloisons et annotations directement dans Fusion

## Workflow complet

```
Plans architecte (DWG/DXF/PDF/Images)
        │
        ▼
┌─────────────────────────┐
│  dxf_to_fusion.py       │  DXF existants → nettoyage calques → DXF Fusion
│  pdf_plan_analyzer.py   │  PDF vectoriels → extraction → DXF Fusion
└─────────┬───────────────┘
          │
          ▼
┌─────────────────────────┐
│  output/dxf-fusion/     │  DXF optimisés, unités mm, calques propres
└─────────┬───────────────┘
          │
          ▼
┌─────────────────────────┐
│  Autodesk Fusion 360    │  Import DXF → Sketch → Extrude → 3D
│  fusion360_typology.py  │  OU génération paramétrique directe
└─────────┬───────────────┘
          │
          ▼
┌─────────────────────────┐
│  Export                  │  STEP, DXF, PDF, 3MF (impression 3D)
│  Impression typologies  │  Plans 2D imprimables par typologie
└─────────────────────────┘
```

## Typologies disponibles

| Code | Nom | Surface typ. | Pièces |
|------|-----|-------------|--------|
| S    | Studio | 30 m² | Séjour-cuisine, SdE |
| 2P   | 2 pièces | 50 m² | Séjour-cuisine, 1 ch., SdB |
| 2.5P | 2½ pièces | 60 m² | Séjour, cuisine, 1 ch., SdB |
| 3P   | 3 pièces | 72 m² | Séjour-cuisine, 2 ch., SdB |
| 3.5P | 3½ pièces | 85 m² | Séjour, cuisine, 2 ch., SdB, WC |
| 4P   | 4 pièces | 100 m² | Séjour-cuisine, 3 ch., SdB, WC |
| 4.5P | 4½ pièces | 115 m² | Séjour, cuisine, 3 ch., 2 SdE |
| 5P   | 5 pièces | 130 m² | Séjour, cuisine, 4 ch., 2 SdE |

Surfaces selon normes SIA 500 / SIA 180.

## Ajout de vos plans

Placez vos fichiers dans les dossiers `sources/` correspondants :

```bash
# Exemple
cp ~/Desktop/plan-bonne-esperance.dxf sources/dxf/plans/
cp ~/Desktop/coupe-AA.pdf sources/pdf/coupes/
cp ~/Desktop/facade-sud.jpg sources/images/facades/
```

Puis lancez la conversion adaptée.

## Impression 3D — Bambu Lab X1 Carbon

### Générer les maquettes STL

```bash
# Toutes les typologies, échelle 1:100
python scripts/generate_3d_print.py -t all -s 1:100 -o output/stl/

# Une typologie spécifique, multi-couleur AMS
python scripts/generate_3d_print.py -t 3.5P -s 1:100 --multi-color -o output/stl/

# Échelle 1:50 pour plus de détails (petits logements)
python scripts/generate_3d_print.py -t S -s 1:50 --multi-color -o output/stl/
```

### Générer des projets Bambu Studio (.3mf)

Les fichiers .3mf incluent les paramètres d'impression et les couleurs AMS pré-configurés.

```bash
# Projet prêt à imprimer, qualité présentation
python scripts/bambu_project.py -t 3.5P -s 1:100 -p presentation -o output/3mf/

# Toutes les typologies, brouillon rapide
python scripts/bambu_project.py -t all -s 1:100 -p draft -o output/3mf/

# Mode mono-couleur (sans AMS)
python scripts/bambu_project.py -t 4.5P -s 1:100 --mono -o output/3mf/
```

### Niveaux de qualité

Chaque typologie est associée à un niveau de qualité qui se traduit
visuellement dans la maquette imprimée:

| Niveau | Description | Hauteur murs | Détails | Typologies |
|--------|-------------|-------------|---------|------------|
| **Standard** | Logement subventionné (LLM) | 10 mm | Murs + cloisons | S, 2P, 3P |
| **Confort** | Loyer contrôlé (LLA) | 12 mm | + sanitaires, cuisine | 2.5P, 3.5P, 4P |
| **Premium** | Loyer libre / PPE | 15 mm | + mobilier, équipements | 4.5P, 5P |

### Configuration AMS multi-couleur

| Slot AMS | Couleur | Partie |
|----------|---------|--------|
| 1 | Blanc | Dalle de sol |
| 2 | Gris foncé | Murs extérieurs |
| 3 | Gris clair | Cloisons intérieures |
| 4 | Rouge SILL (#FF0000) | Sanitaires & cuisine |

### Presets d'impression

| Preset | Couche | Vitesse | Usage |
|--------|--------|---------|-------|
| `draft` | 0.28 mm | 250 mm/s | Vérification rapide |
| `standard` | 0.20 mm | 200 mm/s | Usage courant |
| `presentation` | 0.12 mm | 100 mm/s | Présentation client |
| `detail` | 0.08 mm | 60 mm/s | Ultra-détail |

### Paramètres recommandés Bambu Studio

- **Plaque**: Textured PEI
- **Filament**: PLA Matte (meilleur rendu) ou PLA Basic
- **Remplissage**: 15-20%
- **Support**: Non nécessaire
- **Brim**: Recommandé pour les petites échelles (1:100+)

## Workflow complet

```
Plans architecte (DWG/DXF/PDF/Images)
        │
        ▼
┌─────────────────────────────┐
│  dxf_to_fusion.py           │  DXF existants → nettoyage → DXF Fusion
│  pdf_plan_analyzer.py       │  PDF vectoriels → extraction → DXF
└─────────┬───────────────────┘
          │
          ├──────────────────────────────────────┐
          ▼                                      ▼
┌─────────────────────────┐    ┌─────────────────────────────┐
│  Autodesk Fusion 360    │    │  generate_3d_print.py       │
│  fusion360_typology.py  │    │  → STL par typologie        │
│  Import DXF → 3D        │    │  → Niveaux de qualité       │
└─────────┬───────────────┘    └─────────┬───────────────────┘
          │                              │
          ▼                              ▼
┌─────────────────────────┐    ┌─────────────────────────────┐
│  Export STEP/DXF/PDF    │    │  bambu_project.py           │
│  Plans 2D imprimables   │    │  → .3mf prêt pour Bambu     │
└─────────────────────────┘    │  → Couleurs AMS configurées │
                               │  → Paramètres d'impression  │
                               └─────────┬───────────────────┘
                                         │
                                         ▼
                               ┌─────────────────────────────┐
                               │  Bambu Lab X1 Carbon        │
                               │  Impression multi-couleur   │
                               │  Maquettes typologiques     │
                               └─────────────────────────────┘
```
