#!/usr/bin/env python3
"""
SILL SA — Script de migration WordPress → sillsa.ch v2
Génère les INSERT SQL à partir de l'export XML WordPress.
"""

import xml.etree.ElementTree as ET
import re
import html
from datetime import datetime

XML_PATH = '/mnt/user-data/uploads/sillsa2026_WordPress_2026-03-05.xml'

# --- Parse XML ---
with open(XML_PATH, 'r', encoding='utf-8') as f:
    xml_content = f.read().lstrip('\r\n\ufeff ')

root = ET.fromstring(xml_content)
channel = root.find('channel')
ns_wp = '{http://wordpress.org/export/1.2/}'
ns_content = '{http://purl.org/rss/1.0/modules/content/}'

items = channel.findall('item')

def esc(s):
    """Escape SQL string"""
    if not s:
        return 'NULL'
    s = s.replace("\\", "\\\\").replace("'", "\\'").replace("\n", "\\n").replace("\r", "")
    return f"'{s}'"

def strip_html(s):
    """Remove HTML tags, clean whitespace"""
    if not s:
        return s
    s = re.sub(r'<[^>]+>', '', s)
    s = html.unescape(s)
    s = re.sub(r'\s+', ' ', s).strip()
    return s

def get_metas(item):
    metas = {}
    for meta in item.findall(f'{ns_wp}postmeta'):
        key = meta.findtext(f'{ns_wp}meta_key', '')
        val = meta.findtext(f'{ns_wp}meta_value', '')
        metas[key] = val
    return metas

def decode_event_date(raw, title):
    """
    Decode ACF event_date field (format like Y1020, Y61, Y1231)
    Extract year from title, month+day from the code
    """
    # Extract year from title
    year_match = re.match(r'(\d{4})', title)
    year = int(year_match.group(1)) if year_match else 2009
    
    if not raw or raw == '':
        return f'{year}-01-01'
    
    # Remove leading Y
    code = raw.lstrip('Y')
    
    if len(code) <= 2:
        # e.g. Y61 = June 1st, Y84 = Aug 4th
        month = int(code[0]) if len(code) >= 1 else 1
        day = int(code[1]) if len(code) >= 2 else 1
    elif len(code) == 3:
        # Try 2-digit month first: Y101=Oct 1, Y121=Dec 1
        # If month > 12, use 1-digit month: Y215=Feb 15, Y915=Sep 15
        month = int(code[:2])
        day = int(code[2:])
        if month > 12:
            month = int(code[:1])
            day = int(code[1:])
    elif len(code) == 4:
        # e.g. Y1020 = month 10, day 20; Y1231 = month 12, day 31
        month = int(code[:2])
        day = int(code[2:])
    else:
        month, day = 1, 1
    
    # Clamp values
    month = max(1, min(12, month))
    day = max(1, min(28, day))  # Safe default
    
    return f'{year}-{month:02d}-{day:02d}'


print("-- ============================================================")
print("-- SILL SA — Migration WordPress → sillsa.ch v2")
print(f"-- Généré le {datetime.now().strftime('%Y-%m-%d %H:%M')}")
print("-- ============================================================")
print()

# --- MÉDIAS ---
print("-- -----------------------------------------------------------")
print("-- MÉDIAS")
print("-- -----------------------------------------------------------")
for item in items:
    if item.findtext(f'{ns_wp}post_type') != 'attachment':
        continue
    pid = item.findtext(f'{ns_wp}post_id')
    title = item.findtext('title', '')
    url = item.findtext(f'{ns_wp}attachment_url', '')
    # Extract relative path from URL
    filepath = url.replace('http://sillsa.ch/', '/').replace('https://sillsa.ch/', '/')
    filename = url.split('/')[-1] if url else ''
    mime = item.findtext(f'{ns_wp}post_type', 'image/jpeg')
    # Get actual mime from postmeta or guess
    if filename.endswith('.pdf'):
        mime = 'application/pdf'
    elif filename.endswith('.png'):
        mime = 'image/png'
    elif filename.endswith('.webp'):
        mime = 'image/webp'
    elif filename.endswith('.avif'):
        mime = 'image/avif'
    else:
        mime = 'image/jpeg'
    
    print(f"INSERT INTO sill_medias (id, filename, filepath, alt_text, mime_type) VALUES ({pid}, {esc(filename)}, {esc(filepath)}, {esc(title)}, {esc(mime)});")

print()

# --- PAGES ---
print("-- -----------------------------------------------------------")
print("-- PAGES STATIQUES")
print("-- -----------------------------------------------------------")
sort = 0
for item in items:
    if item.findtext(f'{ns_wp}post_type') != 'page':
        continue
    if item.findtext(f'{ns_wp}status') != 'publish':
        continue
    sort += 1
    pid = item.findtext(f'{ns_wp}post_id')
    title = item.findtext('title', '')
    slug = item.findtext(f'{ns_wp}post_name', '')
    content = item.findtext(f'{ns_content}encoded', '')
    # Strip Elementor shortcodes but keep HTML content
    content = re.sub(r'<!-- wp:.*?-->', '', content)
    content = re.sub(r'<!-- /wp:.*?-->', '', content)
    content = content.strip()
    
    print(f"INSERT INTO sill_pages (slug, title, content, sort_order) VALUES ({esc(slug)}, {esc(title)}, {esc(content)}, {sort});")

print()

