<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/AuthModel.php';
require_once __DIR__ . '/models/DossierModel.php';
require_once __DIR__ . '/models/RendezVousModel.php';
require_once __DIR__ . '/models/ClientModel.php';
require_once __DIR__ . '/models/DocumentModel.php';

$auth = new AuthModel();
if (!$auth->isLoggedIn() || !$auth->isClient()) {
    header('Location: ' . url('login.php'));
    exit;
}

$dossierModel = new DossierModel();
$rdvModel = new RendezVousModel();
$clientModel = new ClientModel();
$documentModel = new DocumentModel();

// Récupérer le client, créer automatiquement le profil s'il n'existe pas
$client = $clientModel->getClientByUserId($auth->getUserId(), true);

// Vérifier si le client existe
if (!$client || !is_array($client)) {
    // Si le profil client n'a pas pu être créé
    $_SESSION['error'] = 'Impossible de créer votre profil client. Veuillez contacter l\'administrateur.';
    header('Location: ' . url('logout.php'));
    exit;
}

// Vérifier que l'ID client existe
if (!isset($client['id'])) {
    $_SESSION['error'] = 'Erreur : ID client manquant. Veuillez contacter l\'administrateur.';
    header('Location: ' . url('logout.php'));
    exit;
}

$dossiers = $dossierModel->getDossiers($client['id']);
$rdvAVenir = $rdvModel->getRendezVousAVenir($client['id'], 5);
$allRdvs = $rdvModel->getRendezVous($client['id']);

// S'assurer que $dossiers, $rdvAVenir et $allRdvs sont des tableaux
if (!is_array($dossiers)) {
    $dossiers = [];
}
if (!is_array($rdvAVenir)) {
    $rdvAVenir = [];
}
if (!is_array($allRdvs)) {
    $allRdvs = [];
}

// Calculer les statistiques pour le client
$totalDossiers = count($dossiers);
$totalRdvs = count($allRdvs);
$dossiersEnCours = 0;
$dossiersFinalises = 0;
$documentsEnAttente = 0;
$documentsValides = 0;

foreach ($dossiers as $dossier) {
    if (in_array($dossier['statut'], [
        Constants::DOSSIER_NOUVEAU, 
        Constants::DOSSIER_ANALYSE_PRELIMINAIRE, 
        Constants::DOSSIER_CONSTITUTION, 
        Constants::DOSSIER_ATTENTE_RDV, 
        Constants::DOSSIER_DEPOT_EFFECTUE, 
        Constants::DOSSIER_TRAITEMENT
    ])) {
        $dossiersEnCours++;
    } elseif (in_array($dossier['statut'], [
        Constants::DOSSIER_VISA_ACCORDE, 
        Constants::DOSSIER_VISA_REFUSE, 
        Constants::DOSSIER_CLOTURE
    ])) {
        $dossiersFinalises++;
    }
    
    $documents = $documentModel->getDocumentsByDossier($dossier['id']);
    // S'assurer que $documents est un tableau
    if (is_array($documents)) {
        foreach ($documents as $doc) {
            if (isset($doc['statut'])) {
                if ($doc['statut'] === 'en_attente') {
                    $documentsEnAttente++;
                } elseif ($doc['statut'] === 'valide') {
                    $documentsValides++;
                }
            }
        }
    }
}

// Récupérer les notifications
require_once __DIR__ . '/models/NotificationModel.php';
$notificationModel = new NotificationModel();
$notificationsNonLues = $notificationModel->getUnreadCount($auth->getUserId());

// Récupérer l'historique et les documents pour chaque dossier
$dossiersAvecDetails = [];
foreach ($dossiers as $dossier) {
    $documents = $documentModel->getDocumentsByDossier($dossier['id']);
    $historique = $dossierModel->getHistorique($dossier['id']);
    
    // S'assurer que documents et historique sont des tableaux
    if (!is_array($documents)) {
        $documents = [];
    }
    if (!is_array($historique)) {
        $historique = [];
    }
    
    $dossiersAvecDetails[] = [
        'dossier' => $dossier,
        'documents' => $documents,
        'historique' => $historique
    ];
}

