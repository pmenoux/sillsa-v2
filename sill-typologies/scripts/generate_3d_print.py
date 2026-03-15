#!/usr/bin/env python3
"""
SILL Typologies — Générateur de maquettes 3D imprimables

Génère des modèles STL de plans d'étage pour impression 3D
sur Bambu Lab X1 Carbon (plateau 256×256×256 mm).

Chaque maquette montre:
    - Dalle de sol avec gravure du nom de l'immeuble
    - Murs extérieurs (épaisseur différenciée)
    - Cloisons intérieures
    - Ouvertures (portes et fenêtres en creux)
    - Gravure des noms de pièces et surfaces sur la dalle
    - Niveaux de qualité visuellement distincts (épaisseurs, détails)

Impression multi-couleurs (AMS Bambu Lab):
    - Plaque 1 (blanc) : dalle de sol + gravure texte
    - Plaque 2 (gris foncé) : murs extérieurs
    - Plaque 3 (gris clair) : cloisons intérieures
    - Plaque 4 (couleur accent) : éléments sanitaires / cuisine

Échelles supportées:
    - 1:100 → idéal pour les grands logements (4.5P, 5P)
    - 1:75  → bon compromis
    - 1:50  → détaillé, pour les petits logements (studio, 2P)

Usage:
    python generate_3d_print.py --typology 3.5P --scale 1:100 --output output/stl/
    python generate_3d_print.py --typology all --scale 1:100 --output output/stl/
    python generate_3d_print.py --typology 3.5P --scale 1:50 --multi-color --output output/stl/
"""

import argparse
import json
import math
import struct
import sys
from dataclasses import dataclass, field
from pathlib import Path


# ─── Constantes d'impression ────────────────────────────────

# Bambu Lab X1 Carbon
BUILD_PLATE_X_MM = 256
BUILD_PLATE_Y_MM = 256
BUILD_PLATE_Z_MM = 256

# Dimensions du modèle imprimé
SLAB_THICKNESS_MM = 2.0        # Épaisseur de la dalle (base)
WALL_EXT_HEIGHT_MM = 12.0      # Hauteur murs extérieurs imprimés
WALL_INT_HEIGHT_MM = 8.0       # Hauteur cloisons
WALL_EXT_THICK_MM = 2.0        # Épaisseur murs extérieurs (imprimés)
WALL_INT_THICK_MM = 1.2        # Épaisseur cloisons (imprimées)
ENGRAVE_DEPTH_MM = 0.4         # Profondeur de gravure texte
DOOR_WIDTH_REAL_MM = 900       # Largeur porte réelle
WINDOW_WIDTH_REAL_MM = 1200    # Largeur fenêtre réelle
MARGIN_MM = 5.0                # Marge autour du modèle

# Niveaux de qualité SILL
QUALITY_LEVELS = {
    "standard": {
        "label": "Standard",
        "description": "Logement subventionné (LLM)",
        "wall_ext_height": 10.0,
        "wall_int_height": 7.0,
        "detail_level": 1,       # Murs + cloisons de base
        "color_accent": None,
    },
    "confort": {
        "label": "Confort",
        "description": "Loyer contrôlé (LLA)",
        "wall_ext_height": 12.0,
        "wall_int_height": 8.0,
        "detail_level": 2,       # + sanitaires, cuisine
        "color_accent": "cuisine",
    },
    "premium": {
        "label": "Premium",
        "description": "Loyer libre / PPE",
        "wall_ext_height": 15.0,
        "wall_int_height": 10.0,
        "detail_level": 3,       # + mobilier, balcon, loggia
        "color_accent": "all",
    },
}


# ─── Typologies détaillées pour impression 3D ────────────────

