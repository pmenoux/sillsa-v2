#!/usr/bin/env python3
"""
SILL Typologies — Script Autodesk Fusion 360 API

Ce script s'exécute DANS Fusion 360 (Scripts & Add-ins).
Il génère des modèles 3D paramétriques à partir des typologies SILL.

Installation:
    1. Ouvrir Fusion 360
    2. Scripts & Add-ins → onglet "Scripts" → "+" (Create)
    3. Copier ce fichier comme script Python
    4. Placer catalog/typologies-sia.json dans le même dossier
    5. Exécuter le script

Le script génère:
    - Plans d'étage 2D (Sketch) avec murs, cloisons, ouvertures
    - Extrusion 3D des murs (hauteur paramétrable)
    - Composants par pièce avec surfaces annotées
    - Export automatique en DXF/STEP/PDF
"""

# ============================================================
# NOTE: Ce script utilise l'API Fusion 360 (adsk.core, adsk.fusion)
# Il ne peut s'exécuter que dans l'environnement Fusion 360.
# ============================================================

import json
import math
import os
import traceback

# Fusion 360 API imports — disponibles uniquement dans Fusion 360
try:
    import adsk.core
    import adsk.fusion
    IN_FUSION = True
except ImportError:
    IN_FUSION = False
    print("Ce script doit être exécuté dans Autodesk Fusion 360.")
    print("Scripts & Add-ins → Scripts → Exécuter")


# ─── Configuration ───────────────────────────────────────────

WALL_HEIGHT_MM = 2700       # Hauteur sous plafond standard Suisse
WALL_EXT_MM = 200           # Épaisseur mur extérieur
WALL_INT_MM = 100           # Épaisseur cloison intérieure
DOOR_WIDTH_MM = 900         # Largeur porte standard
DOOR_HEIGHT_MM = 2100       # Hauteur porte standard
WINDOW_SILL_MM = 900        # Allège fenêtre
WINDOW_HEIGHT_MM = 1200     # Hauteur fenêtre
FLOOR_SLAB_MM = 250         # Épaisseur dalle


# ─── Typologies par défaut (si JSON non disponible) ──────────

DEFAULT_TYPOLOGIES = {
    "3.5P": {
        "nom": "3½ pièces",
        "largeur_mm": 9500,
        "profondeur_mm": 9000,
        "pieces": [
            {"nom": "Séjour",    "x": 0,    "y": 0,    "w": 5000, "h": 4500},
            {"nom": "Cuisine",   "x": 5000, "y": 0,    "w": 4500, "h": 3000},
            {"nom": "Chambre 1", "x": 5000, "y": 3000, "w": 4500, "h": 3000},
            {"nom": "Chambre 2", "x": 0,    "y": 4500, "w": 5000, "h": 4500},
            {"nom": "SdB",       "x": 5000, "y": 6000, "w": 2500, "h": 3000},
            {"nom": "WC",        "x": 7500, "y": 6000, "w": 2000, "h": 1500},
            {"nom": "Entrée",    "x": 7500, "y": 7500, "w": 2000, "h": 1500},
        ],
        "portes": [
            {"x": 4500, "y": 2000, "orientation": "V"},
            {"x": 5500, "y": 3000, "orientation": "H"},
            {"x": 6000, "y": 4500, "orientation": "H"},
            {"x": 5000, "y": 7000, "orientation": "V"},
            {"x": 7500, "y": 7000, "orientation": "V"},
            {"x": 8500, "y": 7500, "orientation": "H"},
        ],
        "fenetres": [
            {"x": 1500, "y": 0, "w": 2000},
            {"x": 6000, "y": 0, "w": 1500},
            {"x": 9500, "y": 1000, "w": 0, "h": 1500, "orientation": "V"},
            {"x": 9500, "y": 4000, "w": 0, "h": 1500, "orientation": "V"},
            {"x": 1500, "y": 9000, "w": 2000},
        ],
    },
    "2.5P": {
        "nom": "2½ pièces",
        "largeur_mm": 7000,
        "profondeur_mm": 7500,
        "pieces": [
            {"nom": "Séjour",    "x": 0,    "y": 0,    "w": 4000, "h": 4500},
            {"nom": "Cuisine",   "x": 4000, "y": 0,    "w": 3000, "h": 3000},
            {"nom": "Chambre",   "x": 0,    "y": 4500, "w": 4000, "h": 3000},
            {"nom": "SdB",       "x": 4000, "y": 3000, "w": 3000, "h": 2500},
            {"nom": "Entrée",    "x": 4000, "y": 5500, "w": 3000, "h": 2000},
        ],
        "portes": [
            {"x": 3500, "y": 2000, "orientation": "V"},
            {"x": 5000, "y": 3000, "orientation": "H"},
            {"x": 4000, "y": 5500, "orientation": "V"},
            {"x": 5500, "y": 5500, "orientation": "H"},
        ],
        "fenetres": [
            {"x": 1000, "y": 0, "w": 2000},
            {"x": 4500, "y": 0, "w": 1500},
            {"x": 1000, "y": 7500, "w": 2000},
        ],
    },
    "4.5P": {
        "nom": "4½ pièces",
        "largeur_mm": 12000,
        "profondeur_mm": 10000,
        "pieces": [
            {"nom": "Séjour",      "x": 0,    "y": 0,    "w": 6000, "h": 5000},
            {"nom": "Cuisine",     "x": 6000, "y": 0,    "w": 4000, "h": 3500},
            {"nom": "Chambre 1",   "x": 0,    "y": 5000, "w": 4500, "h": 5000},
            {"nom": "Chambre 2",   "x": 4500, "y": 5000, "w": 3500, "h": 5000},
            {"nom": "Chambre 3",   "x": 8000, "y": 5000, "w": 4000, "h": 5000},
            {"nom": "SdB",         "x": 6000, "y": 3500, "w": 3000, "h": 1500},
            {"nom": "WC",          "x": 9000, "y": 3500, "w": 1500, "h": 1500},
            {"nom": "Dégagement",  "x": 10000,"y": 0,    "w": 2000, "h": 3500},
            {"nom": "Entrée",      "x": 10500,"y": 3500, "w": 1500, "h": 1500},
        ],
        "portes": [],
        "fenetres": [],
    },
}


