#!/usr/bin/env python3
"""
SILL Typologies — Analyseur de plans PDF

Extrait les dessins vectoriels des PDF architecturaux et les convertit en DXF.
Supporte les plans, coupes et façades.

Workflow:
    1. Ouverture du PDF avec PyMuPDF (fitz)
    2. Extraction des chemins vectoriels (lignes, courbes, polygones)
    3. Détection de l'échelle via les cotations (1:50, 1:100, etc.)
    4. Conversion en entités DXF
    5. Export DXF compatible Fusion 360

Usage:
    python pdf_plan_analyzer.py --input sources/pdf/plans/etage-type.pdf --output output/dxf-fusion/
    python pdf_plan_analyzer.py --input sources/pdf/plans/ --scale 1:100 --output output/dxf-fusion/
"""

import argparse
import json
import math
import re
import sys
from pathlib import Path

try:
    import fitz  # PyMuPDF
except ImportError:
    print("Erreur: PyMuPDF requis. Installez avec: pip install PyMuPDF>=1.24.0")
    sys.exit(1)

try:
    import ezdxf
    from ezdxf import units
except ImportError:
    print("Erreur: ezdxf requis. Installez avec: pip install ezdxf>=1.3.0")
    sys.exit(1)


# Échelles courantes en architecture suisse
SCALES = {
    "1:20": 20,
    "1:50": 50,
    "1:100": 100,
    "1:200": 200,
    "1:500": 500,
}


def detect_scale(page: fitz.Page) -> int:
    """
    Détecte l'échelle du plan en cherchant les annotations de type 1:XX.
    Retourne le dénominateur (50, 100, etc.) ou 100 par défaut.
    """
    text = page.get_text()
    for scale_str, scale_val in SCALES.items():
        if scale_str in text:
            return scale_val

    # Chercher aussi des formats alternatifs
    match = re.search(r"[Éé]ch(?:elle)?[\s.:]*1\s*[:/]\s*(\d+)", text, re.IGNORECASE)
    if match:
        return int(match.group(1))

    return 100  # Par défaut 1:100


def extract_vectors_from_page(page: fitz.Page) -> list:
    """
    Extrait tous les chemins vectoriels d'une page PDF.

    Returns:
        Liste de dictionnaires {type, points, color, width}
    """
    vectors = []
    paths = page.get_drawings()

    for path in paths:
        for item in path["items"]:
            kind = item[0]  # "l" (line), "re" (rect), "qu" (quad), "c" (curve)

            if kind == "l":
                # Ligne: (type, p1, p2)
                p1, p2 = item[1], item[2]
                vectors.append({
                    "type": "line",
                    "points": [(p1.x, p1.y), (p2.x, p2.y)],
                    "color": path.get("color", (0, 0, 0)),
                    "width": path.get("width", 0.5),
                })

            elif kind == "re":
                # Rectangle: (type, rect)
                rect = item[1]
                vectors.append({
                    "type": "rect",
                    "points": [
                        (rect.x0, rect.y0),
                        (rect.x1, rect.y0),
                        (rect.x1, rect.y1),
                        (rect.x0, rect.y1),
                    ],
                    "color": path.get("color", (0, 0, 0)),
                    "width": path.get("width", 0.5),
                })

            elif kind == "c":
                # Courbe de Bézier: (type, p1, p2, p3, p4)
                vectors.append({
                    "type": "curve",
                    "points": [(p.x, p.y) for p in item[1:]],
                    "color": path.get("color", (0, 0, 0)),
                    "width": path.get("width", 0.5),
                })

    return vectors


def vectors_to_dxf(vectors: list, scale: int, output_path: Path, page_height: float):
    """
    Convertit les vecteurs extraits en fichier DXF.

    Les coordonnées PDF (origine en haut à gauche) sont converties
    en coordonnées DXF (origine en bas à gauche).
    Les unités sont converties en mm réels selon l'échelle.
    """
    doc = ezdxf.new("R2018")
    doc.header["$INSUNITS"] = units.MM
    msp = doc.modelspace()

    # Créer les calques
    doc.layers.new("MURS", dxfattribs={"color": 7})       # Blanc
    doc.layers.new("CLOISONS", dxfattribs={"color": 8})   # Gris
    doc.layers.new("OUVERTURES", dxfattribs={"color": 3})  # Vert
    doc.layers.new("DIVERS", dxfattribs={"color": 5})     # Bleu

    # Facteur de conversion: PDF points → mm réels
    # 1 PDF point = 1/72 inch = 0.3528 mm (sur papier)
    # × échelle = dimension réelle
    pt_to_mm = (25.4 / 72.0) * scale

    def transform(x, y):
        """PDF → DXF: flip Y, scale to real mm."""
        return (x * pt_to_mm, (page_height - y) * pt_to_mm)

    for vec in vectors:
        width = vec["width"]

        # Heuristique: les murs ont des traits plus épais
        if width >= 1.0:
            layer = "MURS"
        elif width >= 0.5:
            layer = "CLOISONS"
        elif width >= 0.2:
            layer = "OUVERTURES"
        else:
            layer = "DIVERS"

        if vec["type"] == "line":
            p1 = transform(*vec["points"][0])
            p2 = transform(*vec["points"][1])
            msp.add_line(p1, p2, dxfattribs={"layer": layer})

        elif vec["type"] == "rect":
            points = [transform(*p) for p in vec["points"]]
            points.append(points[0])  # Fermer le rectangle
            msp.add_lwpolyline(points, dxfattribs={"layer": layer}, close=True)

        elif vec["type"] == "curve":
            # Approximer la courbe de Bézier par des segments
            pts = [transform(*p) for p in vec["points"]]
            if len(pts) == 4:
                # Bézier cubique → 10 segments
                bezier_pts = approximate_bezier(pts, segments=10)
                msp.add_lwpolyline(bezier_pts, dxfattribs={"layer": layer})

    output_path.parent.mkdir(parents=True, exist_ok=True)
    doc.saveas(str(output_path))