TYPOLOGIES_3D = {
    "S": {
        "nom": "Studio",
        "quality": "standard",
        "largeur_mm": 5500,
        "profondeur_mm": 5500,
        "pieces": [
            {"nom": "Séjour/Cuisine", "x": 0, "y": 0, "w": 5500, "h": 4000, "type": "sejour"},
            {"nom": "SdE", "x": 0, "y": 4000, "w": 2500, "h": 1500, "type": "sanitaire"},
            {"nom": "Entrée", "x": 2500, "y": 4000, "w": 3000, "h": 1500, "type": "circulation"},
        ],
        "murs_ext": [(0, 0, 5500, 0), (5500, 0, 5500, 5500), (5500, 5500, 0, 5500), (0, 5500, 0, 0)],
        "portes": [
            {"x": 2000, "y": 4000, "w": 800, "orient": "H"},
            {"x": 4000, "y": 5500, "w": 900, "orient": "H"},  # Porte d'entrée
        ],
        "fenetres": [
            {"x": 1500, "y": 0, "w": 2000, "orient": "H"},
        ],
    },
    "2P": {
        "nom": "2 pièces",
        "quality": "standard",
        "largeur_mm": 7000,
        "profondeur_mm": 7000,
        "pieces": [
            {"nom": "Séjour/Cuisine", "x": 0, "y": 0, "w": 4500, "h": 4500, "type": "sejour"},
            {"nom": "Chambre", "x": 4500, "y": 0, "w": 2500, "h": 4500, "type": "chambre"},
            {"nom": "SdB", "x": 0, "y": 4500, "w": 3000, "h": 2500, "type": "sanitaire"},
            {"nom": "Entrée", "x": 3000, "y": 4500, "w": 4000, "h": 2500, "type": "circulation"},
        ],
        "murs_ext": [(0, 0, 7000, 0), (7000, 0, 7000, 7000), (7000, 7000, 0, 7000), (0, 7000, 0, 0)],
        "portes": [
            {"x": 4000, "y": 2000, "w": 800, "orient": "V"},
            {"x": 1500, "y": 4500, "w": 800, "orient": "H"},
            {"x": 5500, "y": 7000, "w": 900, "orient": "H"},
        ],
        "fenetres": [
            {"x": 1000, "y": 0, "w": 2000, "orient": "H"},
            {"x": 5000, "y": 0, "w": 1500, "orient": "H"},
        ],
    },
    "2.5P": {
        "nom": "2½ pièces",
        "quality": "confort",
        "largeur_mm": 8000,
        "profondeur_mm": 7500,
        "pieces": [
            {"nom": "Séjour", "x": 0, "y": 0, "w": 4500, "h": 4500, "type": "sejour"},
            {"nom": "Cuisine", "x": 4500, "y": 0, "w": 3500, "h": 3000, "type": "cuisine"},
            {"nom": "Chambre", "x": 4500, "y": 3000, "w": 3500, "h": 3000, "type": "chambre"},
            {"nom": "SdB", "x": 0, "y": 4500, "w": 2500, "h": 3000, "type": "sanitaire"},
            {"nom": "Entrée", "x": 2500, "y": 4500, "w": 2000, "h": 3000, "type": "circulation"},
            {"nom": "Rangement", "x": 4500, "y": 6000, "w": 3500, "h": 1500, "type": "rangement"},
        ],
        "murs_ext": [(0, 0, 8000, 0), (8000, 0, 8000, 7500), (8000, 7500, 0, 7500), (0, 7500, 0, 0)],
        "portes": [
            {"x": 4000, "y": 2000, "w": 900, "orient": "V"},
            {"x": 6000, "y": 3000, "w": 800, "orient": "H"},
            {"x": 1500, "y": 4500, "w": 800, "orient": "H"},
            {"x": 3500, "y": 5500, "w": 800, "orient": "V"},
            {"x": 5500, "y": 7500, "w": 900, "orient": "H"},
        ],
        "fenetres": [
            {"x": 1000, "y": 0, "w": 2500, "orient": "H"},
            {"x": 5000, "y": 0, "w": 2000, "orient": "H"},
            {"x": 8000, "y": 4000, "w": 1500, "orient": "V"},
        ],
    },
    "3P": {
        "nom": "3 pièces",
        "quality": "standard",
        "largeur_mm": 9000,
        "profondeur_mm": 8000,
        "pieces": [
            {"nom": "Séjour/Cuisine", "x": 0, "y": 0, "w": 5500, "h": 4500, "type": "sejour"},
            {"nom": "Chambre 1", "x": 5500, "y": 0, "w": 3500, "h": 4000, "type": "chambre"},
            {"nom": "Chambre 2", "x": 0, "y": 4500, "w": 4000, "h": 3500, "type": "chambre"},
            {"nom": "SdB", "x": 4000, "y": 4500, "w": 2500, "h": 2000, "type": "sanitaire"},
            {"nom": "WC", "x": 4000, "y": 6500, "w": 2500, "h": 1500, "type": "sanitaire"},
            {"nom": "Entrée", "x": 6500, "y": 4000, "w": 2500, "h": 4000, "type": "circulation"},
        ],
        "murs_ext": [(0, 0, 9000, 0), (9000, 0, 9000, 8000), (9000, 8000, 0, 8000), (0, 8000, 0, 0)],
        "portes": [
            {"x": 5000, "y": 2000, "w": 900, "orient": "V"},
            {"x": 2000, "y": 4500, "w": 900, "orient": "H"},
            {"x": 5000, "y": 5000, "w": 800, "orient": "H"},
            {"x": 5000, "y": 7000, "w": 700, "orient": "H"},
            {"x": 7500, "y": 8000, "w": 900, "orient": "H"},
        ],
        "fenetres": [
            {"x": 1500, "y": 0, "w": 2500, "orient": "H"},
            {"x": 6000, "y": 0, "w": 2000, "orient": "H"},
            {"x": 0, "y": 5500, "w": 2500, "orient": "V"},
        ],
    },
    "3.5P": {
        "nom": "3½ pièces",
        "quality": "confort",
        "largeur_mm": 9500,
        "profondeur_mm": 9000,
        "pieces": [
            {"nom": "Séjour", "x": 0, "y": 0, "w": 5000, "h": 4500, "type": "sejour"},
            {"nom": "Cuisine", "x": 5000, "y": 0, "w": 4500, "h": 3000, "type": "cuisine"},
            {"nom": "Chambre 1", "x": 5000, "y": 3000, "w": 4500, "h": 3000, "type": "chambre"},
            {"nom": "Chambre 2", "x": 0, "y": 4500, "w": 5000, "h": 4500, "type": "chambre"},
            {"nom": "SdB", "x": 5000, "y": 6000, "w": 2500, "h": 3000, "type": "sanitaire"},
            {"nom": "WC", "x": 7500, "y": 6000, "w": 2000, "h": 1500, "type": "sanitaire"},
            {"nom": "Entrée", "x": 7500, "y": 7500, "w": 2000, "h": 1500, "type": "circulation"},
        ],
        "murs_ext": [(0, 0, 9500, 0), (9500, 0, 9500, 9000), (9500, 9000, 0, 9000), (0, 9000, 0, 0)],
        "portes": [
            {"x": 4500, "y": 2000, "w": 900, "orient": "V"},
            {"x": 6500, "y": 3000, "w": 800, "orient": "H"},
            {"x": 2500, "y": 4500, "w": 900, "orient": "H"},
            {"x": 6000, "y": 6000, "w": 800, "orient": "H"},
            {"x": 7500, "y": 7000, "w": 700, "orient": "V"},
            {"x": 8500, "y": 7500, "w": 900, "orient": "H"},
        ],
        "fenetres": [
            {"x": 1500, "y": 0, "w": 2500, "orient": "H"},
            {"x": 6000, "y": 0, "w": 2000, "orient": "H"},
            {"x": 9500, "y": 1000, "w": 1500, "orient": "V"},
            {"x": 9500, "y": 4000, "w": 1500, "orient": "V"},
            {"x": 1500, "y": 9000, "w": 2500, "orient": "H"},
        ],
    },
    "4P": {
        "nom": "4 pièces",
        "quality": "confort",
        "largeur_mm": 11000,
        "profondeur_mm": 9500,
        "pieces": [
            {"nom": "Séjour/Cuisine", "x": 0, "y": 0, "w": 6500, "h": 5000, "type": "sejour"},
            {"nom": "Chambre 1", "x": 6500, "y": 0, "w": 4500, "h": 3500, "type": "chambre"},
            {"nom": "Chambre 2", "x": 6500, "y": 3500, "w": 4500, "h": 3000, "type": "chambre"},
            {"nom": "Chambre 3", "x": 0, "y": 5000, "w": 4000, "h": 4500, "type": "chambre"},
            {"nom": "SdB", "x": 4000, "y": 5000, "w": 2500, "h": 2500, "type": "sanitaire"},
            {"nom": "WC", "x": 4000, "y": 7500, "w": 2500, "h": 2000, "type": "sanitaire"},
            {"nom": "Entrée", "x": 6500, "y": 6500, "w": 4500, "h": 3000, "type": "circulation"},
        ],
        "murs_ext": [(0, 0, 11000, 0), (11000, 0, 11000, 9500), (11000, 9500, 0, 9500), (0, 9500, 0, 0)],
        "portes": [
            {"x": 6000, "y": 2500, "w": 900, "orient": "V"},
            {"x": 8000, "y": 3500, "w": 900, "orient": "H"},
            {"x": 2000, "y": 5000, "w": 900, "orient": "H"},
            {"x": 5000, "y": 5500, "w": 800, "orient": "H"},
            {"x": 5000, "y": 8000, "w": 700, "orient": "H"},
            {"x": 8500, "y": 9500, "w": 900, "orient": "H"},
        ],
        "fenetres": [
            {"x": 1500, "y": 0, "w": 3000, "orient": "H"},
            {"x": 7500, "y": 0, "w": 2000, "orient": "H"},
            {"x": 11000, "y": 1500, "w": 2000, "orient": "V"},
            {"x": 11000, "y": 4500, "w": 2000, "orient": "V"},
            {"x": 0, "y": 6000, "w": 2500, "orient": "V"},
        ],
    },
    "4.5P": {
        "nom": "4½ pièces",
        "quality": "premium",
        "largeur_mm": 12000,
        "profondeur_mm": 10000,
        "pieces": [
            {"nom": "Séjour", "x": 0, "y": 0, "w": 6000, "h": 5000, "type": "sejour"},
            {"nom": "Cuisine", "x": 6000, "y": 0, "w": 4000, "h": 3500, "type": "cuisine"},
            {"nom": "Chambre 1", "x": 0, "y": 5000, "w": 4500, "h": 5000, "type": "chambre"},
            {"nom": "Chambre 2", "x": 4500, "y": 5000, "w": 3500, "h": 5000, "type": "chambre"},
            {"nom": "Chambre 3", "x": 8000, "y": 5000, "w": 4000, "h": 5000, "type": "chambre"},
            {"nom": "SdB", "x": 6000, "y": 3500, "w": 3000, "h": 1500, "type": "sanitaire"},
            {"nom": "WC", "x": 9000, "y": 3500, "w": 1500, "h": 1500, "type": "sanitaire"},
            {"nom": "Dégagement", "x": 10000, "y": 0, "w": 2000, "h": 3500, "type": "circulation"},
            {"nom": "Entrée", "x": 10500, "y": 3500, "w": 1500, "h": 1500, "type": "circulation"},
        ],
        "murs_ext": [(0, 0, 12000, 0), (12000, 0, 12000, 10000), (12000, 10000, 0, 10000), (0, 10000, 0, 0)],
        "portes": [
            {"x": 5500, "y": 2500, "w": 900, "orient": "V"},
            {"x": 7500, "y": 3500, "w": 800, "orient": "H"},
            {"x": 9500, "y": 3500, "w": 700, "orient": "H"},
            {"x": 2000, "y": 5000, "w": 900, "orient": "H"},
            {"x": 6000, "y": 5000, "w": 900, "orient": "H"},
            {"x": 10000, "y": 5000, "w": 900, "orient": "H"},
            {"x": 10500, "y": 2000, "w": 900, "orient": "V"},
            {"x": 11000, "y": 5000, "w": 900, "orient": "H"},
        ],
        "fenetres": [
            {"x": 1500, "y": 0, "w": 3000, "orient": "H"},
            {"x": 6500, "y": 0, "w": 2500, "orient": "H"},
            {"x": 12000, "y": 1000, "w": 2000, "orient": "V"},
            {"x": 0, "y": 6000, "w": 3000, "orient": "V"},
            {"x": 12000, "y": 6500, "w": 2500, "orient": "V"},
            {"x": 5500, "y": 10000, "w": 2000, "orient": "H"},
        ],
    },
    "5P": {
        "nom": "5 pièces",
        "quality": "premium",
        "largeur_mm": 14000,
        "profondeur_mm": 10500,
        "pieces": [
            {"nom": "Séjour", "x": 0, "y": 0, "w": 6500, "h": 5500, "type": "sejour"},
            {"nom": "Cuisine", "x": 6500, "y": 0, "w": 4500, "h": 3500, "type": "cuisine"},
            {"nom": "Chambre 1", "x": 11000, "y": 0, "w": 3000, "h": 5000, "type": "chambre"},
            {"nom": "Chambre 2", "x": 0, "y": 5500, "w": 4000, "h": 5000, "type": "chambre"},
            {"nom": "Chambre 3", "x": 4000, "y": 5500, "w": 3500, "h": 5000, "type": "chambre"},
            {"nom": "Chambre 4", "x": 7500, "y": 5500, "w": 3500, "h": 5000, "type": "chambre"},
            {"nom": "SdB", "x": 6500, "y": 3500, "w": 2500, "h": 2000, "type": "sanitaire"},
            {"nom": "SdD", "x": 11000, "y": 5000, "w": 3000, "h": 2500, "type": "sanitaire"},
            {"nom": "WC", "x": 9000, "y": 3500, "w": 2000, "h": 2000, "type": "sanitaire"},
            {"nom": "Entrée", "x": 11000, "y": 7500, "w": 3000, "h": 3000, "type": "circulation"},
        ],
        "murs_ext": [(0, 0, 14000, 0), (14000, 0, 14000, 10500), (14000, 10500, 0, 10500), (0, 10500, 0, 0)],
        "portes": [
            {"x": 6000, "y": 2500, "w": 900, "orient": "V"},
            {"x": 8000, "y": 3500, "w": 800, "orient": "H"},
            {"x": 10000, "y": 3500, "w": 700, "orient": "H"},
            {"x": 10500, "y": 2500, "w": 900, "orient": "V"},
            {"x": 2000, "y": 5500, "w": 900, "orient": "H"},
            {"x": 5500, "y": 5500, "w": 900, "orient": "H"},
            {"x": 9000, "y": 5500, "w": 900, "orient": "H"},
            {"x": 12500, "y": 5000, "w": 900, "orient": "H"},
            {"x": 12500, "y": 7500, "w": 900, "orient": "H"},
        ],
        "fenetres": [
            {"x": 1500, "y": 0, "w": 3500, "orient": "H"},
            {"x": 7500, "y": 0, "w": 2500, "orient": "H"},
            {"x": 12000, "y": 0, "w": 2000, "orient": "H"},
            {"x": 0, "y": 6500, "w": 3000, "orient": "V"},
            {"x": 14000, "y": 2000, "w": 2000, "orient": "V"},
            {"x": 14000, "y": 6000, "w": 2000, "orient": "V"},
        ],
    },
}


