<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/AuthModel.php';

require_once __DIR__ . '/models/DocumentModel.php';
require_once __DIR__ . '/models/DossierModel.php';
require_once __DIR__ . '/models/NotificationModel.php';

$auth = new AuthModel();
if (!$auth->isLoggedIn()) {
    header('Location: ' . url('login.php'));
    exit;
}

$documentModel = new DocumentModel();
$dossierModel = new DossierModel();
$notificationModel = new NotificationModel();
$message = '';
$error = '';

$dossierId = $_GET['dossier_id'] ?? null;
if (!$dossierId) {
    // Rediriger selon le rôle
    if ($auth->isAdmin()) {
        header('Location: ' . url('dashboard.php'));
    } else {
        header('Location: ' . url('mon-dossier.php'));
    }
    exit;
}

// Vérifier les permissions
$dossier = $dossierModel->getDossierById($dossierId);
if (!$dossier) {
    // Rediriger selon le rôle
    if ($auth->isAdmin()) {
        header('Location: ' . url('dashboard.php'));
    } else {
        header('Location: ' . url('mon-dossier.php'));
    }
    exit;
}

// Si client, vérifier que c'est son dossier
if ($auth->isClient() && $dossier['client_id'] != $auth->getClientId()) {
    header('Location: ' . url('mon-dossier.php'));
    exit;
}

// Créer le dossier uploads s'il n'existe pas
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// Traitement de l'upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document'])) {
    // Vérifier le token CSRF
    if (!CSRFToken::verify($_POST['csrf_token'] ?? '')) {
        $error = 'Jeton de sécurité invalide';
    } else {
        $file = $_FILES['document'];
        
        // Valider le fichier avec la classe FileValidator
        $validation = FileValidator::validate($file, MAX_FILE_SIZE);
        
        if (!$validation['valid']) {
            $error = implode(' | ', $validation['errors']);
        } else {
            // Générer un nom de fichier sécurisé
            $nomFichier = FileValidator::sanitizeFileName($file['name']);
            $cheminFichier = UPLOAD_DIR . $nomFichier;
            
            if (move_uploaded_file($file['tmp_name'], $cheminFichier)) {
                if ($documentModel->addDocument(
                    $dossierId,
                    $file['name'],
                    $cheminFichier,
                    $_POST['type_document'] ?? '',
                    $file['size']
                )) {
                    $message = 'Document uploadé avec succès. En attente de validation par l\'administrateur.';
                    // Notifier l'administrateur
                    $adminId = 1; // À adapter si l'ID admin est différent
                    $notificationModel->create(
                        $adminId,
                        'document',
                        'Nouveau document à valider',
                        'Un nouveau document a été uploadé par l\'utilisateur ID ' . $auth->getUserId() . ' pour le dossier #' . $dossierId,
                        url('documents.php?dossier_id=' . $dossierId),
                        false // true pour envoyer aussi un email
                    );
                    // Notifier l'agent assigné au dossier
                    if (isset($dossier['agent_id']) && $dossier['agent_id']) {
                        $notificationModel->create(
                            $dossier['agent_id'],
                            'document',
                            'Nouveau document ajouté à un dossier',
                            'Un nouveau document a été uploadé pour le dossier #' . $dossierId . ' dont vous êtes responsable.',
                            url('documents.php?dossier_id=' . $dossierId),
                            false
                        );
                    }
                } else {
                    unlink($cheminFichier);
                    $error = 'Erreur lors de l\'enregistrement du document';
                }
            } else {
                $error = 'Erreur lors de l\'upload du fichier';
            }
        }
    }
}

// Traitement de la validation/rejet (admin seulement)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'validate' && $auth->isAdmin()) {
    $docId = $_POST['document_id'];
    $statut = $_POST['statut'];
    
    if ($documentModel->updateStatutDocument($docId, $statut, $auth->getUserId())) {
        $message = 'Statut du document mis à jour';
    } else {
        $error = 'Erreur lors de la mise à jour';
    }
}

