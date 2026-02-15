<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/AuthModel.php';
require_once __DIR__ . '/models/DossierModel.php';
require_once __DIR__ . '/models/RendezVousModel.php';
require_once __DIR__ . '/models/ClientModel.php';
require_once __DIR__ . '/models/AgentModel.php';
require_once __DIR__ . '/models/NotificationModel.php';

$auth = new AuthModel();
if (!$auth->isLoggedIn() || !$auth->isAgent()) {
    header('Location: ' . url('login.php'));
    exit;
}

$dossierModel = new DossierModel();
$rdvModel = new RendezVousModel();
$clientModel = new ClientModel();
$agentModel = new AgentModel();
$notificationModel = new NotificationModel();

$agentId = $auth->getAgentId();
$agent = $agentModel->getAgentByUserId($auth->getUserId());

// Statistiques de l'agent
$stats = $agentModel->getStatistiques($agentId);
$clients = $agentModel->getClientsByAgent($agentId);
$rdvAVenir = $rdvModel->getRendezVousAVenir(null, 10, $agentId);
$notificationsNonLues = $notificationModel->getUnreadCount($auth->getUserId());

// Dossiers de l'agent
$dossiers = $dossierModel->getDossiers(null, $agentId);

include __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="mb-1"><i class="bi bi-speedometer2 text-primary"></i> Dashboard Agent</h1>
                <p class="text-muted mb-0">Bienvenue, <?php echo htmlspecialchars($agent['prenom'] . ' ' . $agent['nom']); ?></p>
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
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card stat-card bg-primary fade-in">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon me-3"><i class="bi bi-people"></i></div>
                <div>
                    <div class="fs-4 fw-bold"> <?php echo $stats['nb_clients'] ?? 0; ?> </div>
                    <div class="text-muted small">Clients</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card bg-info fade-in">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon me-3"><i class="bi bi-folder"></i></div>
                <div>
                    <div class="fs-4 fw-bold"> <?php echo $stats['nb_dossiers'] ?? 0; ?> </div>
                    <div class="text-muted small">Dossiers</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card bg-success fade-in">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon me-3"><i class="bi bi-folder-check"></i></div>
                <div>
                    <div class="fs-4 fw-bold"> <?php echo $stats['nb_dossiers_en_cours'] ?? 0; ?> </div>
                    <div class="text-muted small">Dossiers en cours</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card bg-warning fade-in">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon me-3"><i class="bi bi-calendar-check"></i></div>
                <div>
                    <div class="fs-4 fw-bold"> <?php echo $stats['nb_rdv_a_venir'] ?? 0; ?> </div>
                    <div class="text-muted small">RDV à venir</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Actions rapides -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card fade-in">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-lightning-charge"></i> Actions Rapides</h5>
            </div>
            <div class="card-body d-flex flex-wrap gap-3 align-items-center justify-content-start">
                <a href="clients.php?action=add" class="btn btn-primary btn-lg"><i class="bi bi-person-plus me-2"></i> Nouveau client</a>
                <a href="dossiers.php?action=add" class="btn btn-secondary btn-lg"><i class="bi bi-folder-plus me-2"></i> Nouveau dossier</a>
                <a href="rendez-vous.php?action=add" class="btn btn-success btn-lg"><i class="bi bi-calendar-plus me-2"></i> Nouveau RDV</a>
                <a href="creneaux.php" class="btn btn-outline-primary btn-lg"><i class="bi bi-clock-history me-2"></i> Gérer mes créneaux</a>
            </div>
        </div>
    </div>
</div>

<!-- Mes clients et Prochains RDV -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-people"></i> Mes Clients</h5>
            </div>
            <div class="card-body">
                <?php if (empty($clients)): ?>
                    <p class="text-muted mb-0">Aucun client assigné</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach (array_slice($clients, 0, 5) as $client): ?>
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($client['nom'] . ' ' . $client['prenom']); ?></h6>
                                        <p class="mb-1 text-muted small">
                                            <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($client['email']); ?>
                                        </p>
                                        <small class="text-muted">
                                            <i class="bi bi-folder"></i> <?php echo $client['nb_dossiers']; ?> dossier(s) - 
                                            <i class="bi bi-calendar"></i> <?php echo $client['nb_rdv']; ?> RDV
                                        </small>
                                    </div>
                                    <a href="<?php echo url('clients.php?action=view&id=' . $client['id']); ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($clients) > 5): ?>
                        <div class="mt-3">
                            <a href="<?php echo url('clients.php'); ?>" class="btn btn-sm btn-outline-primary">Voir tous mes clients (<?php echo count($clients); ?>)</a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
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
                                    <div>
                                        <?php if ($rdv['statut'] === 'planifie'): ?>
                                            <a href="<?php echo url('rendez-vous.php?action=confirm&id=' . $rdv['id']); ?>" class="btn btn-sm btn-success">
                                                <i class="bi bi-check"></i> Valider
                                            </a>
                                            <a href="<?php echo url('rendez-vous.php?action=refuse&id=' . $rdv['id']); ?>" class="btn btn-sm btn-danger">
                                                <i class="bi bi-x"></i> Refuser
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-<?php echo $rdv['statut'] === 'confirme' ? 'success' : 'warning'; ?>">
                                                <?php echo ucfirst($rdv['statut']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-3">
                        <a href="<?php echo url('rendez-vous.php'); ?>" class="btn btn-sm btn-outline-primary">Voir tous mes rendez-vous</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Dossiers en cours -->
<?php if (!empty($dossiers)): ?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-folder2-open"></i> Mes Dossiers en Cours</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Numéro</th>
                                <th>Client</th>
                                <th>Type</th>
                                <th>Statut</th>
                                <th>Dernière modification</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($dossiers, 0, 10) as $dossier): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($dossier['numero_dossier']); ?></td>
                                    <td><?php echo htmlspecialchars($dossier['nom'] . ' ' . $dossier['prenom']); ?></td>
                                    <td><?php echo htmlspecialchars($dossier['type_dossier']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            $statutColor = 'primary';
                                            if (in_array($dossier['statut'], ['nouveau', 'soumis'])) {
                                                $statutColor = 'secondary';
                                            } elseif (in_array($dossier['statut'], ['en_revue', 'documents_requis', 'documents_en_verification'])) {
                                                $statutColor = 'warning';
                                            } elseif (in_array($dossier['statut'], ['documents_valides', 'en_traitement'])) {
                                                $statutColor = 'info';
                                            } elseif ($dossier['statut'] === 'valide' || $dossier['statut'] === 'finalise') {
                                                $statutColor = 'success';
                                            }
                                            echo $statutColor;
                                        ?>">
                                            <?php echo htmlspecialchars(STATUTS_DOSSIER[$dossier['statut']] ?? $dossier['statut']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($dossier['date_modification'])); ?></td>
                                    <td>
                                        <a href="<?php echo url('dossiers.php?action=view&id=' . $dossier['id']); ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (count($dossiers) > 10): ?>
                    <div class="mt-3">
                        <a href="<?php echo url('dossiers.php'); ?>" class="btn btn-primary">Voir tous mes dossiers (<?php echo count($dossiers); ?>)</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

</div>
<?php include __DIR__ . '/includes/footer.php'; ?>



