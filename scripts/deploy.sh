#!/bin/bash
# =============================================================
# SILL SA — Deployment script for 26.sillsa.ch (Infomaniak)
# =============================================================
# Usage: ./scripts/deploy.sh
#
# Prerequisites:
# 1. SSH/FTP access to Infomaniak configured
# 2. MariaDB database created on Infomaniak
# 3. schema_sillsa_v2.sql + migration_data.sql imported
# 4. Media files prepared (run prepare-uploads.sh first)
# =============================================================

set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
SITE_DIR="$PROJECT_DIR/site"

echo "=== SILL SA — Deployment Checklist ==="
echo ""
echo "1. Database Setup (Infomaniak Manager)"
echo "   - Create MariaDB database"
echo "   - Import: Datas actuelles/files/schema_sillsa_v2.sql"
echo "   - Import: Datas actuelles/files/migration_data.sql"
echo ""
echo "2. Configuration"
echo "   - Copy site/config.php to server"
echo "   - Update DB_HOST, DB_NAME, DB_USER, DB_PASS"
echo "   - Update SITE_URL to https://26.sillsa.ch"
echo ""
echo "3. Files to upload to 26.sillsa.ch root:"
echo "   site/.htaccess"
echo "   site/index.php"
echo "   site/config.php (with production credentials)"
echo "   site/robots.txt"
echo "   site/sitemap.php"
echo "   site/includes/"
echo "   site/templates/"
echo "   site/assets/"
echo "   site/uploads/ (run prepare-uploads.sh first)"
echo ""
echo "4. Server checks:"
echo "   - PHP 8.2+ enabled"
echo "   - mod_rewrite enabled"
echo "   - AllowOverride All"
echo ""
echo "5. Post-deployment:"
echo "   - Visit https://26.sillsa.ch/"
echo "   - Check /sitemap.xml"
echo "   - Test all routes"
echo "   - Test mobile responsive"
echo ""

# List files to deploy
echo "=== Files to deploy ==="
find "$SITE_DIR" -type f | sort | while read f; do
    echo "  ${f#$SITE_DIR/}"
done

echo ""
echo "Total: $(find "$SITE_DIR" -type f | wc -l) files"