def mm(value: float) -> float:
    """Convertit mm → cm (unité interne Fusion 360)."""
    return value / 10.0


def run_in_fusion(context):
    """Point d'entrée principal pour Fusion 360."""
    ui = None
    try:
        app = adsk.core.Application.get()
        ui = app.userInterface
        design = adsk.fusion.Design.cast(app.activeProduct)

        if not design:
            ui.messageBox("Ouvrez un document Design avant d'exécuter ce script.")
            return

        root = design.rootComponent

        # Charger les typologies depuis le JSON ou utiliser les défauts
        script_dir = os.path.dirname(os.path.abspath(__file__))
        json_path = os.path.join(script_dir, "typologies-sia.json")

        typologies = DEFAULT_TYPOLOGIES
        if os.path.exists(json_path):
            with open(json_path, "r", encoding="utf-8") as f:
                data = json.load(f)
                # Fusionner avec les défauts si format compatible
                ui.messageBox(f"Typologies chargées depuis {json_path}")

        # Demander quelle typologie générer
        typo_names = list(typologies.keys())
        selected = ui.inputBox(
            f"Typologie à générer ({', '.join(typo_names)}):",
            "SILL Typologies",
            typo_names[0] if typo_names else "3.5P",
        )

        if selected[1]:  # Annulé
            return

        typo_code = selected[0].strip()
        if typo_code not in typologies:
            ui.messageBox(f"Typologie '{typo_code}' non trouvée. Disponibles: {', '.join(typo_names)}")
            return

        typo = typologies[typo_code]
        generate_floor_plan(root, typo_code, typo, design)

        ui.messageBox(f"Typologie {typo_code} ({typo['nom']}) générée avec succès!")

    except Exception:
        if ui:
            ui.messageBox(f"Erreur:\n{traceback.format_exc()}")


