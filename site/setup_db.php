<?php
// Setup admin DB — a supprimer apres execution
require_once __DIR__ . '/config.php';
$pdo = db();

// Table login attempts
$pdo->exec("CREATE TABLE IF NOT EXISTS sill_login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username_ip (username, ip_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "Table sill_login_attempts OK\n";

// Verifier colonnes sill_users
$cols = array_column($pdo->query("SHOW COLUMNS FROM sill_users")->fetchAll(), 'Field');
if (!in_array('password_hash', $cols)) {
    $pdo->exec("ALTER TABLE sill_users ADD COLUMN password_hash VARCHAR(255)");
    echo "Ajout colonne password_hash\n";
}
if (!in_array('is_active', $cols)) {
    $pdo->exec("ALTER TABLE sill_users ADD COLUMN is_active TINYINT(1) DEFAULT 1");
    echo "Ajout colonne is_active\n";
}

// User pmenoux — mot de passe: Zer 3920!
$hash = '$2y$10$pasREnsKEIRZwaPdsGEYmOiP73PZ2O7gL/DCKQ/AKNhdmxO4oy8jW';
$ex = $pdo->prepare("SELECT id FROM sill_users WHERE username = ?");
$ex->execute(['pmenoux']);
if ($ex->fetch()) {
    $pdo->prepare("UPDATE sill_users SET password_hash = ?, is_active = 1 WHERE username = ?")
        ->execute([$hash, 'pmenoux']);
    echo "User pmenoux mis a jour\n";
} else {
    $pdo->prepare("INSERT INTO sill_users (username, password_hash, is_active) VALUES (?, ?, 1)")
        ->execute(['pmenoux', $hash]);
    echo "User pmenoux cree\n";
}

echo "\nDone! Supprimez ce fichier.\n";
