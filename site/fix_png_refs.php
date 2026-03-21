<?php
/**
 * Vérifie et corrige les références .png -> .jpg en BDD
 * après compression des images.
 * À exécuter une fois puis supprimer.
 */
require_once __DIR__ . '/config.php';

header('Content-Type: text/plain; charset=utf-8');

$converted_pngs = [
    "Capture-d'écran-2018-05-18-à-09.11.43-1024x879.png",
    "Capture-d'écran-2018-05-18-à-09.11.43-1038x576.png",
    "Capture-d'écran-2018-05-18-à-09.11.43-672x372.png",
    "Capture-d'écran-2018-05-18-à-09.11.43-768x660.png",
    "Capture-d'écran-2018-05-18-à-09.11.43.png",
    "02_450maa_20200623-Photomontage-3D-1024x723.png",
    "02_450maa_20200623-Photomontage-3D-1038x576.png",
    "02_450maa_20200623-Photomontage-3D.png",
    "Capture-d'écran-2020-07-13-à-15.13.34-958x576.png",
    "Capture-d'écran-2020-07-13-à-15.13.34.png",
    "Capture-d'écran-2020-08-18-à-12.26.49-768x995.png",
    "Capture-d'écran-2020-08-18-à-12.26.49-790x1024.png",
    "Capture-d'écran-2020-08-18-à-12.26.49.png",
    "Capture-decran-2021-03-24-a-11.28.32-1024x681.png",
    "Capture-decran-2021-03-24-a-11.28.32-1038x576.png",
    "Capture-decran-2021-03-24-a-11.28.32-768x511.png",
    "Capture-decran-2021-03-24-a-11.28.32.png",
    "Natureenville-725x1024.png",
    "Natureenville-768x1085.png",
    "Natureenville-850x576.png",
    "Natureenville.png",
    "Plan-fiches-672x372.png",
    "Plan-fiches.png",
];

echo "=== Vérification des références PNG en BDD ===\n\n";

$updates = 0;

// 1. sill_medias — filepath
$stmt = $pdo->query("SELECT id, filepath FROM sill_medias WHERE filepath LIKE '%.png'");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "sill_medias avec .png : " . count($rows) . "\n";
foreach ($rows as $row) {
    foreach ($converted_pngs as $png) {
        if (strpos($row['filepath'], $png) !== false) {
            $new = str_replace('.png', '.jpg', $row['filepath']);
            $pdo->prepare("UPDATE sill_medias SET filepath = ? WHERE id = ?")->execute([$new, $row['id']]);
            echo "  FIX sill_medias #{$row['id']}: {$row['filepath']} -> $new\n";
            $updates++;
        }
    }
}

// 2. sill_pages — content (HTML)
$stmt = $pdo->query("SELECT id, slug, content FROM sill_pages WHERE content LIKE '%.png%'");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "\nsill_pages avec .png dans content : " . count($rows) . "\n";
foreach ($rows as $row) {
    $changed = false;
    $content = $row['content'];
    foreach ($converted_pngs as $png) {
        if (strpos($content, $png) !== false) {
            $jpg = str_replace('.png', '.jpg', $png);
            $content = str_replace($png, $jpg, $content);
            echo "  FIX sill_pages #{$row['id']} ({$row['slug']}): $png -> $jpg\n";
            $changed = true;
            $updates++;
        }
    }
    if ($changed) {
        $pdo->prepare("UPDATE sill_pages SET content = ? WHERE id = ?")->execute([$content, $row['id']]);
    }
}

// 3. sill_timeline — check image references
$stmt = $pdo->query("SELECT id, title, image_id FROM sill_timeline");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
// image_id is a FK to sill_medias, already handled above

// 4. sill_immeubles — check all text columns
$stmt = $pdo->query("SHOW COLUMNS FROM sill_immeubles");
$cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
foreach ($cols as $col) {
    $stmt = $pdo->query("SELECT id, nom, `$col` FROM sill_immeubles WHERE `$col` LIKE '%.png%'");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        foreach ($converted_pngs as $png) {
            if (strpos($row[$col] ?? '', $png) !== false) {
                echo "  FOUND sill_immeubles #{$row['id']} ({$row['nom']}) col $col: $png\n";
            }
        }
    }
}

// 5. sill_settings
$stmt = $pdo->query("SELECT setting_key, setting_value FROM sill_settings WHERE setting_value LIKE '%.png%'");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "\nsill_settings avec .png : " . count($rows) . "\n";
foreach ($rows as $row) {
    foreach ($converted_pngs as $png) {
        if (strpos($row['setting_value'], $png) !== false) {
            $new = str_replace($png, str_replace('.png', '.jpg', $png), $row['setting_value']);
            $pdo->prepare("UPDATE sill_settings SET setting_value = ? WHERE setting_key = ?")->execute([$new, $row['setting_key']]);
            echo "  FIX sill_settings {$row['setting_key']}: $png -> .jpg\n";
            $updates++;
        }
    }
}

echo "\n=== Terminé : $updates corrections appliquées ===\n";
echo "\n⚠️  SUPPRIMER CE FICHIER après exécution !\n";
