#!/usr/bin/env php
<?php
/**
 * Script pour créer un fichier notification.mp3 simple
 * Crée un son avec deux notes en utilisant une libraire PHP audio
 * 
 * Usage: php create_default_sound.php
 */

$outputFile = __DIR__ . '/notification.mp3';

// Vérifier si le fichier existe déjà
if (file_exists($outputFile)) {
    echo "❌ Le fichier notification.mp3 existe déjà.\n";
    exit(1);
}

// Essayer de créer un son simple avec FFmpeg
$ffmpegCommand = 'ffmpeg -f lavfi -i "sine=frequency=262:duration=0.2,sine=frequency=330:duration=0.2" -q:a 9 -y ' . escapeshellarg($outputFile) . ' 2>&1';

echo "🎵 Tentative de génération avec FFmpeg...\n";
$output = shell_exec($ffmpegCommand);

if (file_exists($outputFile) && filesize($outputFile) > 100) {
    echo "✅ Fichier créé avec FFmpeg: " . round(filesize($outputFile) / 1024, 2) . " KB\n";
    exit(0);
}

echo "⚠️  FFmpeg n'a pas pu générer le fichier.\n";
echo "💡 Options:\n";
echo "   1. Installer FFmpeg et réexécuter ce script\n";
echo "   2. Utiliser la page web setup.html pour télécharger ou uploader un son\n";
echo "   3. Placer manuellement un fichier MP3 nommé 'notification.mp3' dans ce dossier\n";

exit(1);
?>
