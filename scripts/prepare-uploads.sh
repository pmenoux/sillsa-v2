#!/bin/bash
# =============================================================
# SILL SA — Prepare uploads for deployment
# Copies referenced media files from WP export to site/uploads/
# =============================================================

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
WP_UPLOADS="$PROJECT_DIR/download (2)/web/wordpress/wp-content/uploads"
SITE_UPLOADS="$PROJECT_DIR/site/uploads"

echo "=== SILL SA — Media Upload Preparation ==="
echo "Source:      $WP_UPLOADS"
echo "Destination: $SITE_UPLOADS"
echo ""

# Create uploads directory structure
mkdir -p "$SITE_UPLOADS"

# Copy entire year-based structure (preserving paths)
# The mediaUrl() function maps /wp-content/uploads/YYYY/MM/file to /uploads/YYYY/MM/file
for year_dir in "$WP_UPLOADS"/20*; do
    if [ -d "$year_dir" ]; then
        year=$(basename "$year_dir")
        echo "Copying $year/ ..."
        cp -r "$year_dir" "$SITE_UPLOADS/"
    fi
done

# Copy photo-gallery if exists
if [ -d "$WP_UPLOADS/photo-gallery" ]; then
    echo "Copying photo-gallery/ ..."
    cp -r "$WP_UPLOADS/photo-gallery" "$SITE_UPLOADS/"
fi

# Count files
TOTAL=$(find "$SITE_UPLOADS" -type f | wc -l)
echo ""
echo "Done! $TOTAL files copied to site/uploads/"
echo ""
echo "Note: site/uploads/ is in .gitignore — deploy directly to server."
echo "For deployment, upload the site/uploads/ folder to 26.sillsa.ch/uploads/"
