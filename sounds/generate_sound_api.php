<?php
/**
 * API pour générer le fichier audio avec FFmpeg
 */
header('Content-Type: application/json');

$outputFile = __DIR__ . '/notification.mp3';
$response = ['success' => false, 'message' => ''];

// Vérifier si FFmpeg est disponible
$ffmpegPath = shell_exec('which ffmpeg 2>/dev/null') ?: shell_exec('where ffmpeg 2>nul');

if (!$ffmpegPath) {
    $response['message'] = 'FFmpeg n\'est pas installé sur le serveur. Utilisez l\'option "Télécharger un son" ou "Uploader un fichier".';
    echo json_encode($response);
    exit;
}

// Générer le son avec FFmpeg
// La commande crée une séquence de deux tons (Do, Mi) de 0.2 secondes chacun
$command = sprintf(
    'ffmpeg -f lavfi -i "sine=frequency=262:duration=0.2,sine=frequency=330:duration=0.2" -q:a 9 -y %s 2>&1',
    escapeshellarg($outputFile)
);

$output = shell_exec($command);

if (file_exists($outputFile) && filesize($outputFile) > 100) {
    $response['success'] = true;
    $size = round(filesize($outputFile) / 1024, 2);
    $response['message'] = "✅ Fichier généré avec succès ($size KB)";
} else {
    $response['message'] = 'Erreur lors de la génération du fichier audio.';
}

echo json_encode($response);
?>
