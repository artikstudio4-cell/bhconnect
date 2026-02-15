<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/AuthModel.php';
require_once __DIR__ . '/models/RendezVousModel.php';
require_once __DIR__ . '/models/ClientModel.php';
require_once __DIR__ . '/models/DossierModel.php';
require_once __DIR__ . '/models/WorkflowService.php';

$auth = new AuthModel();
if (!$auth->isLoggedIn() || (!$auth->isAdmin() && !$auth->isAgent())) {
    header('Location: ' . url('login.php'));
    exit;
}

// Récupérer l'agent si c'est un agent
$agentId = null;
if ($auth->isAgent()) {
    require_once __DIR__ . '/models/AgentModel.php';
    $agentModel = new AgentModel();
    $agent = $agentModel->getAgentByUserId($auth->getUserId());
    if ($agent) {
        $agentId = $agent['id'];
    }
}

$rdvModel = new RendezVousModel();
$clientModel = new ClientModel();
$dossierModel = new DossierModel();
$message = '';
$error = '';
$workflowService = new WorkflowService();

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier le token CSRF
    if (!CSRFToken::verify($_POST['csrf_token'] ?? '')) {
        $error = 'Jeton de sécurité invalide';
    } elseif (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                // Seulement les admins peuvent créer des rendez-vous directement
                if (!$auth->isAdmin()) {
                    $error = 'Vous n\'avez pas le droit de créer un rendez-vous directement. Les rendez-vous doivent être créés via les créneaux disponibles.';
                    break;
                }
                $data = [
                    'client_id' => $_POST['client_id'],
                    'dossier_id' => $_POST['dossier_id'] ?? null,
                    'date_heure' => $_POST['date_heure'],
                    'type_rendez_vous' => $_POST['type_rendez_vous'],
                    'notes' => $_POST['notes'] ?? null
                ];
                if ($rdvModel->createRendezVous($data)) {
                    $message = 'Rendez-vous créé avec succès';
                } else {
                    $error = 'Erreur lors de la création du rendez-vous';
                }
                break;
                
            case 'update':
                // Les agents peuvent mettre à jour le statut des rendez-vous de leurs clients
                $id = $_POST['id'];
                
                // Vérifier que l'agent ne peut modifier que les RDV de ses clients
                if ($auth->isAgent()) {
                    $rdv = $rdvModel->getRendezVousById($id);
                    if (!$rdv || $rdv['agent_id'] != $agentId) {
                        $error = 'Vous n\'avez pas le droit de modifier ce rendez-vous';
                        break;
                    }
                }
                
                $data = [
                    'date_heure' => $_POST['date_heure'],
                    'type_rendez_vous' => $_POST['type_rendez_vous'],
                    'notes' => $_POST['notes'] ?? null,
                    'statut' => $_POST['statut']
                ];
                
                // Les agents peuvent seulement modifier le statut
                if ($auth->isAgent() && isset($_POST['statut'])) {
                    $data = ['statut' => $_POST['statut']];
                    // Mise à jour simplifiée pour les agents
                    $stmt = Database::getInstance()->getConnection()->prepare("
                        UPDATE rendez_vous SET statut = ? WHERE id = ? AND agent_id = ?
                    ");
                    if ($stmt->execute([$_POST['statut'], $id, $agentId])) {
                        // Déclencher le workflow si le RDV est terminé
                        if ($_POST['statut'] === 'effectue') {
                            $workflowService->processRendezVousCompletion($id, $auth->getUserId());
                        }
                        $message = 'Statut du rendez-vous mis à jour avec succès';
                    } else {
                        $error = 'Erreur lors de la mise à jour';
                    }
                } else {
                    if ($rdvModel->updateRendezVous($id, $data)) {
                        // Déclencher le workflow si le RDV est terminé
                        if (($data['statut'] ?? '') === 'effectue') {
                            $workflowService->processRendezVousCompletion($id, $auth->getUserId());
                        }
                        $message = 'Rendez-vous mis à jour avec succès';
                    } else {
                        $error = 'Erreur lors de la mise à jour';
                    }
                }
                break;
                
            case 'delete':
                $id = $_POST['id'];
                if ($rdvModel->deleteRendezVous($id)) {
                    $message = 'Rendez-vous supprimé avec succès';
                } else {
                    $error = 'Erreur lors de la suppression';
                }
                break;
        }
    }
}

$action = $_GET['action'] ?? 'list';
$rdvId = $_GET['id'] ?? null;
$rdv = $rdvId ? $rdvModel->getRendezVousById($rdvId) : null;

// Récupérer les rendez-vous selon le rôle
if ($auth->isAdmin()) {
    $rdvs = $rdvModel->getRendezVous();
} else {
    // Agent : voir les rendez-vous de ses clients
    // Récupérer les RDV à venir
    $rdvsAVenir = $rdvModel->getRendezVousAVenir(null, 1000, $agentId);
    // Récupérer tous les RDV et filtrer ceux de l'agent
    $rdvsTous = $rdvModel->getRendezVous(null);
    $rdvsAgent = [];
    foreach ($rdvsTous as $r) {
        if ($r['agent_id'] == $agentId) {
            $rdvsAgent[] = $r;
        }
    }
    // Combiner et dédupliquer
    $rdvsIds = [];
    $rdvs = [];
    foreach (array_merge($rdvsAVenir, $rdvsAgent) as $r) {
        if (!in_array($r['id'], $rdvsIds)) {
            $rdvsIds[] = $r['id'];
            $rdvs[] = $r;
        }
    }
    // Trier par date
    usort($rdvs, function($a, $b) {
        return strtotime($b['date_heure']) - strtotime($a['date_heure']);
    });
}

