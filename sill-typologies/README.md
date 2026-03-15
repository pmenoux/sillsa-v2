# SILL Typologies — Plans d'étage & modélisation Fusion 360

Outils de conversion et modélisation des typologies de logements SILL SA
pour Autodesk Fusion 360.

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