$documents = $documentModel->getDocumentsByDossier($dossierId);

include __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-file-earmark-text text-primary me-2"></i>Documents du dossier #<?php echo htmlspecialchars($dossier['numero_dossier']); ?></h1>
            <p class="text-muted mb-0">Client: <?php echo htmlspecialchars($dossier['client_nom'] . ' ' . $dossier['client_prenom']); ?></p>
        </div>
        <a href="<?php echo $auth->isAdmin() ? url('dossiers.php?action=view&id=' . $dossierId) : url('mon-dossier.php'); ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Retour au dossier
        </a>
    </div>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-gray-800"><i class="bi bi-cloud-upload me-2"></i>Uploader un document</h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <?php echo CSRFToken::field(); ?>
                        
                        <div class="mb-3">
                            <label for="document" class="form-label">Fichier *</label>
                            <input type="file" class="form-control" id="document" name="document" 
                                   accept=".pdf,.jpg,.jpeg,.png" required>
                            <small class="text-muted">Formats acceptés: PDF, JPG, PNG (max <?php echo MAX_FILE_SIZE / 1024 / 1024; ?> Mo)</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="type_document" class="form-label">Type de document</label>
                            <input type="text" class="form-control" id="type_document" name="type_document" 
                                   placeholder="Ex: Passeport, Diplôme, etc.">
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-cloud-upload me-2"></i>Uploader
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-8 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-gray-800"><i class="bi bi-files me-2"></i>Documents existants</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($documents)): ?>
                        <div class="p-4 text-center text-muted">Aucun document uploadé</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Nom</th>
                                        <th>Type</th>
                                        <th>Taille</th>
                                        <th>Statut</th>
                                        <th>Date upload</th>
                                        <th class="text-end pe-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($documents as $doc): ?>
                                        <tr>
                                            <td class="ps-4 fw-medium"><?php echo htmlspecialchars($doc['nom_fichier']); ?></td>
                                            <td><?php echo htmlspecialchars($doc['type_document'] ?? '-'); ?></td>
                                            <td><?php echo number_format($doc['taille_fichier'] / 1024, 2); ?> Ko</td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    $docStatutColor = 'warning';
                                                    if ($doc['statut'] === 'valide') {
                                                        $docStatutColor = 'success';
                                                    } elseif ($doc['statut'] === 'rejete') {
                                                        $docStatutColor = 'danger';
                                                    }
                                                    echo $docStatutColor;
                                                ?>">
                                                    <?php echo ucfirst($doc['statut']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($doc['date_upload'])); ?></td>
                                            <td class="text-end pe-4">
                                                <div class="btn-group btn-group-sm">
                                                    <a href="<?php echo url('uploads/' . basename($doc['chemin_fichier'])); ?>" target="_blank" class="btn btn-outline-primary" title="Voir">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <?php if ($auth->isAdmin() && $doc['statut'] === 'en_attente'): ?>
                                                        <form method="POST" style="display: inline;">
                                                            <?php echo CSRFToken::field(); ?>
                                                            <input type="hidden" name="action" value="validate">
                                                            <input type="hidden" name="document_id" value="<?php echo $doc['id']; ?>">
                                                            <input type="hidden" name="statut" value="valide">
                                                            <button type="submit" class="btn btn-outline-success" title="Valider">
                                                                <i class="bi bi-check-lg"></i>
                                                            </button>
                                                        </form>
                                                        <form method="POST" style="display: inline;">
                                                            <?php echo CSRFToken::field(); ?>
                                                            <input type="hidden" name="action" value="validate">
                                                            <input type="hidden" name="document_id" value="<?php echo $doc['id']; ?>">
                                                            <input type="hidden" name="statut" value="rejete">
                                                            <button type="submit" class="btn btn-outline-danger" title="Rejeter">
                                                                <i class="bi bi-x-lg"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

