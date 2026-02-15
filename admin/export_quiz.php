<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/AuthModel.php';
require_once __DIR__ . '/../models/QuizModel.php';

$auth = new AuthModel();
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    exit('Accès refusé');
}

$quizModel = new QuizModel();
$participations = $quizModel->getAllParticipations();

// Headers pour le téléchargement
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=quiz_resultats_' . date('Y-m-d') . '.csv');

// Création du flux de sortie
$output = fopen('php://output', 'w');

// BOM pour Excel (UTF-8)
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// En-têtes des colonnes
fputcsv($output, ['Date', 'Nom', 'Prenom', 'Email', 'Telephone', 'Score', 'Pourcentage', 'Statut']);

foreach ($participations as $p) {
    $percent = ($p['score'] / 60) * 100;
    $statut = ($percent >= 50) ? 'Admis' : 'Insuffisant';
    
    fputcsv($output, [
        $p['date_fin'],
        $p['nom'],
        $p['prenom'],
        $p['email'],
        $p['telephone'],
        $p['score'],
        number_format($percent, 2) . '%',
        $statut
    ]);
}

fclose($output);
exit;
?>