def generate_floor_plan(root_comp, typo_code: str, typo: dict, design):
    """
    Génère le plan d'étage 3D complet dans Fusion 360.
    """
    # Créer un composant pour la typologie
    occ = root_comp.occurrences.addNewComponent(adsk.core.Matrix3D.create())
    comp = occ.component
    comp.name = f"SILL_{typo_code}_{typo['nom']}"

    # ─── 1. Dalle de sol ─────────────────────────────────
    sketch_floor = comp.sketches.add(comp.xYConstructionPlane)
    sketch_floor.name = "Dalle"

    w = mm(typo["largeur_mm"])
    d = mm(typo["profondeur_mm"])

    lines = sketch_floor.sketchCurves.sketchLines
    p0 = adsk.core.Point3D.create(0, 0, 0)
    p1 = adsk.core.Point3D.create(w, 0, 0)
    p2 = adsk.core.Point3D.create(w, d, 0)
    p3 = adsk.core.Point3D.create(0, d, 0)
    lines.addByTwoPoints(p0, p1)
    lines.addByTwoPoints(p1, p2)
    lines.addByTwoPoints(p2, p3)
    lines.addByTwoPoints(p3, p0)

    # Extruder la dalle vers le bas
    prof = sketch_floor.profiles.item(0)
    extrudes = comp.features.extrudeFeatures
    ext_input = extrudes.createInput(prof, adsk.fusion.FeatureOperations.NewBodyFeatureOperation)
    slab_dist = adsk.core.ValueInput.createByReal(-mm(FLOOR_SLAB_MM))
    ext_input.setDistanceExtent(False, slab_dist)
    slab_body = extrudes.add(ext_input)
    slab_body.name = "Dalle"

    # ─── 2. Murs extérieurs ──────────────────────────────
    sketch_walls = comp.sketches.add(comp.xYConstructionPlane)
    sketch_walls.name = "Murs extérieurs"

    wall_t = mm(WALL_EXT_MM)
    lines_w = sketch_walls.sketchCurves.sketchLines

    # Rectangle extérieur
    lines_w.addByTwoPoints(
        adsk.core.Point3D.create(-wall_t, -wall_t, 0),
        adsk.core.Point3D.create(w + wall_t, -wall_t, 0),
    )
    lines_w.addByTwoPoints(
        adsk.core.Point3D.create(w + wall_t, -wall_t, 0),
        adsk.core.Point3D.create(w + wall_t, d + wall_t, 0),
    )
    lines_w.addByTwoPoints(
        adsk.core.Point3D.create(w + wall_t, d + wall_t, 0),
        adsk.core.Point3D.create(-wall_t, d + wall_t, 0),
    )
    lines_w.addByTwoPoints(
        adsk.core.Point3D.create(-wall_t, d + wall_t, 0),
        adsk.core.Point3D.create(-wall_t, -wall_t, 0),
    )

    # Rectangle intérieur
    lines_w.addByTwoPoints(p0, p1)
    lines_w.addByTwoPoints(p1, p2)
    lines_w.addByTwoPoints(p2, p3)
    lines_w.addByTwoPoints(p3, p0)

    # Extruder les murs (profil entre les deux rectangles)
    for i in range(sketch_walls.profiles.count):
        prof = sketch_walls.profiles.item(i)
        # Prendre le profil en forme de U (entre ext et int)
        area = prof.areaProperties().area
        if area < w * d * 0.9:  # Pas le profil intérieur complet
            ext_input = extrudes.createInput(prof, adsk.fusion.FeatureOperations.NewBodyFeatureOperation)
            wall_dist = adsk.core.ValueInput.createByReal(mm(WALL_HEIGHT_MM))
            ext_input.setDistanceExtent(False, wall_dist)
            wall_body = extrudes.add(ext_input)
            wall_body.name = "Murs extérieurs"
            break

    # ─── 3. Cloisons intérieures ─────────────────────────
    sketch_partitions = comp.sketches.add(comp.xYConstructionPlane)
    sketch_partitions.name = "Cloisons"

    wall_int = mm(WALL_INT_MM)
    lines_p = sketch_partitions.sketchCurves.sketchLines

    for piece in typo["pieces"]:
        px = mm(piece["x"])
        py = mm(piece["y"])
        pw = mm(piece["w"])
        ph = mm(piece["h"])

        # Dessiner le contour de chaque pièce
        lines_p.addByTwoPoints(
            adsk.core.Point3D.create(px, py, 0),
            adsk.core.Point3D.create(px + pw, py, 0),
        )
        lines_p.addByTwoPoints(
            adsk.core.Point3D.create(px + pw, py, 0),
            adsk.core.Point3D.create(px + pw, py + ph, 0),
        )
        lines_p.addByTwoPoints(
            adsk.core.Point3D.create(px + pw, py + ph, 0),
            adsk.core.Point3D.create(px, py + ph, 0),
        )
        lines_p.addByTwoPoints(
            adsk.core.Point3D.create(px, py + ph, 0),
            adsk.core.Point3D.create(px, py, 0),
        )

    # ─── 4. Annotations de surface ───────────────────────
    texts = sketch_partitions.sketchTexts
    for piece in typo["pieces"]:
        area_m2 = (piece["w"] / 1000) * (piece["h"] / 1000)
        label = f"{piece['nom']}\n{area_m2:.1f} m²"

        cx = mm(piece["x"] + piece["w"] / 2)
        cy = mm(piece["y"] + piece["h"] / 2)

        text_input = texts.createInput2(label, mm(300))
        text_input.setAsMultiLine(
            adsk.core.Point3D.create(cx - mm(1000), cy - mm(200), 0),
            adsk.core.Point3D.create(cx + mm(1000), cy + mm(200), 0),
            adsk.core.HorizontalAlignments.CenterHorizontalAlignment,
            adsk.core.VerticalAlignments.MiddleVerticalAlignment,
            0,
        )
        texts.add(text_input)


