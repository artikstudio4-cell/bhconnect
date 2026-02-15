<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/AuthModel.php';
require_once __DIR__ . '/models/DossierModel.php';
require_once __DIR__ . '/models/RendezVousModel.php';
require_once __DIR__ . '/models/ClientModel.php';
require_once __DIR__ . '/models/AgentModel.php';
require_once __DIR__ . '/models/NotificationModel.php';
require_once __DIR__ . '/models/AuditModel.php';

$auth = new AuthModel();
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ' . url('login.php'));
    exit;
}

try {
    $dossierModel = new DossierModel();
    $rdvModel = new RendezVousModel();
    $clientModel = new ClientModel();
    $agentModel = new AgentModel();
    $notificationModel = new NotificationModel();
    
    // Statistiques globales
    $stats = $dossierModel->getStatistiques();
    $totalClients = count($clientModel->getAllClients());
    $totalAgents = count($agentModel->getAllAgents());
    $rdvAVenir = $rdvModel->getRendezVousAVenir(null, 10);
    $notificationsNonLues = $notificationModel->getUnreadCount($auth->getUserId());
    
    // Tentative de chargement de AuditModel (peut ne pas fonctionner si la table n'existe pas)
    $auditModel = null;
    $auditStats = ['total_actions' => 0, 'nb_utilisateurs' => 0, 'nb_actions_differentes' => 0, 'nb_tables_affectees' => 0];
    $topActions = [];
    $topUsers = [];
    $recentLogs = [];
    
    try {
        $auditModel = new AuditModel();
        $auditStats = $auditModel->getStats();
        $topActions = $auditModel->getTopActions(5);
        $topUsers = $auditModel->getTopUsers(5);
        $recentLogs = $auditModel->getLogs([], 10, 0);
    } catch (Exception $e) {
        // Si AuditModel échoue, continuer sans les stats d'audit
        error_log("Erreur AuditModel dans dashboard: " . $e->getMessage());
    }
} catch (Exception $e) {
    error_log("Erreur lors du chargement du dashboard: " . $e->getMessage());
    $_SESSION['error'] = 'Une erreur est survenue lors du chargement du tableau de bord.';
    header('Location: ' . url('login.php'));
    exit;
}

include __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mb-1"><i class="bi bi-speedometer2 text-primary"></i> Dashboard Administrateur</h1>
                    <p class="text-muted mb-0">Vue d'ensemble complète du cabinet</p>
                </div>
                <?php if ($notificationsNonLues > 0): ?>
                    <a href="<?php echo url('notifications.php'); ?>" class="btn btn-warning position-relative">
                        <i class="bi bi-bell"></i> Notifications
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?php echo $notificationsNonLues; ?>
                        </span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Statistiques principales -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-primary shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-2 opacity-75">Total Agents</h6>
                            <h2 class="card-title mb-0"><?php echo $totalAgents; ?></h2>
                        </div>
                        <i class="bi bi-people-fill fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-info shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-2 opacity-75">Total Clients</h6>
                            <h2 class="card-title mb-0"><?php echo $totalClients; ?></h2>
                        </div>
                        <i class="bi bi-people-fill fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- ... other cards ... -->
        <!-- I am truncating for brevity in this replacement tool, but I should apply to the whole file. 
             Since replace_file_content replaces a block, I should target the start and end of the block 
             I want to wrap. 
             Actually, to wrap the whole file content, I can't easily do it in one go if I don't provide the whole content.
             I will just add the div start after header and div end before footer.
        -->
</div>

