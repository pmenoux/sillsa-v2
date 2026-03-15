#!/usr/bin/env python3
"""
SILL Typologies — DXF to Fusion 360 Converter

Lit les fichiers DXF source (plans, coupes, façades), les nettoie et les
optimise pour importation dans Autodesk Fusion 360.

Workflow:
    1. Lecture du DXF source (plans architecte)
    2. Nettoyage des calques inutiles (hachures, annotations lourdes)
    3. Regroupement par typologie (détection automatique des pièces)
    4. Export DXF R2018 compatible Fusion 360
    5. Génération optionnelle d'un rapport de surfaces

Usage:
    python dxf_to_fusion.py --input sources/dxf/plans/ --output output/dxf-fusion/
    python dxf_to_fusion.py --input sources/dxf/plans/etage-type.dxf --output output/dxf-fusion/
    python dxf_to_fusion.py --input sources/dxf/plans/ --report --output output/dxf-fusion/
"""

import argparse
import json
import sys
from pathlib import Path

try:
    import ezdxf
    from ezdxf import units
    from ezdxf.addons import odafc
except ImportError:
    print("Erreur: ezdxf requis. Installez avec: pip install ezdxf>=1.3.0")
    sys.exit(1)

# Calques à exclure pour alléger le fichier Fusion 360
LAYERS_TO_SKIP = {
    "HATCH", "HACHURES", "DEFPOINTS", "VIEWPORT",
    "XREF", "ANNO", "ANNOTATIONS", "DIM", "DIMENSIONS",
    "TEXTE", "TEXT", "COTATIONS", "TITLE", "TITLEBLOCK",
    "CARTOUCHE", "BORDER", "CADRE",
}

# Calques structurels à conserver
LAYERS_STRUCTURAL = {
    "MURS", "WALLS", "MUR", "WALL",
    "CLOISONS", "PARTITIONS", "PARTITION",
    "PORTES", "DOORS", "DOOR",
    "FENETRES", "WINDOWS", "WINDOW",
    "ESCALIER", "STAIRS", "STAIR",
    "SANITAIRE", "PLUMBING", "CUISINE", "KITCHEN",
    "MOBILIER", "FURNITURE",
    "AXE", "AXES", "GRID",
    "0",
}


def clean_dxf_for_fusion(input_path: Path, output_path: Path, keep_all_layers: bool = False) -> dict:
    """
    Nettoie un DXF pour importation dans Fusion 360.

    Returns:
        dict avec les métadonnées du traitement
    """
    doc = ezdxf.readfile(str(input_path))
    msp = doc.modelspace()

    stats = {
        "input": str(input_path),
        "output": str(output_path),
        "layers_found": [],
        "layers_kept": [],
        "layers_removed": [],
        "entities_before": 0,
        "entities_after": 0,
    }

    # Inventaire des calques
    for layer in doc.layers:
        stats["layers_found"].append(layer.dxf.name)

    # Compter les entités avant nettoyage
    stats["entities_before"] = len(list(msp))

    if not keep_all_layers:
        # Supprimer les entités des calques non structurels
        entities_to_delete = []
        for entity in msp:
            layer_name = entity.dxf.layer.upper()
            if layer_name in LAYERS_TO_SKIP:
                entities_to_delete.append(entity)

        for entity in entities_to_delete:
            msp.delete_entity(entity)
            if entity.dxf.layer not in stats["layers_removed"]:
                stats["layers_removed"].append(entity.dxf.layer)

    # Entités restantes
    stats["entities_after"] = len(list(msp))
    stats["layers_kept"] = [
        l for l in stats["layers_found"]
        if l not in stats["layers_removed"]
    ]

    # Forcer les unités en millimètres (standard Fusion 360)
    doc.header["$INSUNITS"] = units.MM

    # Sauvegarder en DXF R2018 (meilleure compatibilité Fusion 360)
    output_path.parent.mkdir(parents=True, exist_ok=True)
    doc.saveas(str(output_path), fmt="asc")

    return stats


