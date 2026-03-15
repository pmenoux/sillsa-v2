#!/usr/bin/env python3
"""
SILL Typologies — Générateur de projet Bambu Studio (.3mf)

Crée un fichier .3mf directement importable dans Bambu Studio
avec les paramètres d'impression pré-configurés et les assignations
de couleurs AMS pour le X1 Carbon.

Le format 3MF est un ZIP contenant:
    - 3D/3dmodel.model (XML avec géométrie)
    - Metadata/plate_1.json (configuration Bambu)
    - [Content_Types].xml

Usage:
    python bambu_project.py --typology 3.5P --scale 1:100 --output output/3mf/
    python bambu_project.py --typology all --scale 1:100 --preset presentation --output output/3mf/
"""

import argparse
import json
import struct
import sys
import zipfile
from io import BytesIO
from pathlib import Path
from xml.etree import ElementTree as ET

# Import du générateur STL
from generate_3d_print import (
    TYPOLOGIES_3D, QUALITY_LEVELS, generate_model,
    compute_normal, write_binary_stl,
)


# ─── Presets d'impression pour Bambu Lab X1 Carbon ───────────

PRINT_PRESETS = {
    "draft": {
        "label": "Brouillon rapide",
        "layer_height": 0.28,
        "infill": 10,
        "wall_loops": 2,
        "speed": 250,
        "supports": False,
        "estimated_time_min": 20,
        "filament": "PLA Basic",
    },
    "standard": {
        "label": "Standard",
        "layer_height": 0.20,
        "infill": 15,
        "wall_loops": 3,
        "speed": 200,
        "supports": False,
        "estimated_time_min": 35,
        "filament": "PLA Basic",
    },
    "presentation": {
        "label": "Présentation (qualité max)",
        "layer_height": 0.12,
        "infill": 20,
        "wall_loops": 4,
        "speed": 100,
        "supports": False,
        "estimated_time_min": 60,
        "filament": "PLA Matte",
    },
    "detail": {
        "label": "Ultra-détail",
        "layer_height": 0.08,
        "infill": 20,
        "wall_loops": 4,
        "speed": 60,
        "supports": False,
        "estimated_time_min": 120,
        "filament": "PLA Matte",
    },
}

# Couleurs AMS pour les différentes parties
AMS_COLORS = {
    "dalle": {"hex": "#FFFFFF", "name": "Blanc", "slot": 1},
    "murs-ext": {"hex": "#4A4A4A", "name": "Gris foncé", "slot": 2},
    "cloisons": {"hex": "#B0B0B0", "name": "Gris clair", "slot": 3},
    "accent": {"hex": "#FF0000", "name": "Rouge SILL", "slot": 4},
}

# Couleurs par niveau de qualité
QUALITY_COLORS = {
    "standard": {"base": "#E0E0E0", "accent": "#808080"},
    "confort": {"base": "#F5F0EB", "accent": "#CC0000"},
    "premium": {"base": "#FFFFFF", "accent": "#FF0000"},
}


def triangles_to_3mf_mesh(triangles: list) -> ET.Element:
    """Convertit une liste de triangles en mesh 3MF XML."""
    mesh = ET.Element("mesh")
    vertices_el = ET.SubElement(mesh, "vertices")
    triangles_el = ET.SubElement(mesh, "triangles")

    # Dédupliquer les vertices
    vertex_map = {}
    vertex_list = []

    def get_vertex_index(v):
        key = (round(v[0], 4), round(v[1], 4), round(v[2], 4))
        if key not in vertex_map:
            vertex_map[key] = len(vertex_list)
            vertex_list.append(key)
            vert = ET.SubElement(vertices_el, "vertex")
            vert.set("x", f"{key[0]:.4f}")
            vert.set("y", f"{key[1]:.4f}")
            vert.set("z", f"{key[2]:.4f}")
        return vertex_map[key]

    for tri in triangles:
        i1 = get_vertex_index(tri.v1)
        i2 = get_vertex_index(tri.v2)
        i3 = get_vertex_index(tri.v3)
        tri_el = ET.SubElement(triangles_el, "triangle")
        tri_el.set("v1", str(i1))
        tri_el.set("v2", str(i2))
        tri_el.set("v3", str(i3))

    return mesh


