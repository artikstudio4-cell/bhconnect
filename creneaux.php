<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/AuthModel.php';
require_once __DIR__ . '/models/CreneauModel.php';
require_once __DIR__ . '/models/DossierModel.php';
require_once __DIR__ . '/models/ClientModel.php';
require_once __DIR__ . '/models/NotificationModel.php';
require_once __DIR__ . '/models/RendezVousModel.php';
require_once __DIR__ . '/models/AgentModel.php';

$auth = new AuthModel();
if (!$auth->isLoggedIn() || (!$auth->isClient() && !$auth->isAgent() && !$auth->isAdmin())) {
    header('Location: ' . url('login.php'));
    exit;
}

$creneauModel = new CreneauModel();
$dossierModel = new DossierModel();
$clientModel = new ClientModel();
$agentModel = new AgentModel();
$notificationModel = new NotificationModel();

// Récupérer le client seulement si c'est un client
$client = null;
$dossiers = [];
if ($auth->isClient()) {
    $client = $clientModel->getClientByUserId($auth->getUserId(), true);
    
    // Vérifier si le client existe
    if (!$client || !is_array($client) || !isset($client['id'])) {
        $_SESSION['error'] = 'Erreur : profil client introuvable.';
        header('Location: ' . url('logout.php'));
        exit;
    }
    
    $dossiers = $dossierModel->getDossiers($client['id']);
}

$message = '';
$error = '';

// Traitement de la réservation (seulement pour les clients)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reserver' && $auth->isClient() && $client) {
    $creneauId = $_POST['creneau_id'] ?? null;
    $dossierId = !empty($_POST['dossier_id']) ? $_POST['dossier_id'] : null;
    $typeRdv = $_POST['type_rendez_vous'] ?? 'Consultation';
    $notes = $_POST['notes'] ?? null;
    
    if ($creneauId && $client) {
        try {
            $rdvId = $creneauModel->reserverCreneau(
                $creneauId,
                $client['id'],
                $typeRdv,
                $auth->getUserId(),
                $dossierId,
                $notes
            );
            
            // Notifier l'agent si assigné
            $rdvModel = new RendezVousModel();
            $rdv = $rdvModel->getRendezVousById($rdvId);
            if ($rdv && isset($rdv['agent_id']) && $rdv['agent_id']) {
                // Récupérer l'ID utilisateur de l'agent
                $agentModel = new AgentModel();
                $agent = $agentModel->getAgentById($rdv['agent_id']);
                if ($agent && isset($agent['utilisateur_id'])) {
                    $notificationModel->create(
                        $agent['utilisateur_id'],
                        'rdv_demande',
                        'Nouvelle demande de RDV',
                        'Un client a demandé un rendez-vous',
                        url('rendez-vous.php?id=' . $rdvId),
                        true
                    );
                }
            }
            
            $message = 'Rendez-vous réservé avec succès. En attente de validation par l\'agent.';
        } catch (Exception $e) {
            $error = 'Erreur : ' . $e->getMessage();
        }
    } else {
        $error = 'Créneau invalide ou client introuvable';
    }
}

// Récupérer les créneaux disponibles
$dateDebut = $_GET['date_debut'] ?? date('Y-m-d');
$dateFin = $_GET['date_fin'] ?? date('Y-m-d', strtotime('+2 weeks'));

// Si c'est un agent, ne montrer que ses créneaux

// Pour les agents, afficher tous les créneaux disponibles (pas de filtre agent_id)
$creneaux = $creneauModel->getCreneauxDisponibles($dateDebut, $dateFin, null);

// Grouper les créneaux par date
$creneauxParDate = [];
foreach ($creneaux as $creneau) {
    $date = $creneau['date_creneau'];
    if (!isset($creneauxParDate[$date])) {
        $creneauxParDate[$date] = [];
    }
    $creneauxParDate[$date][] = $creneau;
}
ksort($creneauxParDate);