// Définir l'ordre du workflow
$workflowSteps = [
    'soumis' => ['label' => 'Soumis', 'icon' => 'bi-send', 'color' => 'primary'],
    'en_revue' => ['label' => 'En revue', 'icon' => 'bi-eye', 'color' => 'info'],
    'documents_requis' => ['label' => 'Documents requis', 'icon' => 'bi-file-earmark-text', 'color' => 'warning'],
    'documents_en_verification' => ['label' => 'Vérification', 'icon' => 'bi-search', 'color' => 'warning'],
    'documents_valides' => ['label' => 'Documents validés', 'icon' => 'bi-check-circle', 'color' => 'success'],
    'en_traitement' => ['label' => 'En traitement', 'icon' => 'bi-gear', 'color' => 'info'],
    'en_attente_reponse' => ['label' => 'En attente', 'icon' => 'bi-clock', 'color' => 'warning'],
    'complement_demande' => ['label' => 'Complément demandé', 'icon' => 'bi-exclamation-triangle', 'color' => 'warning'],
    'valide' => ['label' => 'Validé', 'icon' => 'bi-check2-all', 'color' => 'success'],
    'finalise' => ['label' => 'Finalisé', 'icon' => 'bi-flag-fill', 'color' => 'success'],
    'archive' => ['label' => 'Archivé', 'icon' => 'bi-archive', 'color' => 'secondary']
];

include __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="mb-1"><i class="bi bi-speedometer2 text-primary"></i> Mon Tableau de Bord</h1>
                <p class="text-muted mb-0">Bienvenue, <?php echo htmlspecialchars($client['prenom'] . ' ' . $client['nom']); ?></p>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <?php if ($notificationsNonLues > 0): ?>
                    <a href="<?php echo url('notifications.php'); ?>" class="btn btn-warning position-relative">
                        <i class="bi bi-bell"></i> Notifications
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?php echo $notificationsNonLues; ?>
                        </span>
                    </a>
                <?php endif; ?>
                <a href="<?php echo url('messages.php'); ?>" class="btn btn-info position-relative">
                    <i class="bi bi-envelope"></i> Messages
                </a>
                <span class="badge bg-primary fs-6 px-3 py-2">
                    <i class="bi bi-person-circle"></i> Espace Client
                </span>
            </div>
        </div>
    </div>
</div>

<!-- SECTION QUIZ & DESTINATIONS -->
<?php 
require_once __DIR__ . '/models/QuizModel.php';
$quizModel = new QuizModel();
$participation = $quizModel->hasParticipated($auth->getUserId());
?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <?php if (!$participation || $participation['statut'] !== 'termine'): ?>
                            <h4 class="text-primary fw-bold mb-2">
                                <i class="bi bi-mortarboard-fill me-2"></i> Test d'aptitude - Études en Europe
                            </h4>
                            <p class="text-muted mb-3">
                                Pour valider votre éligibilité et maximiser vos chances d'admission, passez ce test de connaissances.
                                <br><small>Durée estimée : 10 minutes.</small>
                            </p>
                            <a href="<?php echo url('quiz/intro.php'); ?>" class="btn btn-primary px-4">
                                <i class="bi bi-play-fill"></i> Commencer le test maintenant
                            </a>
                        <?php else: ?>
                            <div class="alert alert-success border-0 bg-success bg-opacity-10 mb-0">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-check-circle-fill text-success fs-3 me-3"></i>
                                    <div>
                                        <h5 class="alert-heading fw-bold mb-1 text-success">Test validé !</h5>
                                        <p class="mb-0 text-muted">Bravo, vous avez complété votre test d'aptitude.</p>
                                    </div>
                                    <div class="ms-auto">
                                        <a href="<?php echo url('quiz/resultat.php'); ?>" class="btn btn-sm btn-outline-success fw-bold">
                                            Voir mon score
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4 text-center d-none d-md-block">
                         <i class="bi bi-award text-primary opacity-25" style="font-size: 6rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statistiques principales -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card stat-card text-white bg-primary shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-2 opacity-75">Mes Dossiers</h6>
                        <h2 class="card-title mb-0"><?php echo $totalDossiers; ?></h2>
                        <small class="opacity-75">Total</small>
                    </div>
                    <div class="stat-icon bg-white bg-opacity-25">
                        <i class="bi bi-folder-fill"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card stat-card text-white bg-info shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-2 opacity-75">En Cours</h6>
                        <h2 class="card-title mb-0"><?php echo $dossiersEnCours; ?></h2>
                        <small class="opacity-75">Dossiers actifs</small>
                    </div>
                    <div class="stat-icon bg-white bg-opacity-25">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card stat-card text-white bg-success shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-2 opacity-75">Finalisés</h6>
                        <h2 class="card-title mb-0"><?php echo $dossiersFinalises; ?></h2>
                        <small class="opacity-75">Dossiers terminés</small>
                    </div>
                    <div class="stat-icon bg-white bg-opacity-25">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card stat-card text-white bg-warning shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-2 opacity-75">Rendez-vous</h6>
                        <h2 class="card-title mb-0"><?php echo count($rdvAVenir); ?></h2>
                        <small class="opacity-75">À venir</small>
                    </div>
                    <div class="stat-icon bg-white bg-opacity-25">
                        <i class="bi bi-calendar-event-fill"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Documents et Actions rapides -->
