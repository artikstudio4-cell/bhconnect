#!/usr/bin/env php
<?php
/**
 * Script pour générer le fichier audio notification.mp3
 * Utilise FFmpeg pour créer un son simple
 */

$outputFile = __DIR__ . '/notification.mp3';

// Vérifier si FFmpeg est installé
$ffmpegPath = shell_exec('which ffmpeg 2>/dev/null') ?: shell_exec('where ffmpeg 2>nul');

if (!$ffmpegPath) {
    echo "❌ FFmpeg n'est pas installé. Impossible de générer le fichier audio.\n";
    echo "📥 Installation:\n";
    echo "   Windows (via Chocolatey): choco install ffmpeg\n";
    echo "   macOS (via Homebrew): brew install ffmpeg\n";
    echo "   Linux: apt-get install ffmpeg (Debian/Ubuntu) ou yum install ffmpeg (CentOS)\n";
    echo "\n";
    echo "💡 Alternative: Téléchargez un fichier notification.mp3 depuis une source gratuite\n";
    echo "   et placez-le dans ce dossier (sounds/)\n";
    exit(1);
}

echo "🎵 Génération du fichier audio notification.mp3...\n";

// Utiliser FFmpeg pour générer un son simple (2 notes: Do, Mi)
// La commande génère une séquence de tons
$command = sprintf(
    'ffmpeg -f lavfi -i "sine=frequency=262:duration=0.2,sine=frequency=330:duration=0.2" -q:a 9 -y "%s" 2>&1',
    escapeshellarg($outputFile)
);

$output = shell_exec($command);

if (file_exists($outputFile) && filesize($outputFile) > 0) {
    echo "✅ Fichier créé avec succès: notification.mp3\n";
    echo "📊 Taille: " . round(filesize($outputFile) / 1024, 2) . " KB\n";
    echo "📍 Chemin: " . $outputFile . "\n";
} else {
    echo "❌ Erreur lors de la génération du fichier audio\n";
    echo "Sortie FFmpeg:\n";
    echo $output . "\n";
    exit(1);
}

echo "\n✅ Le fichier audio est maintenant prêt pour les notifications!\n";
?>