def approximate_bezier(control_points: list, segments: int = 10) -> list:
    """Approxime une courbe de Bézier cubique par des segments de droite."""
    p0, p1, p2, p3 = control_points
    points = []
    for i in range(segments + 1):
        t = i / segments
        t2 = t * t
        t3 = t2 * t
        mt = 1 - t
        mt2 = mt * mt
        mt3 = mt2 * mt

        x = mt3 * p0[0] + 3 * mt2 * t * p1[0] + 3 * mt * t2 * p2[0] + t3 * p3[0]
        y = mt3 * p0[1] + 3 * mt2 * t * p1[1] + 3 * mt * t2 * p2[1] + t3 * p3[1]
        points.append((x, y))

    return points


def analyze_pdf(input_path: Path, output_dir: Path, forced_scale: int = None) -> dict:
    """
    Analyse un PDF de plan et génère le DXF correspondant.

    Returns:
        dict avec les métadonnées d'analyse
    """
    pdf = fitz.open(str(input_path))
    stats = {
        "input": str(input_path),
        "pages": len(pdf),
        "outputs": [],
    }

    for page_num in range(len(pdf)):
        page = pdf[page_num]

        # Détecter ou forcer l'échelle
        scale = forced_scale or detect_scale(page)

        # Extraire les vecteurs
        vectors = extract_vectors_from_page(page)

        if not vectors:
            print(f"  Page {page_num + 1}: aucun vecteur trouvé, ignorée")
            continue

        # Générer le nom de sortie
        stem = input_path.stem
        suffix = f"_p{page_num + 1}" if len(pdf) > 1 else ""
        output_file = output_dir / f"{stem}{suffix}.dxf"

        # Convertir en DXF
        vectors_to_dxf(vectors, scale, output_file, page.rect.height)

        page_stats = {
            "page": page_num + 1,
            "scale": f"1:{scale}",
            "vectors": len(vectors),
            "output": str(output_file),
        }
        stats["outputs"].append(page_stats)
        print(f"  Page {page_num + 1}: {len(vectors)} vecteurs, échelle 1:{scale} → {output_file.name}")

    pdf.close()
    return stats


def main():
    parser = argparse.ArgumentParser(
        description="SILL Typologies — Analyse de plans PDF → DXF"
    )
    parser.add_argument(
        "--input", "-i", required=True,
        help="Fichier PDF ou répertoire de PDF"
    )
    parser.add_argument(
        "--output", "-o", required=True,
        help="Répertoire de sortie pour les DXF"
    )
    parser.add_argument(
        "--scale", "-s",
        help="Forcer l'échelle (ex: 1:50, 1:100). Auto-détection par défaut."
    )
    parser.add_argument(
        "--report", "-r", action="store_true",
        help="Générer un rapport JSON"
    )

    args = parser.parse_args()
    input_path = Path(args.input)
    output_dir = Path(args.output)

    # Parser l'échelle forcée
    forced_scale = None
    if args.scale:
        match = re.match(r"1:(\d+)", args.scale)
        if match:
            forced_scale = int(match.group(1))
        else:
            print(f"Erreur: format d'échelle invalide '{args.scale}'. Utilisez 1:50, 1:100, etc.")
            sys.exit(1)

    all_stats = []

    if input_path.is_file():
        print(f"Analyse: {input_path.name}")
        stats = analyze_pdf(input_path, output_dir, forced_scale)
        all_stats.append(stats)
    elif input_path.is_dir():
        pdf_files = sorted(input_path.glob("**/*.pdf"))
        if not pdf_files:
            print(f"Aucun PDF trouvé dans {input_path}")
            sys.exit(1)
        for pdf_file in pdf_files:
            print(f"\nAnalyse: {pdf_file.name}")
            stats = analyze_pdf(pdf_file, output_dir, forced_scale)
            all_stats.append(stats)
    else:
        print(f"Erreur: {input_path} n'existe pas")
        sys.exit(1)

    if args.report:
        report_path = output_dir / "rapport-pdf-analyse.json"
        report_path.parent.mkdir(parents=True, exist_ok=True)
        with open(report_path, "w", encoding="utf-8") as f:
            json.dump(all_stats, f, indent=2, ensure_ascii=False)
        print(f"\nRapport: {report_path}")

    total_pages = sum(len(s["outputs"]) for s in all_stats)
    print(f"\nTerminé: {len(all_stats)} PDF → {total_pages} DXF générés")


if __name__ == "__main__":
    main()