<!-- Statistiques principales -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-2 opacity-75">Total Agents</h6>
                        <h2 class="card-title mb-0"><?php echo $totalAgents; ?></h2>
                    </div>
                    <i class="bi bi-people-fill fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card text-white bg-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-2 opacity-75">Total Clients</h6>
                        <h2 class="card-title mb-0"><?php echo $totalClients; ?></h2>
                    </div>
                    <i class="bi bi-person-fill fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-2 opacity-75">Total Dossiers</h6>
                        <h2 class="card-title mb-0"><?php echo $stats['total']; ?></h2>
                    </div>
                    <i class="bi bi-folder-fill fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-2 opacity-75">RDV à venir</h6>
                        <h2 class="card-title mb-0"><?php echo count($rdvAVenir); ?></h2>
                    </div>
                    <i class="bi bi-calendar-event-fill fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Actions rapides -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-lightning-charge"></i> Actions Rapides</h5>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    <a href="<?php echo url('agents.php?action=create'); ?>" class="btn btn-primary">
                        <i class="bi bi-person-plus"></i> Nouveau agent
                    </a>
                    <a href="<?php echo url('clients.php?action=create'); ?>" class="btn btn-success">
                        <i class="bi bi-person-plus"></i> Nouveau client
                    </a>
                    <a href="<?php echo url('dossiers.php?action=create'); ?>" class="btn btn-info">
                        <i class="bi bi-folder-plus"></i> Nouveau dossier
                    </a>
                    <a href="<?php echo url('audit.php'); ?>" class="btn btn-secondary">
                        <i class="bi bi-file-text"></i> Logs d'audit
                    </a>
                    <a href="<?php echo url('admin/quiz_resultats.php'); ?>" class="btn btn-outline-primary">
                        <i class="bi bi-trophy"></i> Résultats Quiz
                    </a>
                    <a href="<?php echo url('admin/quiz_questions.php'); ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-question-circle"></i> Gérer Quiz
                    </a>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="bi bi-download"></i> Exporter
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="<?php echo url('api/export_clients.php?format=csv'); ?>">
                                    <i class="bi bi-file-earmark-spreadsheet"></i> Clients (CSV)
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo url('api/export_clients.php?format=json'); ?>">
                                    <i class="bi bi-file-earmark-code"></i> Clients (JSON)
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Dossiers par statut et Prochains RDV -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Dossiers par statut</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Statut</th>
                            <th class="text-end">Nombre</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['par_statut'] as $stat): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(STATUTS_DOSSIER[$stat['statut']] ?? $stat['statut']); ?></td>
                                <td class="text-end"><strong><?php echo $stat['nombre']; ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-calendar-check"></i> Prochains rendez-vous</h5>
            </div>
            <div class="card-body">
                <?php if (empty($rdvAVenir)): ?>
                    <p class="text-muted mb-0">Aucun rendez-vous à venir</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach (array_slice($rdvAVenir, 0, 5) as $rdv): ?>
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($rdv['nom'] . ' ' . $rdv['prenom']); ?></h6>
                                        <p class="mb-1 text-muted small">
                                            <i class="bi bi-calendar"></i> <?php echo date('d/m/Y à H:i', strtotime($rdv['date_heure'])); ?>
                                        </p>
                                        <p class="mb-0 small"><?php echo htmlspecialchars($rdv['type_rendez_vous']); ?></p>
                                    </div>
                                    <span class="badge bg-<?php echo $rdv['statut'] === 'confirme' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($rdv['statut']); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-3">
                        <a href="<?php echo url('rendez-vous.php'); ?>" class="btn btn-sm btn-outline-primary">Voir tous les rendez-vous</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Logs d'audit récents -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-shield-check"></i> Actions les plus fréquentes</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Action</th>
                            <th class="text-end">Nombre</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topActions as $action): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($action['action']); ?></td>
                                <td class="text-end"><strong><?php echo $action['nombre']; ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Derniers logs d'audit</h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <?php if (empty($recentLogs)): ?>
                        <div class="text-center text-muted py-3">
                            <p class="mb-0">Aucun log d'audit disponible</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recentLogs as $log): ?>
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($log['action']); ?></h6>
                                        <p class="mb-1 text-muted small">
                                            <?php echo htmlspecialchars($log['utilisateur_email']); ?> - 
                                            <?php echo htmlspecialchars($log['table_affectee']); ?>
                                        </p>
                                        <small class="text-muted">
                                            <?php echo date('d/m/Y H:i', strtotime($log['date_action'])); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="mt-3">
                    <a href="<?php echo url('audit.php'); ?>" class="btn btn-sm btn-outline-primary">Voir tous les logs</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statistiques d'audit -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-graph-up"></i> Statistiques d'audit</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-3">
                        <div class="p-3">
                            <div class="display-4 text-primary mb-2"><?php echo $auditStats['total_actions']; ?></div>
                            <p class="text-muted mb-0">Total actions</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3">
                            <div class="display-4 text-info mb-2"><?php echo $auditStats['nb_utilisateurs']; ?></div>
                            <p class="text-muted mb-0">Utilisateurs actifs</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3">
                            <div class="display-4 text-success mb-2"><?php echo $auditStats['nb_actions_differentes']; ?></div>
                            <p class="text-muted mb-0">Types d'actions</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3">
                            <div class="display-4 text-warning mb-2"><?php echo $auditStats['nb_tables_affectees']; ?></div>
                            <p class="text-muted mb-0">Tables affectées</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
