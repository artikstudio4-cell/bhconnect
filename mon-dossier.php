<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/AuthModel.php';
require_once __DIR__ . '/models/DossierModel.php';
require_once __DIR__ . '/models/DocumentModel.php';
require_once __DIR__ . '/models/ClientModel.php';

$auth = new AuthModel();
if (!$auth->isLoggedIn() || !$auth->isClient()) {
    header('Location: ' . url('login.php'));
    exit;
}

$dossierModel = new DossierModel();
$documentModel = new DocumentModel();
$clientModel = new ClientModel();

// Récupérer le client, créer automatiquement le profil s'il n'existe pas
$client = $clientModel->getClientByUserId($auth->getUserId(), true);

// Vérifier si le client existe
if (!$client || !is_array($client) || !isset($client['id'])) {
    $_SESSION['error'] = 'Erreur : profil client introuvable.';
    header('Location: ' . url('logout.php'));
    exit;
}

$dossiers = $dossierModel->getDossiers($client['id']);

include __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row mb-5">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 fw-bold mb-2"><i class="bi bi-folder-fill me-2 text-primary"></i> Mon dossier</h1>
                    <p class="text-muted mb-0">Consultation et suivi de vos dossiers en cours</p>
                </div>
            </div>
        </div>
    </div>

<?php if (empty($dossiers)): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> Vous n'avez aucun dossier enregistré pour le moment.
    </div>
<?php else: ?>
    <?php foreach ($dossiers as $dossier): ?>
        <?php 
        $historique = $dossierModel->getHistorique($dossier['id']);
        $documents = $documentModel->getDocumentsByDossier($dossier['id']);
        ?>
        <div class="card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-header bg-white py-3 px-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 fw-bold">
                            <i class="bi bi-folder-fill me-2 text-primary"></i> <?php echo htmlspecialchars($dossier['numero_dossier']); ?>
                        </h5>
                        <small class="text-muted"><?php echo htmlspecialchars($dossier['type_dossier']); ?></small>
                    </div>
                    <span class="badge rounded-pill px-3 py-2 bg-<?php echo Constants::getDossierStatusColor($dossier['statut']); ?>">
                        <?php echo htmlspecialchars($dossier['statut']); ?>
                    </span>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="row g-4">
                    <div class="col-md-6">
                        <!-- Timeline -->
                        <h6 class="mb-3 fw-bold text-gray-800"><i class="bi bi-clock-history me-2"></i> Historique du dossier</h6>
                        <div class="timeline position-relative ps-3 border-start">
                            <?php if (empty($historique)): ?>
                                <p class="text-muted">Aucun historique disponible</p>
                            <?php else: ?>
                                <?php 
                                $statutsMapping = Constants::getDossierStatusesMapping();
                                $statutsOrdre = array_keys($statutsMapping);
                                $statutActuel = $dossier['statut'];
                                $indexActuel = array_search($statutActuel, $statutsOrdre);
                                
                                foreach ($historique as $hist): 
                                    $statutIndex = array_search($hist['statut_nouveau'], $statutsOrdre);
                                    $isActive = ($statutIndex === $indexActuel);
                                    $isCompleted = ($statutIndex < $indexActuel);
                                ?>
                                    <div class="timeline-item position-relative mb-4 ps-4">
                                        <div class="position-absolute top-0 start-0 translate-middle rounded-circle border border-white 
                                            <?php echo $isActive || $isCompleted ? 'bg-primary' : 'bg-secondary'; ?>"
                                            style="width: 12px; height: 12px; margin-left: -1px;"></div>
                                        
                                        <div class="card border-0 shadow-sm bg-light">
                                            <div class="card-body p-3">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <h6 class="mb-0 fw-bold">
                                                        <?php echo htmlspecialchars($statutsMapping[$hist['statut_nouveau']] ?? $hist['statut_nouveau']); ?>
                                                    </h6>
                                                    <small class="text-muted" style="font-size: 0.75rem;">
                                                        <?php echo date('d/m/Y H:i', strtotime($hist['date_modification'])); ?>
                                                    </small>
                                                </div>
                                                <?php if ($hist['commentaire']): ?>
                                                    <p class="mb-0 small text-muted"><?php echo nl2br(htmlspecialchars($hist['commentaire'])); ?></p>
                                                <?php endif; ?>
                                                <small class="text-muted d-block mt-1" style="font-size: 0.7rem;">
                                                    <i class="bi bi-person me-1"></i> Cabinet
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <!-- Documents -->
                        <h6 class="mb-3 fw-bold text-gray-800"><i class="bi bi-file-earmark me-2"></i> Documents</h6>
                        <?php if (empty($documents)): ?>
                            <div class="text-center py-4 bg-light rounded-3">
                                <i class="bi bi-file-earmark-x text-muted mb-2" style="font-size: 1.5rem;"></i>
                                <p class="text-muted mb-0 small">Aucun document uploadé</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush rounded-3 overflow-hidden border">
                                <?php foreach ($documents as $doc): ?>
                                    <div class="list-group-item px-3 py-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center">
                                                <div class="me-3 text-primary">
                                                    <i class="bi bi-file-earmark-text fs-4"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0 small fw-bold"><?php echo htmlspecialchars($doc['nom_fichier']); ?></h6>
                                                    <small class="text-muted" style="font-size: 0.75rem;">
                                                        <?php echo htmlspecialchars($doc['type_document'] ?? 'Document'); ?>
                                                        • <?php echo number_format($doc['taille_fichier'] / 1024, 2); ?> Ko
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="d-flex flex-column align-items-end gap-2">
                                                <span class="badge rounded-pill bg-<?php 
                                                    $docStatutColor = 'warning';
                                                    if ($doc['statut'] === 'valide') {
                                                        $docStatutColor = 'success';
                                                    } elseif ($doc['statut'] === 'rejete') {
                                                        $docStatutColor = 'danger';
                                                    }
                                                    echo $docStatutColor;
                                                ?>" style="font-size: 0.65rem;">
                                                    <?php echo ucfirst($doc['statut']); ?>
                                                </span>
                                                <?php if ($doc['statut'] === 'valide'): ?>
                                                    <a href="<?php echo url('uploads/' . basename($doc['chemin_fichier'])); ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill py-0 px-2" style="font-size: 0.7rem;">
                                                        <i class="bi bi-eye"></i> Voir
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-3">
                            <a href="<?php echo url('documents.php?dossier_id=' . $dossier['id']); ?>" class="btn btn-primary w-100 rounded-pill">
                                <i class="bi bi-upload me-2"></i> Uploader un document
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

