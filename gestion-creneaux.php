<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/AuthModel.php';
require_once __DIR__ . '/models/CreneauModel.php';
require_once __DIR__ . '/models/AgentModel.php';
require_once __DIR__ . '/models/EmailService.php';
require_once __DIR__ . '/models/ClientModel.php';
$clientModel = new ClientModel();

$auth = new AuthModel();
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ' . url('login.php'));
    exit;
}

$creneauModel = new CreneauModel();
$agentModel = new AgentModel();
$agents = $agentModel->getAllAgents();
$message = '';
$error = '';

// Traitement des actions via le contrôleur
require_once __DIR__ . '/controllers/CreneauController.php';
$controller = new CreneauController();
$result = $controller->handleRequest();
$message = $result['message'];
$error = $result['error'];

// Récupérer tous les créneaux
$creneaux = $creneauModel->getAllCreneaux();

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
        <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-calendar-check text-primary me-2"></i>Gestion des Créneaux</h1>
    </div>

    <div class="row">
        <!-- Formulaire d'ajout -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-gray-800"><i class="bi bi-plus-circle me-2"></i>Ajouter un créneau</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php echo CSRFToken::field(); ?>
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label for="date_debut" class="form-label">Date et heure de début</label>
                            <input type="datetime-local" class="form-control" id="date_debut" name="date_debut" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="heure_fin" class="form-label">Date et heure de fin</label>
                            <input type="datetime-local" class="form-control" id="heure_fin" name="heure_fin" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="agent_id" class="form-label">Agent</label>
                            <select class="form-select" id="agent_id" name="agent_id" required>
                                <option value="">Sélectionner un agent</option>
                                <?php foreach ($agents as $agent): ?>
                                    <option value="<?php echo $agent['id']; ?>">
                                        <?php echo htmlspecialchars($agent['nom'] . ' ' . $agent['prenom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="note" class="form-label">Note (optionnel)</label>
                            <input type="text" class="form-control" id="note" name="note" placeholder="Ex: Consultation vidéo">
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="disponible" name="disponible" value="1" checked>
                            <label class="form-check-label" for="disponible">Créneau disponible ?</label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check-lg me-2"></i>Ajouter le créneau
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Liste des créneaux -->
        <div class="col-md-8 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-gray-800"><i class="bi bi-list me-2"></i>Créneaux disponibles</h5>
                    <?php if (!empty($creneaux)): ?>
                        <form method="POST" onsubmit="return confirm('Voulez-vous vraiment supprimer tous les créneaux passés ?');">
                            <?php echo CSRFToken::field(); ?>
                            <input type="hidden" name="action" value="delete_all">
                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                <i class="bi bi-trash me-1"></i>Nettoyer historique
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Date</th>
                                    <th>Heure</th>
                                    <th>Agent</th>
                                    <th>Statut</th>
                                    <th class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($creneaux)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">Aucun créneau disponible</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($creneaux as $creneau): ?>
                                        <tr>
                                            <td class="ps-4 fw-medium"><?php echo date('d/m/Y', strtotime($creneau['date_creneau'])); ?></td>
                                            <td>
                                                <?php 
                                                    echo date('H:i', strtotime($creneau['heure_debut'])) . ' - ' . 
                                                         date('H:i', strtotime($creneau['heure_fin'])); 
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars(($creneau['agent_nom'] ?? 'Tous') . ' ' . ($creneau['agent_prenom'] ?? '')); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $creneau['disponible'] ? 'success' : 'secondary'; ?>">
                                                    <?php echo $creneau['disponible'] ? 'Disponible' : 'Réservé'; ?>
                                                </span>
                                            </td>
                                            <td class="text-end pe-4">
                                                <form method="POST" onsubmit="return confirm('Supprimer ce créneau ?');">
                                                    <?php echo CSRFToken::field(); ?>
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?php echo $creneau['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