include __DIR__ . '/includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h1><i class="bi bi-calendar-check"></i> 
            <?php if ($auth->isClient()): ?>
                Réservation de Rendez-vous
            <?php elseif ($auth->isAgent()): ?>
                Planning Disponible
            <?php else: ?>
                Créneaux Disponibles
            <?php endif; ?>
        </h1>
        <p class="text-muted">
            <?php if ($auth->isClient()): ?>
                Consultez les créneaux disponibles et réservez votre rendez-vous
            <?php elseif ($auth->isAgent()): ?>
                Consultez le planning des créneaux disponibles pour vos clients
            <?php else: ?>
                Consultez tous les créneaux disponibles
            <?php endif; ?>
        </p>
    </div>
</div>

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

<!-- Filtres de date -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="date_debut" class="form-label">Date de début</label>
                        <input type="date" class="form-control" id="date_debut" name="date_debut" 
                               value="<?php echo htmlspecialchars($dateDebut); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="date_fin" class="form-label">Date de fin</label>
                        <input type="date" class="form-control" id="date_fin" name="date_fin" 
                               value="<?php echo htmlspecialchars($dateFin); ?>" required>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Rechercher
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Liste des créneaux disponibles -->
<?php if (empty($creneauxParDate)): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> Aucun créneau disponible pour cette période.
    </div>
<?php else: ?>
    <?php foreach ($creneauxParDate as $date => $creneauxJour): ?>
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-calendar3"></i> 
                    <?php echo date('l d/m/Y', strtotime($date)); ?>
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <?php foreach ($creneauxJour as $creneau): ?>
                        <div class="col-md-3 mb-2">
                            <div class="card border">
                                <div class="card-body text-center">
                                    <h6 class="mb-2">
                                        <?php echo date('H:i', strtotime($creneau['heure_debut'])); ?> - 
                                        <?php echo date('H:i', strtotime($creneau['heure_fin'])); ?>
                                    </h6>
                                    <?php if ($creneau['agent_nom']): ?>
                                        <p class="text-muted small mb-2">
                                            <i class="bi bi-person"></i> 
                                            <?php echo htmlspecialchars($creneau['agent_nom'] . ' ' . $creneau['agent_prenom']); ?>
                                        </p>
                                    <?php else: ?>
                                        <p class="text-muted small mb-2">Agent à assigner</p>
                                    <?php endif; ?>
                                    <?php if ($auth->isClient()): ?>
                                        <button type="button" class="btn btn-sm btn-primary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#reservationModal"
                                                onclick="setCreneau(<?php echo $creneau['id']; ?>, '<?php echo $date . ' ' . $creneau['heure_debut']; ?>')">
                                            <i class="bi bi-calendar-plus"></i> Réserver
                                        </button>
                                    <?php else: ?>
                                        <span class="badge bg-success">Disponible</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Modal de réservation -->
<div class="modal fade" id="reservationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Réserver un créneau</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="reserver">
                    <input type="hidden" name="creneau_id" id="creneau_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Créneau sélectionné</label>
                        <input type="text" class="form-control" id="creneau_info" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="dossier_id" class="form-label">Dossier concerné (optionnel)</label>
                        <select class="form-select" id="dossier_id" name="dossier_id">
                            <option value="">Aucun dossier spécifique</option>
                            <?php foreach ($dossiers as $dossier): ?>
                                <option value="<?php echo $dossier['id']; ?>">
                                    <?php echo htmlspecialchars($dossier['numero_dossier'] . ' - ' . $dossier['type_dossier']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="type_rendez_vous" class="form-label">Type de rendez-vous *</label>
                        <select class="form-select" id="type_rendez_vous" name="type_rendez_vous" required>
                            <option value="Consultation">Consultation</option>
                            <option value="Suivi de dossier">Suivi de dossier</option>
                            <option value="Signature de documents">Signature de documents</option>
                            <option value="Entretien">Entretien</option>
                            <option value="Autre">Autre</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes (optionnel)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Réserver</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function setCreneau(creneauId, creneauInfo) {
    document.getElementById('creneau_id').value = creneauId;
    document.getElementById('creneau_info').value = creneauInfo;
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