def extract_room_boundaries(input_path: Path) -> list:
    """
    Tente d'extraire les contours des pièces depuis un DXF.
    Cherche les polylignes fermées sur les calques de murs.

    Returns:
        Liste de pièces détectées avec surfaces approximatives
    """
    doc = ezdxf.readfile(str(input_path))
    msp = doc.modelspace()
    rooms = []

    for entity in msp:
        if entity.dxftype() in ("LWPOLYLINE", "POLYLINE"):
            if not entity.is_closed:
                continue

            layer = entity.dxf.layer.upper()
            # Chercher les polylignes sur calques pertinents
            if any(kw in layer for kw in ("PIECE", "ROOM", "ZONE", "TYPO", "LOGEMENT", "APT")):
                points = list(entity.vertices())
                if len(points) >= 3:
                    # Calcul de surface par formule du lacet (Shoelace)
                    area = abs(sum(
                        points[i][0] * points[(i + 1) % len(points)][1]
                        - points[(i + 1) % len(points)][0] * points[i][1]
                        for i in range(len(points))
                    )) / 2.0

                    # Convertir en m² si unité est mm
                    area_m2 = area / 1_000_000

                    rooms.append({
                        "layer": entity.dxf.layer,
                        "area_m2": round(area_m2, 2),
                        "vertices": len(points),
                    })

    return rooms


def process_directory(input_dir: Path, output_dir: Path, report: bool = False) -> list:
    """Traite tous les DXF d'un répertoire."""
    all_stats = []

    dxf_files = sorted(input_dir.glob("**/*.dxf"))
    if not dxf_files:
        print(f"Aucun fichier DXF trouvé dans {input_dir}")
        return all_stats

    for dxf_file in dxf_files:
        # Conserver la structure de sous-dossiers
        relative = dxf_file.relative_to(input_dir)
        output_file = output_dir / relative.with_stem(f"{relative.stem}_fusion")

        print(f"Traitement: {dxf_file.name}")
        stats = clean_dxf_for_fusion(dxf_file, output_file)

        if report:
            rooms = extract_room_boundaries(dxf_file)
            stats["rooms_detected"] = rooms
            if rooms:
                stats["total_area_m2"] = round(sum(r["area_m2"] for r in rooms), 2)

        all_stats.append(stats)
        print(f"  → {output_file.name} ({stats['entities_before']} → {stats['entities_after']} entités)")

    return all_stats


def main():
    parser = argparse.ArgumentParser(
        description="SILL Typologies — Conversion DXF → Fusion 360"
    )
    parser.add_argument(
        "--input", "-i", required=True,
        help="Fichier DXF ou répertoire de fichiers DXF source"
    )
    parser.add_argument(
        "--output", "-o", required=True,
        help="Répertoire de sortie pour les DXF optimisés"
    )
    parser.add_argument(
        "--report", "-r", action="store_true",
        help="Générer un rapport JSON des surfaces détectées"
    )
    parser.add_argument(
        "--keep-all-layers", action="store_true",
        help="Conserver tous les calques (pas de nettoyage)"
    )

    args = parser.parse_args()
    input_path = Path(args.input)
    output_dir = Path(args.output)

    if input_path.is_file():
        output_file = output_dir / f"{input_path.stem}_fusion.dxf"
        stats = clean_dxf_for_fusion(input_path, output_file, args.keep_all_layers)
        all_stats = [stats]
        print(f"✓ {output_file} ({stats['entities_before']} → {stats['entities_after']} entités)")
    elif input_path.is_dir():
        all_stats = process_directory(input_path, output_dir, args.report)
    else:
        print(f"Erreur: {input_path} n'existe pas")
        sys.exit(1)

    if args.report and all_stats:
        report_path = output_dir / "rapport-conversion.json"
        report_path.parent.mkdir(parents=True, exist_ok=True)
        with open(report_path, "w", encoding="utf-8") as f:
            json.dump(all_stats, f, indent=2, ensure_ascii=False)
        print(f"\nRapport: {report_path}")

    print(f"\nTerminé: {len(all_stats)} fichier(s) traité(s)")


if __name__ == "__main__":
    main()