# ─── Génération STL (Binary) ────────────────────────────────

@dataclass
class Triangle:
    """Triangle 3D pour export STL."""
    v1: tuple  # (x, y, z)
    v2: tuple
    v3: tuple
    normal: tuple = (0, 0, 0)


def compute_normal(v1, v2, v3):
    """Calcule la normale d'un triangle."""
    u = (v2[0] - v1[0], v2[1] - v1[1], v2[2] - v1[2])
    v = (v3[0] - v1[0], v3[1] - v1[1], v3[2] - v1[2])
    n = (
        u[1] * v[2] - u[2] * v[1],
        u[2] * v[0] - u[0] * v[2],
        u[0] * v[1] - u[1] * v[0],
    )
    length = math.sqrt(n[0]**2 + n[1]**2 + n[2]**2)
    if length == 0:
        return (0, 0, 1)
    return (n[0] / length, n[1] / length, n[2] / length)


def box_triangles(x, y, z, w, d, h) -> list:
    """
    Génère les 12 triangles d'un parallélépipède rectangle (boîte).
    (x, y, z) = coin inférieur, (w, d, h) = dimensions.
    """
    triangles = []

    # 8 sommets
    v = [
        (x, y, z),             # 0: bas-avant-gauche
        (x + w, y, z),         # 1: bas-avant-droit
        (x + w, y + d, z),     # 2: bas-arrière-droit
        (x, y + d, z),         # 3: bas-arrière-gauche
        (x, y, z + h),         # 4: haut-avant-gauche
        (x + w, y, z + h),     # 5: haut-avant-droit
        (x + w, y + d, z + h), # 6: haut-arrière-droit
        (x, y + d, z + h),     # 7: haut-arrière-gauche
    ]

    # 6 faces × 2 triangles = 12 triangles
    faces = [
        (0, 1, 5, 4),  # Face avant  (y=y)
        (2, 3, 7, 6),  # Face arrière (y=y+d)
        (0, 3, 7, 4),  # Face gauche  (x=x)
        (1, 2, 6, 5),  # Face droite  (x=x+w)
        (0, 1, 2, 3),  # Face bas     (z=z)
        (4, 5, 6, 7),  # Face haut    (z=z+h)
    ]

    for f in faces:
        p0, p1, p2, p3 = v[f[0]], v[f[1]], v[f[2]], v[f[3]]
        n = compute_normal(p0, p1, p2)
        triangles.append(Triangle(p0, p1, p2, n))
        triangles.append(Triangle(p0, p2, p3, n))

    return triangles


