<?php
// config.php — SILL SA sillsa.ch v2
// BDD credentials: fill in after creating MariaDB on Infomaniak

define('DB_HOST', 'localhost');
define('DB_NAME', 'sillsa_v2');
define('DB_USER', 'sillsa_admin');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

define('SITE_URL', 'https://26.sillsa.ch');
define('SITE_NAME', 'SILL SA');
define('SITE_TAGLINE', 'Société Immobilière Lausannoise pour le Logement SA');

define('UPLOADS_DIR', __DIR__ . '/uploads');
define('UPLOADS_URL', SITE_URL . '/uploads');
define('SITE_ROOT', __DIR__);

// PDO connection
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}