def create_3mf(typo_code: str, scale_denom: int, output_path: Path,
               preset_name: str = "standard", multi_color: bool = True):
    """
    Crée un fichier .3mf pour Bambu Studio.
    """
    typo = TYPOLOGIES_3D[typo_code]
    quality_key = typo["quality"]
    quality = QUALITY_LEVELS[quality_key]
    preset = PRINT_PRESETS[preset_name]

    print(f"  Génération 3MF: {typo_code} ({typo['nom']})")
    print(f"  Qualité: {quality['label']} | Preset: {preset['label']}")

    model = generate_model(typo_code, typo, scale_denom, quality, multi_color)

    # Créer le document 3MF (ZIP)
    output_path.parent.mkdir(parents=True, exist_ok=True)

    with zipfile.ZipFile(output_path, "w", zipfile.ZIP_DEFLATED) as zf:
        # ─── [Content_Types].xml ─────────────────────────
        content_types = ET.Element("Types")
        content_types.set("xmlns", "http://schemas.openxmlformats.org/package/2006/content-types")

        default_model = ET.SubElement(content_types, "Default")
        default_model.set("Extension", "model")
        default_model.set("ContentType", "application/vnd.ms-package.3dmanufacturing-3dmodel+xml")

        default_rels = ET.SubElement(content_types, "Default")
        default_rels.set("Extension", "rels")
        default_rels.set("ContentType", "application/vnd.openxmlformats-package.relationships+xml")

        zf.writestr("[Content_Types].xml", ET.tostring(content_types, encoding="unicode", xml_declaration=True))

        # ─── _rels/.rels ─────────────────────────────────
        rels = ET.Element("Relationships")
        rels.set("xmlns", "http://schemas.openxmlformats.org/package/2006/relationships")

        rel = ET.SubElement(rels, "Relationship")
        rel.set("Target", "/3D/3dmodel.model")
        rel.set("Id", "rel0")
        rel.set("Type", "http://schemas.microsoft.com/3dmanufacturing/2013/01/3dmodel")

        zf.writestr("_rels/.rels", ET.tostring(rels, encoding="unicode", xml_declaration=True))

        # ─── 3D/3dmodel.model ────────────────────────────
        ns = "http://schemas.microsoft.com/3dmanufacturing/core/2015/02"
        model_el = ET.Element("model")
        model_el.set("xmlns", ns)
        model_el.set("unit", "millimeter")

        metadata = ET.SubElement(model_el, "metadata")
        metadata.set("name", "Title")
        metadata.text = f"SILL SA — Typologie {typo_code} ({typo['nom']})"

        metadata2 = ET.SubElement(model_el, "metadata")
        metadata2.set("name", "Description")
        metadata2.text = (
            f"Maquette {typo['nom']} | "
            f"Qualité {quality['label']} | "
            f"Échelle 1:{scale_denom} | "
            f"{typo['largeur_mm']/1000:.1f}×{typo['profondeur_mm']/1000:.1f}m"
        )

        resources = ET.SubElement(model_el, "resources")
        build = ET.SubElement(model_el, "build")

        if multi_color:
            parts = {
                "dalle": model["main"],
                "murs-ext": model["walls_ext"],
                "cloisons": model["walls_int"],
                "accent": model["accent"],
            }
            obj_id = 1
            for part_name, triangles in parts.items():
                if not triangles:
                    continue

                obj = ET.SubElement(resources, "object")
                obj.set("id", str(obj_id))
                obj.set("type", "model")
                obj.set("name", f"{typo_code}_{part_name}")

                mesh = triangles_to_3mf_mesh(triangles)
                obj.append(mesh)

                item = ET.SubElement(build, "item")
                item.set("objectid", str(obj_id))

                obj_id += 1
        else:
            obj = ET.SubElement(resources, "object")
            obj.set("id", "1")
            obj.set("type", "model")
            obj.set("name", f"SILL_{typo_code}")

            mesh = triangles_to_3mf_mesh(model["main"])
            obj.append(mesh)

            item = ET.SubElement(build, "item")
            item.set("objectid", "1")

        model_xml = ET.tostring(model_el, encoding="unicode", xml_declaration=True)
        zf.writestr("3D/3dmodel.model", model_xml)

        # ─── Metadata/project_settings.json ──────────────
        # Configuration Bambu Studio
        project_settings = {
            "printer": "Bambu Lab X1 Carbon",
            "filament": preset["filament"],
            "layer_height": preset["layer_height"],
            "initial_layer_height": 0.2,
            "infill_density": preset["infill"],
            "wall_loops": preset["wall_loops"],
            "print_speed": preset["speed"],
            "support_enabled": preset["supports"],
            "plate_type": "Textured PEI",
            "notes": (
                f"SILL SA — Typologie {typo_code}\n"
                f"Qualité: {quality['label']}\n"
                f"Échelle: 1:{scale_denom}\n"
                f"Preset: {preset['label']}\n"
            ),
        }

        if multi_color:
            project_settings["ams_mapping"] = [
                {
                    "part": name,
                    "color": AMS_COLORS[name]["hex"],
                    "color_name": AMS_COLORS[name]["name"],
                    "ams_slot": AMS_COLORS[name]["slot"],
                }
                for name in ["dalle", "murs-ext", "cloisons", "accent"]
            ]

        zf.writestr(
            "Metadata/project_settings.json",
            json.dumps(project_settings, indent=2, ensure_ascii=False),
        )

        # ─── Metadata/print_info.json ────────────────────
        surface_m2 = (typo["largeur_mm"] / 1000) * (typo["profondeur_mm"] / 1000)
        print_info = {
            "typology": typo_code,
            "name": typo["nom"],
            "quality_level": quality["label"],
            "quality_description": quality["description"],
            "scale": f"1:{scale_denom}",
            "real_dimensions_m": f"{typo['largeur_mm']/1000:.1f} × {typo['profondeur_mm']/1000:.1f}",
            "print_dimensions_mm": f"{typo['largeur_mm']/scale_denom:.1f} × {typo['profondeur_mm']/scale_denom:.1f}",
            "surface_brute_m2": round(surface_m2, 1),
            "pieces": [
                {
                    "nom": p["nom"],
                    "type": p["type"],
                    "surface_m2": round((p["w"] / 1000) * (p["h"] / 1000), 1),
                }
                for p in typo["pieces"]
            ],
            "preset": preset_name,
            "estimated_print_time_min": preset["estimated_time_min"],
        }

        zf.writestr(
            "Metadata/print_info.json",
            json.dumps(print_info, indent=2, ensure_ascii=False),
        )

    print(f"  → {output_path.name} créé")
    return str(output_path)