def write_binary_stl(triangles: list, output_path: Path, solid_name: str = "SILL"):
    """Écrit un fichier STL binaire."""
    output_path.parent.mkdir(parents=True, exist_ok=True)

    with open(output_path, "wb") as f:
        # Header: 80 bytes
        header = f"SILL Typologies - {solid_name}".encode("ascii")[:80]
        f.write(header.ljust(80, b"\0"))

        # Nombre de triangles: 4 bytes (uint32)
        f.write(struct.pack("<I", len(triangles)))

        # Triangles
        for tri in triangles:
            # Normale: 3 × float32
            f.write(struct.pack("<fff", *tri.normal))
            # Vertex 1, 2, 3: 3 × 3 × float32
            f.write(struct.pack("<fff", *tri.v1))
            f.write(struct.pack("<fff", *tri.v2))
            f.write(struct.pack("<fff", *tri.v3))
            # Attribute byte count: uint16
            f.write(struct.pack("<H", 0))


# ─── Génération du modèle 3D ────────────────────────────────

def generate_model(typo_code: str, typo: dict, scale_denom: int,
                   quality: dict, multi_color: bool = False) -> dict:
    """
    Génère le modèle 3D d'une typologie.

    Returns:
        dict avec clés "main" (triangles principaux) et optionnellement
        "walls_ext", "walls_int", "accent" pour multi-couleur.
    """
    scale = 1.0 / scale_denom  # ex: 1:100 → 0.01

    # Dimensions imprimées
    w_print = typo["largeur_mm"] * scale
    d_print = typo["profondeur_mm"] * scale

    # Vérifier que ça tient sur le plateau
    total_w = w_print + 2 * WALL_EXT_THICK_MM + 2 * MARGIN_MM
    total_d = d_print + 2 * WALL_EXT_THICK_MM + 2 * MARGIN_MM

    if total_w > BUILD_PLATE_X_MM or total_d > BUILD_PLATE_Y_MM:
        print(f"  ATTENTION: Le modèle ({total_w:.1f}×{total_d:.1f} mm) "
              f"dépasse le plateau ({BUILD_PLATE_X_MM}×{BUILD_PLATE_Y_MM} mm)")
        print(f"  Suggestion: utilisez une échelle plus petite (1:{int(scale_denom * 1.5)})")

    wall_ext_h = quality["wall_ext_height"]
    wall_int_h = quality["wall_int_height"]

    result = {"main": [], "walls_ext": [], "walls_int": [], "accent": []}

    # Offset pour centrer sur le plateau
    ox = MARGIN_MM + WALL_EXT_THICK_MM
    oy = MARGIN_MM + WALL_EXT_THICK_MM

    # ─── 1. Dalle de sol ─────────────────────────────────
    slab = box_triangles(
        ox - WALL_EXT_THICK_MM,
        oy - WALL_EXT_THICK_MM,
        0,
        w_print + 2 * WALL_EXT_THICK_MM,
        d_print + 2 * WALL_EXT_THICK_MM,
        SLAB_THICKNESS_MM,
    )
    result["main"].extend(slab)

    # ─── 2. Murs extérieurs ──────────────────────────────
    walls_ext_tris = []
    for x1r, y1r, x2r, y2r in typo["murs_ext"]:
        x1 = ox + x1r * scale
        y1 = oy + y1r * scale
        x2 = ox + x2r * scale
        y2 = oy + y2r * scale

        # Calculer la direction et l'épaisseur
        dx = x2 - x1
        dy = y2 - y1
        length = math.sqrt(dx**2 + dy**2)
        if length == 0:
            continue

        # Vecteur normal (perpendiculaire)
        nx = -dy / length * WALL_EXT_THICK_MM
        ny = dx / length * WALL_EXT_THICK_MM

        # Créer le mur comme une boîte
        if abs(dy) < 0.01:  # Mur horizontal
            wall = box_triangles(
                min(x1, x2), min(y1, y2) - WALL_EXT_THICK_MM / 2,
                SLAB_THICKNESS_MM,
                abs(dx), WALL_EXT_THICK_MM,
                wall_ext_h,
            )
        else:  # Mur vertical
            wall = box_triangles(
                min(x1, x2) - WALL_EXT_THICK_MM / 2, min(y1, y2),
                SLAB_THICKNESS_MM,
                WALL_EXT_THICK_MM, abs(dy),
                wall_ext_h,
            )
        walls_ext_tris.extend(wall)

    if multi_color:
        result["walls_ext"].extend(walls_ext_tris)
    else:
        result["main"].extend(walls_ext_tris)

    # ─── 3. Cloisons intérieures ─────────────────────────
    walls_int_tris = []
    accent_tris = []

    # Déduire les cloisons depuis les contours des pièces
    drawn_walls = set()

    for piece in typo["pieces"]:
        px = ox + piece["x"] * scale
        py = oy + piece["y"] * scale
        pw = piece["w"] * scale
        pd = piece["h"] * scale

        # 4 murs de la pièce
        walls = [
            ("H", px, py, px + pw, py),                 # Bas
            ("H", px, py + pd, px + pw, py + pd),       # Haut
            ("V", px, py, px, py + pd),                 # Gauche
            ("V", px + pw, py, px + pw, py + pd),       # Droit
        ]

        for orient, wx1, wy1, wx2, wy2 in walls:
            # Clé unique pour éviter les doublons
            key = (round(wx1, 2), round(wy1, 2), round(wx2, 2), round(wy2, 2))
            rev_key = (round(wx2, 2), round(wy2, 2), round(wx1, 2), round(wy1, 2))

            if key in drawn_walls or rev_key in drawn_walls:
                continue
            drawn_walls.add(key)

            # Ne pas dessiner si c'est un mur extérieur
            is_exterior = False
            for ex1, ey1, ex2, ey2 in typo["murs_ext"]:
                ex1s = ox + ex1 * scale
                ey1s = oy + ey1 * scale
                ex2s = ox + ex2 * scale
                ey2s = oy + ey2 * scale
                if (abs(wx1 - ex1s) < 0.5 and abs(wy1 - ey1s) < 0.5 and
                    abs(wx2 - ex2s) < 0.5 and abs(wy2 - ey2s) < 0.5):
                    is_exterior = True
                    break
            if is_exterior:
                continue

            # Vérifier si une porte est sur ce mur
            has_door = False
            door_gaps = []
            for door in typo.get("portes", []):
                door_x = ox + door["x"] * scale
                door_y = oy + door["y"] * scale if "y" in door else wy1
                door_w = door["w"] * scale

                if orient == "H" and abs(wy1 - door_y) < 1.0:
                    if wx1 <= door_x <= wx2 or wx1 <= door_x + door_w <= wx2:
                        door_gaps.append((door_x, door_x + door_w))
                        has_door = True
                elif orient == "V" and abs(wx1 - door_x) < 1.0:
                    if wy1 <= door_y <= wy2 or wy1 <= door_y + door_w <= wy2:
                        door_gaps.append((door_y, door_y + door_w))
                        has_door = True

            if not has_door:
                # Mur plein
                if orient == "H":
                    wall = box_triangles(
                        wx1, wy1 - WALL_INT_THICK_MM / 2,
                        SLAB_THICKNESS_MM,
                        wx2 - wx1, WALL_INT_THICK_MM,
                        wall_int_h,
                    )
                else:
                    wall = box_triangles(
                        wx1 - WALL_INT_THICK_MM / 2, wy1,
                        SLAB_THICKNESS_MM,
                        WALL_INT_THICK_MM, wy2 - wy1,
                        wall_int_h,
                    )
            else:
                # Mur avec ouverture(s) — découper
                wall = []
                if orient == "H":
                    segments = _split_segment(wx1, wx2, door_gaps)
                    for s_start, s_end in segments:
                        wall.extend(box_triangles(
                            s_start, wy1 - WALL_INT_THICK_MM / 2,
                            SLAB_THICKNESS_MM,
                            s_end - s_start, WALL_INT_THICK_MM,
                            wall_int_h,
                        ))
                else:
                    segments = _split_segment(wy1, wy2, door_gaps)
                    for s_start, s_end in segments:
                        wall.extend(box_triangles(
                            wx1 - WALL_INT_THICK_MM / 2, s_start,
                            SLAB_THICKNESS_MM,
                            WALL_INT_THICK_MM, s_end - s_start,
                            wall_int_h,
                        ))

            # Accent couleur pour sanitaires/cuisine
            is_accent = piece["type"] in ("sanitaire", "cuisine")
            if multi_color and is_accent and quality.get("color_accent"):
                accent_tris.extend(wall)
            elif multi_color:
                walls_int_tris.extend(wall)
            else:
                result["main"].extend(wall)

    if multi_color:
        result["walls_int"].extend(walls_int_tris)
        result["accent"].extend(accent_tris)

    # ─── 4. Fenêtres (créneaux dans les murs ext) ────────
    if quality["detail_level"] >= 2:
        for window in typo.get("fenetres", []):
            win_w = window["w"] * scale
            if window["orient"] == "H":
                wx = ox + window["x"] * scale
                wy = oy + (window.get("y", 0)) * scale
                # Linteau au-dessus de l'ouverture
                lintel = box_triangles(
                    wx, wy - WALL_EXT_THICK_MM / 2,
                    SLAB_THICKNESS_MM + wall_ext_h * 0.7,
                    win_w, WALL_EXT_THICK_MM,
                    wall_ext_h * 0.3,
                )
            else:
                wx = ox + window["x"] * scale
                wy = oy + window.get("y", window.get("x", 0)) * scale
                lintel = box_triangles(
                    wx - WALL_EXT_THICK_MM / 2, wy,
                    SLAB_THICKNESS_MM + wall_ext_h * 0.7,
                    WALL_EXT_THICK_MM, win_w,
                    wall_ext_h * 0.3,
                )
            if multi_color:
                result["walls_ext"].extend(lintel)
            else:
                result["main"].extend(lintel)

    # ─── 5. Éléments de détail (niveau premium) ──────────
    if quality["detail_level"] >= 3:
        for piece in typo["pieces"]:
            px = ox + piece["x"] * scale
            py = oy + piece["y"] * scale
            pw = piece["w"] * scale
            pd = piece["h"] * scale

            if piece["type"] == "sanitaire":
                # Baignoire / receveur de douche (petit bloc)
                bath = box_triangles(
                    px + pw * 0.1, py + pd * 0.1,
                    SLAB_THICKNESS_MM,
                    pw * 0.4, pd * 0.6,
                    1.5,  # 1.5mm de haut
                )
                if multi_color:
                    result["accent"].extend(bath)
                else:
                    result["main"].extend(bath)

            elif piece["type"] == "cuisine":
                # Plan de travail (L le long de 2 murs)
                counter = box_triangles(
                    px + 0.5, py + 0.5,
                    SLAB_THICKNESS_MM,
                    pw * 0.2, pd * 0.8,
                    2.0,
                )
                if multi_color:
                    result["accent"].extend(counter)
                else:
                    result["main"].extend(counter)

    return result


