<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/AuthModel.php';
require_once __DIR__ . '/models/RendezVousModel.php';
require_once __DIR__ . '/models/ClientModel.php';

$auth = new AuthModel();
if (!$auth->isLoggedIn() || !$auth->isClient()) {
    header('Location: ' . url('login.php'));
    exit;
}

$rdvModel = new RendezVousModel();
$clientModel = new ClientModel();

// Récupérer le client, créer automatiquement le profil s'il n'existe pas
$client = $clientModel->getClientByUserId($auth->getUserId(), true);

// Vérifier si le client existe
if (!$client || !is_array($client) || !isset($client['id'])) {
    $_SESSION['error'] = 'Erreur : profil client introuvable.';
    header('Location: ' . url('logout.php'));
    exit;
}

$rdvs = $rdvModel->getRendezVous($client['id']);
$rdvAVenir = $rdvModel->getRendezVousAVenir($client['id']);

include __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row mb-5">
        <div class="col-12">
            <h1 class="h2 fw-bold mb-2"><i class="bi bi-calendar-event me-2 text-primary"></i> Mes rendez-vous</h1>
            <p class="text-muted mb-0">Consultation et gestion de vos rendez-vous</p>
        </div>
    </div>

<?php if (!empty($rdvAVenir)): ?>
    <div class="card shadow-lg border-0 rounded-4 mb-5 overflow-hidden">
        <div class="card-header bg-success text-white py-3 px-4">
            <h5 class="mb-0"><i class="bi bi-calendar-check me-2"></i> Rendez-vous à venir</h5>
        </div>
        <div class="card-body p-0">
            <div class="list-group list-group-flush">
                <?php foreach ($rdvAVenir as $rdv): ?>
                    <div class="list-group-item p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <div class="bg-success-subtle text-success rounded-circle p-3 me-3">
                                    <i class="bi bi-calendar-day fs-4"></i>
                                </div>
                                <div>
                                    <h5 class="mb-1 fw-bold">
                                        <?php echo date('d/m/Y', strtotime($rdv['date_heure'])); ?>
                                        <span class="text-muted fw-normal ms-2">à <?php echo date('H:i', strtotime($rdv['date_heure'])); ?></span>
                                    </h5>
                                    <p class="mb-1 text-primary fw-medium"><?php echo htmlspecialchars($rdv['type_rendez_vous']); ?></p>
                                    <?php if ($rdv['notes']): ?>
                                        <p class="mb-0 text-muted small"><i class="bi bi-chat-left-text me-1"></i> <?php echo nl2br(htmlspecialchars($rdv['notes'])); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="badge rounded-pill px-3 py-2 bg-<?php 
                                    $rdvStatutColor = 'primary';
                                    if ($rdv['statut'] === 'planifie') {
                                        $rdvStatutColor = 'secondary';
                                    } elseif ($rdv['statut'] === 'confirme') {
                                        $rdvStatutColor = 'success';
                                    }
                                    echo $rdvStatutColor;
                                ?> mb-2 d-block">
                                    <?php echo ucfirst($rdv['statut']); ?>
                                </span>
                                <?php if ($rdv['numero_dossier']): ?>
                                    <small class="text-muted d-block bg-light rounded px-2 py-1 border">
                                        <i class="bi bi-folder me-1"></i> <?php echo htmlspecialchars($rdv['numero_dossier']); ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="card shadow-sm border-0 rounded-4">
    <div class="card-header bg-white py-3 px-4">
        <h5 class="mb-0 text-gray-800"><i class="bi bi-clock-history me-2"></i> Historique des rendez-vous</h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($rdvs)): ?>
            <div class="text-center py-5">
                <i class="bi bi-calendar-x text-muted mb-3" style="font-size: 2rem;"></i>
                <p class="text-muted mb-0">Aucun rendez-vous enregistré</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="px-4 py-3 border-0">Date/Heure</th>
                            <th class="py-3 border-0">Type</th>
                            <th class="py-3 border-0">Dossier</th>
                            <th class="py-3 border-0">Statut</th>
                            <th class="py-3 border-0">Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rdvs as $rdv): ?>
                            <tr>
                                <td class="px-4">
                                    <div class="fw-bold"><?php echo date('d/m/Y', strtotime($rdv['date_heure'])); ?></div>
                                    <small class="text-muted"><?php echo date('H:i', strtotime($rdv['date_heure'])); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($rdv['type_rendez_vous']); ?></td>
                                <td>
                                    <?php if ($rdv['numero_dossier']): ?>
                                        <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-2">
                                            <?php echo htmlspecialchars($rdv['numero_dossier']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge rounded-pill bg-<?php 
                                        $rdvStatutColor = 'primary';
                                        if ($rdv['statut'] === 'planifie') {
                                            $rdvStatutColor = 'secondary';
                                        } elseif ($rdv['statut'] === 'confirme') {
                                            $rdvStatutColor = 'success';
                                        } elseif ($rdv['statut'] === 'annule') {
                                            $rdvStatutColor = 'danger';
                                        } elseif ($rdv['statut'] === 'termine') {
                                            $rdvStatutColor = 'info';
                                        }
                                        echo $rdvStatutColor;
                                    ?>">
                                        <?php echo ucfirst($rdv['statut']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($rdv['notes']): ?>
                                        <span class="d-inline-block text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($rdv['notes']); ?>">
                                            <?php echo htmlspecialchars($rdv['notes']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
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

<?php include __DIR__ . '/includes/footer.php'; ?>

