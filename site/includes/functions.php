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

function immeubleCoverUrl(string $slug, ?int $imageId = null): string {
    // 1. New system: media/immeubles/{slug}/cover.*
    $dir = SITE_ROOT . '/media/immeubles/' . $slug;
    if (is_dir($dir)) {
        $covers = glob($dir . '/cover.{jpg,jpeg,png,webp}', GLOB_BRACE);
        if ($covers) {
            $filename = basename($covers[0]);
            return SITE_URL . '/media/immeubles/' . $slug . '/' . $filename . '?v=' . filemtime($covers[0]);
        }
    }
    // 2. Fallback: legacy image_id
    if ($imageId) {
        return mediaUrl($imageId);
    }
    // 3. Placeholder
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