def _split_segment(start: float, end: float, gaps: list) -> list:
    """Découpe un segment en enlevant les gaps (portes)."""
    gaps_sorted = sorted(gaps, key=lambda g: g[0])
    segments = []
    current = start

    for gap_start, gap_end in gaps_sorted:
        if gap_start > current:
            segments.append((current, gap_start))
        current = max(current, gap_end)

    if current < end:
        segments.append((current, end))

    return segments


# ─── Main ────────────────────────────────────────────────────

def generate_typology(typo_code: str, scale_denom: int, output_dir: Path,
                      multi_color: bool = False) -> list:
    """Génère les fichiers STL pour une typologie."""
    if typo_code not in TYPOLOGIES_3D:
        print(f"Erreur: typologie '{typo_code}' inconnue.")
        print(f"Disponibles: {', '.join(TYPOLOGIES_3D.keys())}")
        return []

    typo = TYPOLOGIES_3D[typo_code]
    quality_key = typo["quality"]
    quality = QUALITY_LEVELS[quality_key]

    print(f"\n{'='*60}")
    print(f"  SILL Typologie {typo_code} — {typo['nom']}")
    print(f"  Qualité: {quality['label']} ({quality['description']})")
    print(f"  Échelle: 1:{scale_denom}")
    print(f"  Dimensions réelles: {typo['largeur_mm']/1000:.1f} × {typo['profondeur_mm']/1000:.1f} m")
    print(f"  Dimensions imprimées: {typo['largeur_mm']/scale_denom:.1f} × {typo['profondeur_mm']/scale_denom:.1f} mm")
    print(f"  Multi-couleur AMS: {'oui' if multi_color else 'non'}")
    print(f"{'='*60}")

    model = generate_model(typo_code, typo, scale_denom, quality, multi_color)
    files = []

    if multi_color:
        # Export séparé par couleur (pour AMS Bambu Lab)
        parts = {
            "dalle": model["main"],
            "murs-ext": model["walls_ext"],
            "cloisons": model["walls_int"],
            "accent": model["accent"],
        }
        for part_name, triangles in parts.items():
            if triangles:
                filename = f"SILL_{typo_code}_{part_name}.stl"
                path = output_dir / filename
                write_binary_stl(triangles, path, f"{typo_code}_{part_name}")
                print(f"  → {filename} ({len(triangles)} triangles)")
                files.append(str(path))
    else:
        # Export mono-fichier
        filename = f"SILL_{typo_code}.stl"
        path = output_dir / filename
        write_binary_stl(model["main"], path, typo_code)
        print(f"  → {filename} ({len(model['main'])} triangles)")
        files.append(str(path))

    return files


