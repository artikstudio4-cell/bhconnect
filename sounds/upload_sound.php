<?php
/**
 * Uploader un fichier audio personnalisé
 */
header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!isset($_FILES['audio'])) {
    $response['message'] = 'Aucun fichier fourni';
    echo json_encode($response);
    exit;
}

$file = $_FILES['audio'];
$outputFile = __DIR__ . '/notification.mp3';

// Vérifier le type MIME
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$allowedMimes = ['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/ogg', 'audio/webm'];

if (!in_array($mimeType, $allowedMimes)) {
    $response['message'] = "Format non autorisé. Utilisez MP3, WAV, OGG ou WebM. (Type détecté: $mimeType)";
    echo json_encode($response);
    exit;
}

// Vérifier la taille (max 5 MB)
$maxSize = 5 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    $response['message'] = 'Fichier trop volumineux (max 5 MB)';
    echo json_encode($response);
    exit;
}

// Si c'est un MP3, copier directement
if ($mimeType === 'audio/mpeg' || $mimeType === 'audio/mp3') {
    if (move_uploaded_file($file['tmp_name'], $outputFile)) {
        $response['success'] = true;
        $size = round(filesize($outputFile) / 1024, 2);
        $response['message'] = "✅ Fichier uploadé avec succès ($size KB)";
    } else {
        $response['message'] = 'Erreur lors du déplacement du fichier';
    }
} else {
    // Pour autres formats, copier temporairement et indiquer que la conversion est nécessaire
    if (copy($file['tmp_name'], $outputFile)) {
        $response['success'] = true;
        $size = round(filesize($outputFile) / 1024, 2);
        $response['message'] = "✅ Fichier uploadé. Note: Format détecté $mimeType (sera joué en HTML5)";
    } else {
        $response['message'] = 'Erreur lors de la copie du fichier';
    }
}

echo json_encode($response);
?>