# --- IMMEUBLES (Portefeuille) ---
print("-- -----------------------------------------------------------")
print("-- PORTEFEUILLE IMMOBILIER")
print("-- -----------------------------------------------------------")
sort = 0
for item in items:
    if item.findtext(f'{ns_wp}post_type') != 'post':
        continue
    if item.findtext(f'{ns_wp}status') != 'publish':
        continue
    cats = [c.text for c in item.findall('category') if c.get('domain') == 'category']
    if 'Portefeuille' not in cats:
        continue
    
    sort += 1
    metas = get_metas(item)
    slug = item.findtext(f'{ns_wp}post_name', '')
    nom = item.findtext('title', '')
    chapeau = metas.get('chapeau', '')
    desc = metas.get('description', '')
    details = metas.get('texte_details', '')
    adresse = metas.get('adresse', '')
    img_id = metas.get('illustration', '')
    img_val = f"'{img_id}'" if img_id and img_id.isdigit() else 'NULL'
    
    print(f"INSERT INTO sill_immeubles (slug, nom, chapeau, description, details, adresse, image_id, sort_order) VALUES ({esc(slug)}, {esc(nom)}, {esc(chapeau)}, {esc(desc)}, {esc(details)}, {esc(adresse)}, {img_val}, {sort});")

print()

# --- ACTUALITÉS ---
print("-- -----------------------------------------------------------")
print("-- ACTUALITÉS")
print("-- -----------------------------------------------------------")
for item in items:
    if item.findtext(f'{ns_wp}post_type') != 'post':
        continue
    if item.findtext(f'{ns_wp}status') != 'publish':
        continue
    cats = [c.text for c in item.findall('category') if c.get('domain') == 'category']
    if 'Actualités' not in cats:
        continue
    
    metas = get_metas(item)
    slug = item.findtext(f'{ns_wp}post_name', '')
    title = item.findtext('title', '')
    chapeau = metas.get('chapeau', '')
    desc = metas.get('description', '')
    details = metas.get('texte_details', '')
    adresse = metas.get('adresse', '')
    img_id = metas.get('illustration', '')
    img_val = f"'{img_id}'" if img_id and img_id.isdigit() else 'NULL'
    pub_date = item.findtext(f'{ns_wp}post_date', '')[:10]
    
    print(f"INSERT INTO sill_actualites (slug, title, chapeau, description, details, image_id, adresse, published_at) VALUES ({esc(slug)}, {esc(title)}, {esc(chapeau)}, {esc(desc)}, {esc(details)}, {img_val}, {esc(adresse)}, '{pub_date}');")

print()

# --- TIMELINE ---
print("-- -----------------------------------------------------------")
print("-- TIMELINE")
print("-- -----------------------------------------------------------")
sort = 0
for item in items:
    if item.findtext(f'{ns_wp}post_type') != 'sill_timeline':
        continue
    if item.findtext(f'{ns_wp}status') != 'publish':
        continue
    
    sort += 1
    metas = get_metas(item)
    slug = item.findtext(f'{ns_wp}post_name', '')
    title = item.findtext('title', '')
    raw_date = metas.get('event_date', '')
    event_date = decode_event_date(raw_date, title)
    category = metas.get('event_category', 'gouvernance')
    desc = metas.get('event_description', '') or item.findtext(f'{ns_content}encoded', '')
    desc = strip_html(desc)
    img_id = metas.get('event_image', '')
    img_val = f"'{img_id}'" if img_id and img_id.isdigit() and int(img_id) > 0 else 'NULL'
    link = metas.get('event_link', '')
    
    print(f"INSERT INTO sill_timeline (slug, title, event_date, category, description, image_id, link_url, sort_order) VALUES ({esc(slug)}, {esc(title)}, '{event_date}', {esc(category)}, {esc(desc)}, {img_val}, {esc(link)}, {sort});")

print()

# --- PUBLICATIONS (extraites des médias PDF) ---
print("-- -----------------------------------------------------------")
print("-- PUBLICATIONS (Rapports annuels)")
print("-- -----------------------------------------------------------")
ra_pdfs = []
for item in items:
    if item.findtext(f'{ns_wp}post_type') != 'attachment':
        continue
    url = item.findtext(f'{ns_wp}attachment_url', '')
    title = item.findtext('title', '')
    pid = item.findtext(f'{ns_wp}post_id')
    if url.endswith('.pdf') and ('rapport' in title.lower() or 'RA' in title):
        # Extract year from filename
        year_match = re.search(r'20[12]\d', title + url)
        year = year_match.group(0) if year_match else '2020'
        filepath = url.replace('http://sillsa.ch/', '/').replace('https://sillsa.ch/', '/')
        slug = f"rapport-annuel-{year}"
        ra_pdfs.append((slug, title, year, filepath, pid))

# Sort by year, deduplicate
seen_years = set()
for slug, title, year, filepath, pid in sorted(ra_pdfs, key=lambda x: x[2]):
    if year in seen_years and 'Short' not in title and 'port' not in title:
        continue
    seen_years.add(year)
    print(f"INSERT INTO sill_publications (slug, title, annee, type, pdf_path) VALUES ({esc(slug)}, {esc(title)}, {year}, 'rapport_annuel', {esc(filepath)});")

# ESG
print(f"INSERT INTO sill_publications (slug, title, annee, type, pdf_path) VALUES ('esg-2024', 'Performance ESG 2024', 2024, 'esg', NULL);")

print()
print("-- ============================================================")
print("-- FIN MIGRATION")
print("-- ============================================================")