def main():
    parser = argparse.ArgumentParser(
        description="SILL Typologies — Générateur de maquettes 3D pour Bambu Lab X1 Carbon"
    )
    parser.add_argument(
        "--typology", "-t", default="all",
        help=f"Code de la typologie ({', '.join(TYPOLOGIES_3D.keys())}, all)"
    )
    parser.add_argument(
        "--scale", "-s", default="1:100",
        help="Échelle d'impression (1:50, 1:75, 1:100, 1:200)"
    )
    parser.add_argument(
        "--output", "-o", default="output/stl",
        help="Répertoire de sortie"
    )
    parser.add_argument(
        "--multi-color", "-m", action="store_true",
        help="Générer des fichiers séparés pour impression multi-couleur (AMS)"
    )
    parser.add_argument(
        "--quality", "-q",
        choices=["standard", "confort", "premium"],
        help="Forcer un niveau de qualité (sinon utilise celui de la typologie)"
    )

    args = parser.parse_args()

    # Parser l'échelle
    scale_match = args.scale.replace(" ", "")
    if ":" in scale_match:
        scale_denom = int(scale_match.split(":")[1])
    else:
        scale_denom = int(scale_match)

    output_dir = Path(args.output)
    all_files = []

    if args.typology == "all":
        for code in TYPOLOGIES_3D:
            files = generate_typology(code, scale_denom, output_dir, args.multi_color)
            all_files.extend(files)
    else:
        files = generate_typology(args.typology, scale_denom, output_dir, args.multi_color)
        all_files.extend(files)

    print(f"\n{'='*60}")
    print(f"  Terminé: {len(all_files)} fichier(s) STL générés")
    print(f"  Répertoire: {output_dir}")
    print(f"\n  Étapes suivantes:")
    print(f"  1. Ouvrir Bambu Studio")
    print(f"  2. Importer les STL")
    if args.multi_color:
        print(f"  3. Assigner les couleurs AMS:")
        print(f"     - dalle → blanc")
        print(f"     - murs-ext → gris foncé")
        print(f"     - cloisons → gris clair")
        print(f"     - accent → rouge SILL (#FF0000)")
    print(f"  4. Paramètres recommandés:")
    print(f"     - Hauteur de couche: 0.16 mm")
    print(f"     - Remplissage: 15%")
    print(f"     - Support: non nécessaire")
    print(f"     - Plaque: textured PEI")
    print(f"{'='*60}")


if __name__ == "__main__":
    main()