// Récupérer les clients selon le rôle
if ($auth->isAdmin()) {
    $clients = $clientModel->getAllClients();
} else {
    // Agent : voir tous les clients pour assigner des rendez-vous
    $clients = $clientModel->getAllClients();
}

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

    <?php if ($action === 'create' || $action === 'edit'): ?>
        <div class="row">
            <div class="col-md-6 offset-md-3">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 text-gray-800">
                            <i class="bi bi-calendar-plus text-primary me-2"></i>
                            <?php echo $action === 'create' ? 'Nouveau Rendez-vous' : 'Modifier le Rendez-vous'; ?>
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST">
                            <?php echo CSRFToken::field(); ?>
                            <input type="hidden" name="action" value="<?php echo $action; ?>">
                            <?php if ($action === 'edit'): ?>
                                <input type="hidden" name="id" value="<?php echo $rdv['id']; ?>">
                            <?php endif; ?>

                            <div class="mb-3">
                                <label for="client_id" class="form-label">Client *</label>
                                <select name="client_id" id="client_id" class="form-select" required>
                                    <option value="">Sélectionner un client</option>
                                    <?php foreach ($clients as $client): ?>
                                        <option value="<?php echo $client['id']; ?>" 
                                            <?php echo (isset($rdv) && $rdv['client_id'] == $client['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($client['nom'] . ' ' . $client['prenom']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="agent_id" class="form-label">Agent *</label>
                                <select name="agent_id" id="agent_id" class="form-select" required>
                                    <option value="">Sélectionner un agent</option>
                                    <?php foreach ($agents as $agent): ?>
                                        <option value="<?php echo $agent['id']; ?>"
                                            <?php echo (isset($rdv) && $rdv['agent_id'] == $agent['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($agent['nom'] . ' ' . $agent['prenom']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="date_heure" class="form-label">Date et Heure *</label>
                                <input type="datetime-local" class="form-control" id="date_heure" name="date_heure" 
                                       value="<?php echo isset($rdv) ? date('Y-m-d\TH:i', strtotime($rdv['date_heure'])) : ''; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="motif" class="form-label">Motif *</label>
                                <textarea class="form-control" id="motif" name="motif" rows="3" required><?php echo htmlspecialchars($rdv['motif'] ?? ''); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="statut" class="form-label">Statut</label>
                                <select name="statut" id="statut" class="form-select">
                                    <?php
                                    foreach (Constants::getRdvStatuses() as $statut):
                                    ?>
                                        <option value="<?php echo $statut; ?>" <?php echo (isset($rdv) && $rdv['statut'] === $statut) ? 'selected' : ''; ?>>
                                            <?php echo ucfirst($statut); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="d-flex gap-2 justify-content-end">
                                <a href="<?php echo url('rendez-vous.php'); ?>" class="btn btn-light border">Annuler</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-lg me-2"></i>Enregistrer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-calendar-event text-primary me-2"></i>Gestion des Rendez-vous</h1>
            <a href="<?php echo url('rendez-vous.php?action=create'); ?>" class="btn btn-primary">
                <i class="bi bi-plus-lg me-2"></i>Nouveau Rendez-vous
            </a>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Date</th>
                                <th>Heure</th>
                                <th>Client</th>
                                <th>Agent</th>
                                <th>Motif</th>
                                <th>Statut</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rendezVous)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">Aucun rendez-vous trouvé</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($rendezVous as $rdv): ?>
                                    <tr>
                                        <td class="ps-4 fw-medium"><?php echo date('d/m/Y', strtotime($rdv['date_heure'])); ?></td>
                                        <td><?php echo date('H:i', strtotime($rdv['date_heure'])); ?></td>
                                        <td><?php echo htmlspecialchars($rdv['client_nom'] . ' ' . $rdv['client_prenom']); ?></td>
                                        <td><?php echo htmlspecialchars($rdv['agent_nom'] . ' ' . $rdv['agent_prenom']); ?></td>
                                        <td><?php echo htmlspecialchars($rdv['motif']); ?></td>
                                        <td>
                                            <?php
                                            $badges = [
                                                'planifie' => 'bg-primary',
                                                'confirme' => 'bg-success',
                                                'effectue' => 'bg-info',
                                                'annule'   => 'bg-danger'
                                            ];
                                            $badgeClass = $badges[$rdv['statut']] ?? 'bg-secondary';
                                            ?>
                                            <span class="badge <?php echo $badgeClass; ?>"><?php echo ucfirst($rdv['statut']); ?></span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="btn-group">
                                                <a href="<?php echo url('rendez-vous.php?action=edit&id=' . $rdv['id']); ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce rendez-vous ?');">
                                                    <?php echo CSRFToken::field(); ?>
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?php echo $rdv['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
