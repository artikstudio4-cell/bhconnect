<?php
/**
 * Vérifier l'existence du fichier audio
 */
header('Content-Type: application/json');

$soundFile = __DIR__ . '/notification.mp3';
$response = [
    'exists' => false,
    'filename' => 'notification.mp3',
    'size' => ''
];

if (file_exists($soundFile)) {
    $response['exists'] = true;
    $size = filesize($soundFile);
    if ($size > 1024 * 1024) {
        $response['size'] = round($size / (1024 * 1024), 2) . ' MB';
    } else {
        $response['size'] = round($size / 1024, 2) . ' KB';
    }
}

echo json_encode($response);
?>
