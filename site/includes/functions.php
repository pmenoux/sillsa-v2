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