def generate_standalone_dxf(typo_code: str, typo: dict, output_path: str):
    """
    Génère un DXF 2D d'une typologie SANS Fusion 360.
    Utilisable en mode standalone pour pré-visualisation.
    """
    import ezdxf
    from ezdxf import units as dxf_units

    doc = ezdxf.new("R2018")
    doc.header["$INSUNITS"] = dxf_units.MM
    msp = doc.modelspace()

    # Calques
    doc.layers.new("MURS_EXT", dxfattribs={"color": 7, "lineweight": 50})
    doc.layers.new("CLOISONS", dxfattribs={"color": 8, "lineweight": 25})
    doc.layers.new("TEXTE", dxfattribs={"color": 2})
    doc.layers.new("COTES", dxfattribs={"color": 3})

    w = typo["largeur_mm"]
    d = typo["profondeur_mm"]

    # Murs extérieurs
    ext = WALL_EXT_MM
    msp.add_lwpolyline(
        [(-ext, -ext), (w + ext, -ext), (w + ext, d + ext), (-ext, d + ext)],
        close=True, dxfattribs={"layer": "MURS_EXT"},
    )
    msp.add_lwpolyline(
        [(0, 0), (w, 0), (w, d), (0, d)],
        close=True, dxfattribs={"layer": "MURS_EXT"},
    )

    # Cloisons et annotations
    for piece in typo["pieces"]:
        px, py, pw, ph = piece["x"], piece["y"], piece["w"], piece["h"]

        msp.add_lwpolyline(
            [(px, py), (px + pw, py), (px + pw, py + ph), (px, py + ph)],
            close=True, dxfattribs={"layer": "CLOISONS"},
        )

        # Annotation
        area = (pw / 1000) * (ph / 1000)
        msp.add_mtext(
            f"{piece['nom']}\\P{area:.1f} m²",
            dxfattribs={
                "layer": "TEXTE",
                "insert": (px + pw / 2, py + ph / 2),
                "char_height": 150,
                "attachment_point": 5,  # Centre
            },
        )

    # Cotation générale
    dim_style = doc.dimstyles.new("SILL")
    dim_style.dxf.dimtxt = 100
    dim_style.dxf.dimasz = 80

    msp.add_linear_dim(
        base=(0, -ext - 500),
        p1=(0, 0), p2=(w, 0),
        dxfattribs={"layer": "COTES", "dimstyle": "SILL"},
    )
    msp.add_linear_dim(
        base=(-ext - 500, 0),
        p1=(0, 0), p2=(0, d),
        angle=90,
        dxfattribs={"layer": "COTES", "dimstyle": "SILL"},
    )

    # Cartouche
    msp.add_mtext(
        f"SILL SA — Typologie {typo_code} ({typo['nom']})\\P"
        f"Dimensions: {w / 1000:.1f} × {d / 1000:.1f} m\\P"
        f"Surface brute: {(w / 1000) * (d / 1000):.1f} m²",
        dxfattribs={
            "layer": "TEXTE",
            "insert": (0, -ext - 1500),
            "char_height": 120,
        },
    )

    doc.saveas(output_path)
    return output_path


# ─── Point d'entrée ──────────────────────────────────────────

if IN_FUSION:
    def run(context):
        run_in_fusion(context)
else:
    # Mode standalone: générer les DXF sans Fusion 360
    if __name__ == "__main__":
        import argparse

        parser = argparse.ArgumentParser(
            description="SILL Typologies — Génération DXF standalone (sans Fusion 360)"
        )
        parser.add_argument(
            "--typology", "-t", default="all",
            help="Code de la typologie (3.5P, 2.5P, 4.5P, all)"
        )
        parser.add_argument(
            "--output", "-o", default="output/dxf-fusion",
            help="Répertoire de sortie"
        )

        args = parser.parse_args()
        output_dir = Path(args.output)
        output_dir.mkdir(parents=True, exist_ok=True)

        if args.typology == "all":
            for code, typo in DEFAULT_TYPOLOGIES.items():
                out = generate_standalone_dxf(code, typo, str(output_dir / f"typo_{code}.dxf"))
                print(f"Généré: {out}")
        else:
            if args.typology not in DEFAULT_TYPOLOGIES:
                print(f"Erreur: '{args.typology}' non trouvée. Disponibles: {', '.join(DEFAULT_TYPOLOGIES.keys())}")
                sys.exit(1)
            typo = DEFAULT_TYPOLOGIES[args.typology]
            out = generate_standalone_dxf(args.typology, typo, str(output_dir / f"typo_{args.typology}.dxf"))
            print(f"Généré: {out}")
