<?php
$baseDir = isset($_GET['baseDir']) ? $_GET['baseDir'] : 'foto';

// 🛡 zabezpieczenie przed nieautoryzowanymi ścieżkami
$baseDir = rtrim($baseDir, '/');
if (!is_dir($baseDir)) {
    http_response_code(400);
    echo json_encode(['error' => 'Nieprawidłowy katalog']);
    exit;
}

$folders = [];

foreach (glob($baseDir . '/*', GLOB_ONLYDIR) as $dir) {
    $folderName = basename($dir);
    $images = array_merge(
        glob($dir . '/*.webp'),
        glob($dir . '/*.jpg'),
        glob($dir . '/*.jpeg'),
        glob($dir . '/*.png')
    );
    $folders[] = [
        'name' => $folderName,
        'images' => array_map('basename', $images)
    ];
}

header('Content-Type: application/json');
echo json_encode($folders);