<div class="row mb-4">
    <div class="col-md-6 mb-3">
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-file-earmark-text text-primary"></i> État des Documents</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <div class="p-3">
                            <div class="display-4 text-warning mb-2"><?php echo $documentsEnAttente; ?></div>
                            <p class="text-muted mb-0">En attente</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3">
                            <div class="display-4 text-success mb-2"><?php echo $documentsValides; ?></div>
                            <p class="text-muted mb-0">Validés</p>
                        </div>
                    </div>
                </div>
                <?php if ($documentsEnAttente > 0): ?>
                    <div class="alert alert-warning mb-0 mt-3">
                        <i class="bi bi-exclamation-triangle"></i> 
                        Vous avez <?php echo $documentsEnAttente; ?> document(s) en attente de validation.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-3">
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-lightning-charge text-warning"></i> Actions Rapides</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="<?php echo url('mon-dossier.php'); ?>" class="btn btn-outline-primary btn-lg quick-action-card">
                        <i class="bi bi-folder"></i> Consulter mes dossiers
                    </a>
                    <a href="<?php echo url('creneaux.php'); ?>" class="btn btn-outline-warning btn-lg quick-action-card">
                        <i class="bi bi-calendar-plus"></i> Réserver un rendez-vous
                    </a>
                    <a href="<?php echo url('mes-rendez-vous.php'); ?>" class="btn btn-outline-info btn-lg quick-action-card">
                        <i class="bi bi-calendar-event"></i> Voir mes rendez-vous
                    </a>
                    <?php if (!empty($dossiers)): ?>
                        <a href="<?php echo url('documents.php?dossier_id=' . $dossiers[0]['id']); ?>" class="btn btn-outline-success btn-lg quick-action-card">
                            <i class="bi bi-upload"></i> Uploader un document
                        </a>
                    <?php endif; ?>
                    <a href="<?php echo url('messages.php'); ?>" class="btn btn-outline-secondary btn-lg quick-action-card">
                        <i class="bi bi-envelope"></i> Messagerie
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Rendez-vous à venir -->
<?php if (!empty($rdvAVenir)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-calendar-check"></i> Prochains Rendez-vous</h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <?php foreach ($rdvAVenir as $rdv): ?>
                        <div class="list-group-item px-0 border-0 border-bottom">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="bi bi-calendar3 text-primary me-2 fs-5"></i>
                                        <h6 class="mb-0">
                                            <?php echo date('d/m/Y', strtotime($rdv['date_heure'])); ?>
                                            <span class="text-muted">à <?php echo date('H:i', strtotime($rdv['date_heure'])); ?></span>
                                        </h6>
                                    </div>
                                    <p class="mb-1 fw-bold"><?php echo htmlspecialchars($rdv['type_rendez_vous']); ?></p>
                                    <?php if ($rdv['notes']): ?>
                                        <p class="mb-1 text-muted small"><?php echo nl2br(htmlspecialchars($rdv['notes'])); ?></p>
                                    <?php endif; ?>
                                    <?php if ($rdv['numero_dossier']): ?>
                                        <small class="text-muted">
                                            <i class="bi bi-folder"></i> Dossier: <?php echo htmlspecialchars($rdv['numero_dossier']); ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                                <span class="badge bg-<?php echo $rdv['statut'] === 'confirme' ? 'success' : 'warning'; ?> fs-6 px-3 py-2">
                                    <?php echo ucfirst($rdv['statut']); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-3">
                    <a href="<?php echo url('mes-rendez-vous.php'); ?>" class="btn btn-sm btn-outline-primary">
                        Voir tous mes rendez-vous
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Dossiers avec workflow visuel -->
<?php if (!empty($dossiersAvecDetails)): ?>
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-folder2-open text-primary"></i> Mes Dossiers</h5>
            </div>
            <div class="card-body">
                <?php foreach (array_slice($dossiersAvecDetails, 0, 3) as $item): 
                    $dossier = $item['dossier'];
                    $documents = $item['documents'];
                    $historique = $item['historique'];
                    
                    // Déterminer l'étape actuelle dans le workflow
                    $statutActuel = $dossier['statut'];
                    $stepInfo = $workflowSteps[$statutActuel] ?? ['label' => $statutActuel, 'icon' => 'bi-circle', 'color' => 'secondary'];
                    
                    // Compter les documents par statut
                    $docsEnAttente = 0;
                    $docsValides = 0;
                    $docsRejetes = 0;
                    foreach ($documents as $doc) {
                        if ($doc['statut'] === 'en_attente') $docsEnAttente++;
                        elseif ($doc['statut'] === 'valide') $docsValides++;
                        elseif ($doc['statut'] === 'rejete') $docsRejetes++;
                    }
                ?>
                    <div class="card mb-4 border">
                        <div class="card-header bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">
                                        <i class="bi bi-folder-fill text-primary"></i>
                                        <strong><?php echo htmlspecialchars($dossier['numero_dossier']); ?></strong>
                                    </h6>
                                    <small class="text-muted"><?php echo htmlspecialchars($dossier['type_dossier']); ?></small>
                                </div>
                                <span class="badge bg-<?php echo $stepInfo['color']; ?> fs-6 px-3 py-2">
                                    <i class="bi <?php echo $stepInfo['icon']; ?>"></i>
                                    <?php echo htmlspecialchars($stepInfo['label']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Workflow visuel -->
                            <div class="mb-4">
                                <h6 class="mb-3"><i class="bi bi-diagram-3"></i> État du dossier</h6>
                                <div class="workflow-container">
                                    <?php
                                    // Étapes principales du workflow
                                    $etapesPrincipales = [
                                        'soumis', 'en_revue', 'documents_valides', 'en_traitement', 'valide', 'finalise'
                                    ];
                                    
                                    // Trouver l'index de l'étape actuelle
                                    $indexActuel = -1;
                                    foreach ($etapesPrincipales as $idx => $etape) {
                                        if ($etape === $statutActuel || 
                                            (in_array($statutActuel, ['documents_requis', 'documents_en_verification']) && $etape === 'documents_valides') ||
                                            (in_array($statutActuel, ['en_attente_reponse', 'complement_demande']) && $etape === 'en_traitement')) {
                                            $indexActuel = $idx;
                                            break;
                                        }
                                    }
                                    
                                    // Si le statut est dans les étapes principales, utiliser son index
                                    if ($indexActuel === -1) {
                                        $indexActuel = array_search($statutActuel, $etapesPrincipales);
                                    }
                                    
                                    foreach ($etapesPrincipales as $idx => $etape):
                                        $step = $workflowSteps[$etape] ?? ['label' => $etape, 'icon' => 'bi-circle', 'color' => 'secondary'];
                                        $isActive = ($idx === $indexActuel);
                                        $isCompleted = ($indexActuel !== false && $idx < $indexActuel);
                                        $isPending = ($indexActuel !== false && $idx > $indexActuel);
                                    ?>
                                        <div class="workflow-step <?php echo $isActive ? 'active' : ($isCompleted ? 'completed' : 'pending'); ?>">
                                            <div class="workflow-step-icon">
                                                <i class="bi <?php echo $step['icon']; ?>"></i>
                                            </div>
                                            <div class="workflow-step-label">
                                                <?php echo htmlspecialchars($step['label']); ?>
                                            </div>
                                            <?php if ($idx < count($etapesPrincipales) - 1): ?>
                                                <div class="workflow-step-connector"></div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Documents -->
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="mb-3"><i class="bi bi-file-earmark-text"></i> Documents</h6>
                                    <?php if (empty($documents)): ?>
                                        <p class="text-muted small">Aucun document uploadé</p>
                                    <?php else: ?>
                                        <div class="document-stats mb-3">
                                            <div class="d-flex gap-3">
                                                <div class="text-center">
                                                    <div class="display-6 text-warning"><?php echo $docsEnAttente; ?></div>
                                                    <small class="text-muted">En attente</small>
                                                </div>
                                                <div class="text-center">
                                                    <div class="display-6 text-success"><?php echo $docsValides; ?></div>
                                                    <small class="text-muted">Validés</small>
                                                </div>
                                                <?php if ($docsRejetes > 0): ?>
                                                    <div class="text-center">
                                                        <div class="display-6 text-danger"><?php echo $docsRejetes; ?></div>
                                                        <small class="text-muted">Rejetés</small>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="list-group list-group-flush">
                                            <?php foreach (array_slice($documents, 0, 3) as $doc): ?>
                                                <div class="list-group-item px-0 border-0 border-bottom">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div class="flex-grow-1">
                                                            <i class="bi bi-file-earmark-pdf text-danger"></i>
                                                            <strong><?php echo htmlspecialchars($doc['nom_fichier']); ?></strong>
                                                            <br>
                                                            <small class="text-muted">
                                                                <?php echo htmlspecialchars($doc['type_document']); ?>
                                                                - <?php echo number_format($doc['taille_fichier'] / 1024, 2); ?> Ko
                                                            </small>
                                                        </div>
                                                        <span class="badge bg-<?php 
                                                            echo $doc['statut'] === 'valide' ? 'success' : 
                                                                ($doc['statut'] === 'rejete' ? 'danger' : 'warning'); 
                                                        ?>">
                                                            <?php echo ucfirst($doc['statut']); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php if (count($documents) > 3): ?>
                                            <small class="text-muted">
                                                + <?php echo count($documents) - 3; ?> autre(s) document(s)
                                            </small>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="col-md-6">
                                    <h6 class="mb-3"><i class="bi bi-clock-history"></i> Dernières activités</h6>
                                    <?php if (empty($historique)): ?>
                                        <p class="text-muted small">Aucune activité récente</p>
                                    <?php else: ?>
                                        <div class="timeline-mini">
                                            <?php foreach (array_slice($historique, 0, 3) as $hist): ?>
                                                <div class="timeline-item-mini">
                                                    <div class="timeline-marker-mini"></div>
                                                    <div class="timeline-content-mini">
                                                        <strong><?php echo htmlspecialchars(STATUTS_DOSSIER[$hist['statut_nouveau']] ?? $hist['statut_nouveau']); ?></strong>
                                                        <br>
                                                        <small class="text-muted">
                                                            <?php echo date('d/m/Y à H:i', strtotime($hist['date_modification'])); ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="mt-3 d-flex gap-2">
                                <a href="<?php echo url('mon-dossier.php?id=' . $dossier['id']); ?>" class="btn btn-primary">
                                    <i class="bi bi-eye"></i> Voir le détail
                                </a>
                                <?php if ($docsEnAttente > 0 || in_array($statutActuel, ['documents_requis', 'complement_demande'])): ?>
                                    <a href="<?php echo url('documents.php?dossier_id=' . $dossier['id']); ?>" class="btn btn-outline-success">
                                        <i class="bi bi-upload"></i> Uploader des documents
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (count($dossiersAvecDetails) > 3): ?>
                    <div class="text-center mt-3">
                        <a href="<?php echo url('mon-dossier.php'); ?>" class="btn btn-primary">
                            Voir tous mes dossiers (<?php echo count($dossiersAvecDetails); ?>)
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-folder-x display-1 text-muted mb-3"></i>
                <h5 class="text-muted">Aucun dossier enregistré</h5>
                <p class="text-muted">Vos dossiers apparaîtront ici une fois créés par l'administration.</p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

</div>
<?php include __DIR__ . '/includes/footer.php'; ?>



