<?php
// includes/functions.php — Helper functions

/**
 * Execute a prepared query and return all results.
 */
function query(string $sql, array $params = []): array {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Execute a prepared query and return first result.
 */
function queryOne(string $sql, array $params = []): ?array {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Get a site setting value.
 */
function setting(string $key): ?string {
    $row = queryOne('SELECT setting_value FROM sill_settings WHERE setting_key = ?', [$key]);
    return $row ? $row['setting_value'] : null;
}

/**
 * Get menu items (with children).
 */
function getMenu(): array {
    $items = query('SELECT * FROM sill_menu WHERE is_active = 1 ORDER BY sort_order');
    $menu = [];
    $children = [];
    foreach ($items as $item) {
        if ($item['parent_id'] == 0) {
            $menu[$item['id']] = $item;
            $menu[$item['id']]['children'] = [];
        } else {
            $children[] = $item;
        }
    }
    foreach ($children as $child) {
        if (isset($menu[$child['parent_id']])) {
            $menu[$child['parent_id']]['children'][] = $child;
        }
    }
    return array_values($menu);
}

/**
 * Get page data by slug.
 */
function getPage(string $slug): ?array {
    return queryOne('SELECT * FROM sill_pages WHERE slug = ? AND is_active = 1', [$slug]);
}

/**
 * Get media URL by ID. Converts WP upload paths to local paths.
 */
function mediaUrl(int $id): string {
    $media = queryOne('SELECT filepath FROM sill_medias WHERE id = ?', [$id]);
    if (!$media) return SITE_URL . '/assets/img/placeholder.jpg';
    $path = str_replace('/wp-content/uploads/', '/uploads/', $media['filepath']);
    return SITE_URL . $path;
}

/**
 * Sanitize output for HTML.
 */
function e(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Check if current route matches and return active class.
 */
function isActive(string $route): string {
    global $pageData;
    return ($pageData['route'] === $route) ? ' class="active"' : '';
}

/**
 * Format a KPI numeric value: detect actual decimal places.
 * Returns ['formatted' => '1.25', 'decimals' => 2]
 */
function kpiFormat($value): array {
    if ($value === null) return ['formatted' => '', 'decimals' => 0];
    $num = (float)$value;
    // Format with 2 decimals, then strip trailing zeros
    $str = rtrim(rtrim(number_format($num, 2, '.', ''), '0'), '.');
    $decimals = 0;
    if (strpos($str, '.') !== false) {
        $decimals = strlen($str) - strpos($str, '.') - 1;
    }
    return ['formatted' => $str, 'decimals' => $decimals];
}

// ---------------------------------------------------------------------------
// Slug generation
// ---------------------------------------------------------------------------
function make_slug(string $title): string {
    $slug = strtolower(trim($title));
    $slug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $slug);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    return trim($slug, '-');
}

// ---------------------------------------------------------------------------
// Immeuble media helpers (filesystem-based)
// ---------------------------------------------------------------------------

function immeubleMediaPath(string $slug): string {
    return SITE_ROOT . '/media/immeubles/' . $slug;
}

function immeubleCoverUrl(string $slug): string {
    $dir = SITE_ROOT . '/media/immeubles/' . $slug;
    if (is_dir($dir)) {
        $covers = glob($dir . '/cover.{jpg,jpeg,png,webp}', GLOB_BRACE);
        if ($covers) {
            $filename = basename($covers[0]);
            return SITE_URL . '/media/immeubles/' . $slug . '/' . $filename . '?v=' . filemtime($covers[0]);
        }
    }
    return SITE_URL . '/assets/img/placeholder-immeuble.jpg';
}

function immeubleGalerie(string $slug): array {
    $dir = SITE_ROOT . '/media/immeubles/' . $slug;
    if (!is_dir($dir)) return [];
    $files = glob($dir . '/[0-9][0-9]-*.{jpg,jpeg,png,webp}', GLOB_BRACE);
    sort($files);
    $galerie = [];
    foreach ($files as $f) {
        $filename = basename($f);
        $caption = preg_replace('/^\d{2}-/', '', pathinfo($filename, PATHINFO_FILENAME));
        $caption = ucfirst(str_replace('-', ' ', $caption));
        $galerie[] = [
            'url'      => SITE_URL . '/media/immeubles/' . $slug . '/' . $filename . '?v=' . filemtime($f),
            'caption'  => $caption,
            'filename' => $filename,
        ];
    }
    return $galerie;
}

// ---------------------------------------------------------------------------
// Swiss Design typographic cleanup for rich text content
// ---------------------------------------------------------------------------

/**
 * Clean raw HTML (typically from WordPress) into proper Swiss Design typography.
 * - Decodes HTML entities (rsquo, laquo, raquo, ndash, mdash, hellip, nbsp)
 * - Converts <br><br> sequences into paragraph breaks
 * - Wraps orphan text in <p> tags
 * - Removes empty paragraphs
 * - Normalizes whitespace
 * - Strips Word/WP cruft (inline styles, empty spans, class attributes)
 */
function cleanSwissTypography(string $html): string {
    $html = trim($html);
    if ($html === '') return '';

    // 1. Decode HTML entities → proper UTF-8 characters
    $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // 2. Normalize common named entities that html_entity_decode may miss
    $html = str_replace(
        ['&rsquo;', '&lsquo;', '&rdquo;', '&ldquo;', '&laquo;', '&raquo;', '&ndash;', '&mdash;', '&hellip;', '&nbsp;', '&amp;'],
        ["'",       "'",       "\u{201D}", "\u{201C}", '«',       '»',       '–',       '—',       '…',        ' ',      '&'],
        $html
    );

    // 3. Strip inline styles, class attributes, and empty spans (WP cruft)
    $html = preg_replace('/\s+style="[^"]*"/i', '', $html);
    $html = preg_replace('/\s+class="[^"]*"/i', '', $html);
    $html = preg_replace('/<span\s*>\s*(.*?)\s*<\/span>/is', '$1', $html);

    // 4. Convert <br><br> (with optional whitespace/tags between) into paragraph breaks
    //    This handles: <br><br>, <br /><br />, <br>\n<br>, etc.
    $html = preg_replace('#(<br\s*/?\s*>[\s]*){2,}#i', '</p><p>', $html);

    // 5. If content has no <p> tags at all, wrap in <p>
    if (stripos($html, '<p>') === false && stripos($html, '<p ') === false) {
        $html = '<p>' . $html . '</p>';
    }

    // 6. Ensure content starts with <p> and ends with </p>
    $html = preg_replace('#^\s*(?!<p[\s>])#i', '<p>', $html, 1);
    if (!preg_match('#</p>\s*$#i', $html)) {
        $html .= '</p>';
    }

    // 7. Convert remaining single <br> inside paragraphs to proper sentence flow
    //    (remove <br> that are just WordPress line wrapping, not intentional breaks)
    $html = preg_replace('#<br\s*/?\s*>\s*#i', ' ', $html);

    // 8. Collapse multiple spaces into one
    $html = preg_replace('/[ \t]+/', ' ', $html);

    // 9. Remove empty paragraphs
    $html = preg_replace('#<p>\s*</p>#i', '', $html);

    // 10. Trim whitespace inside <p> tags
    $html = preg_replace('#<p>\s+#i', '<p>', $html);
    $html = preg_replace('#\s+</p>#i', '</p>', $html);

    // 11. Ensure proper spacing between paragraphs (newline for readability)
    $html = preg_replace('#</p>\s*<p>#i', "</p>\n<p>", $html);

    // 12. French typography: space before ; : ! ? » and after «
    $html = preg_replace('/\s+([;:!?»])/u', "\u{00A0}$1", $html);
    $html = preg_replace('/(«)\s+/u', "$1\u{00A0}", $html);

    return trim($html);
}

function uploadImmeubleImage(array $file, string $slug, string $targetName): string|false {
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    $maxSize = 20 * 1024 * 1024; // 20 Mo

    if ($file['error'] !== UPLOAD_ERR_OK) {
        flash('error', 'Erreur upload pour ' . e($file['name'] ?? $targetName) . '.');
        return false;
    }
    if ($file['size'] > $maxSize) {
        flash('error', 'Image trop lourde (max 5 Mo) : ' . e($file['name'] ?? $targetName));
        return false;
    }

    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($mime, $allowed, true)) {
        flash('error', 'Format non accepté (JPG, PNG ou WebP uniquement) : ' . e($file['name'] ?? $targetName));
        return false;
    }

    $ext = match($mime) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        default      => false,
    };
    if (!$ext) return false;

    $dir = immeubleMediaPath($slug);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $dest = $dir . '/' . $targetName . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return $targetName . '.' . $ext;
    }
    flash('error', 'Impossible de sauvegarder l\'image : ' . e($targetName));
    return false;
}