def main():
    parser = argparse.ArgumentParser(
        description="SILL Typologies — Projet Bambu Studio (.3mf)"
    )
    parser.add_argument(
        "--typology", "-t", default="all",
        help=f"Code de la typologie ({', '.join(TYPOLOGIES_3D.keys())}, all)"
    )
    parser.add_argument(
        "--scale", "-s", default="1:100",
        help="Échelle (1:50, 1:75, 1:100)"
    )
    parser.add_argument(
        "--output", "-o", default="output/3mf",
        help="Répertoire de sortie"
    )
    parser.add_argument(
        "--preset", "-p", default="presentation",
        choices=PRINT_PRESETS.keys(),
        help="Preset d'impression"
    )
    parser.add_argument(
        "--mono", action="store_true",
        help="Mode mono-couleur (pas d'AMS)"
    )

    args = parser.parse_args()

    scale_denom = int(args.scale.replace(" ", "").split(":")[-1])
    output_dir = Path(args.output)
    multi_color = not args.mono

    print(f"\n{'='*60}")
    print(f"  SILL Typologies → Bambu Studio 3MF")
    print(f"  Preset: {PRINT_PRESETS[args.preset]['label']}")
    print(f"  Échelle: 1:{scale_denom}")
    print(f"  Multi-couleur AMS: {'oui' if multi_color else 'non'}")
    print(f"{'='*60}")

    files = []
    if args.typology == "all":
        for code in TYPOLOGIES_3D:
            path = output_dir / f"SILL_{code}.3mf"
            create_3mf(code, scale_denom, path, args.preset, multi_color)
            files.append(str(path))
    else:
        if args.typology not in TYPOLOGIES_3D:
            print(f"Erreur: '{args.typology}' inconnue. Disponibles: {', '.join(TYPOLOGIES_3D.keys())}")
            sys.exit(1)
        path = output_dir / f"SILL_{args.typology}.3mf"
        create_3mf(args.typology, scale_denom, path, args.preset, multi_color)
        files.append(str(path))

    print(f"\n{'='*60}")
    print(f"  {len(files)} fichier(s) .3mf générés dans {output_dir}/")
    print(f"\n  Pour imprimer:")
    print(f"  1. Ouvrir Bambu Studio")
    print(f"  2. Fichier → Ouvrir un projet → sélectionner le .3mf")
    print(f"  3. Les paramètres et couleurs AMS sont pré-configurés")
    print(f"  4. Vérifier le placement sur le plateau")
    print(f"  5. Lancer le slice puis envoyer à l'imprimante")
    if multi_color:
        print(f"\n  Configuration AMS recommandée:")
        for part, color in AMS_COLORS.items():
            print(f"     Slot {color['slot']}: {color['name']} ({color['hex']}) → {part}")
    print(f"{'='*60}")


if __name__ == "__main__":
    main()
