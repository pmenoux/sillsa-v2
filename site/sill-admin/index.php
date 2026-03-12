<?php
// sill-admin/index.php — Admin router / front controller
// SILL SA v2 — PHP 8.2 vanilla

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/auth.php';

// ---------------------------------------------------------------------------
// Route resolution
// ---------------------------------------------------------------------------

$page   = preg_replace('/[^a-z_-]/', '', strtolower($_GET['page']   ?? 'dashboard'));
$action = preg_replace('/[^a-z_-]/', '', strtolower($_GET['action'] ?? 'list'));
$id     = isset($_GET['id']) ? (int) $_GET['id'] : null;

$publicPages = ['login'];
$validPages  = ['login', 'dashboard', 'kpi', 'pages', 'timeline', 'publications', 'settings', 'menu'];

// Reject unknown pages
if (!in_array($page, $validPages, true)) {
    $page = 'dashboard';
}

// ---------------------------------------------------------------------------
// Logout
// ---------------------------------------------------------------------------

if ($page === 'logout') {
    logout(); // redirects internally
    exit;
}

// ---------------------------------------------------------------------------
// Auth gate
// ---------------------------------------------------------------------------

if (!in_array($page, $publicPages, true)) {
    requireAuth();
}

// ---------------------------------------------------------------------------
// Dispatch
// ---------------------------------------------------------------------------

$pageFile = __DIR__ . '/' . $page . '.php';

// Login page renders directly (no layout)
if ($page === 'login') {
    require __DIR__ . '/login.php';
    exit;
}

// All other pages render inside the layout
if (!file_exists($pageFile)) {
    $pageFile = __DIR__ . '/dashboard.php';
}

require __DIR__ . '/layout.php';
