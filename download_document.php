<?php
/**
 * Endpoint sécurisé pour télécharger les documents
 * Vérifie les permissions avant de servir le fichier
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/AuthModel.php';
require_once __DIR__ . '/models/DocumentModel.php';
require_once __DIR__ . '/models/DossierModel.php';

$auth = new AuthModel();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    die('Non authentifié');
}

$documentId = $_GET['id'] ?? null;

if (!$documentId) {
    http_response_code(400);
    die('ID document requis');
}

$documentModel = new DocumentModel();
$document = $documentModel->getDocumentById($documentId);

if (!$document) {
    http_response_code(404);
    die('Document introuvable');
}

// Vérifier les permissions
$dossierModel = new DossierModel();
$dossier = $dossierModel->getDossierById($document['dossier_id'] ?? $document['dossier_id']);

if (!$dossier) {
    http_response_code(404);
    die('Dossier introuvable');
}

// Vérifier que l'utilisateur a accès à ce document
$hasAccess = false;

if ($auth->isAdmin()) {
    $hasAccess = true;
} elseif ($auth->isAgent()) {
    // Agent peut accéder aux documents de ses dossiers assignés
    // À implémenter selon votre logique métier
    $hasAccess = true; // À affiner
} elseif ($auth->isClient()) {
    // Client peut accéder aux documents de ses propres dossiers
    if ($dossier['client_id'] == $auth->getClientId()) {
        $hasAccess = true;
    }
}

if (!$hasAccess) {
    http_response_code(403);
    die('Accès refusé');
}

// Vérifier que le fichier existe et est valide
$filePath = $document['chemin_fichier'];

if (!file_exists($filePath)) {
    http_response_code(404);
    die('Fichier introuvable');
}

// Vérifier que le chemin du fichier reste dans le répertoire uploads
$realpath = realpath($filePath);
$uploadDirReal = realpath(UPLOAD_DIR);

if ($uploadDirReal === false || $realpath === false || strpos($realpath, $uploadDirReal) !== 0) {
    http_response_code(403);
    die('Accès refusé - Chemin invalide');
}

// Enregistrer dans l'audit
require_once __DIR__ . '/models/AuditModel.php';
$auditModel = new AuditModel();
$auditModel->log($auth->getUserId(), 'download_document', "Téléchargement du document #" . $documentId, 'success');

// Servir le fichier
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($document['nom_fichier']) . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

readfile($filePath);
exit;
