<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/AuthModel.php';
require_once __DIR__ . '/models/PlanningModel.php';
require_once __DIR__ . '/models/AgentModel.php';

$auth = new AuthModel();
if (!$auth->isLoggedIn()) {
    header('Location: ' . url('login.php'));
    exit;
}

// Déterminer le rôle
$isAdmin = $auth->isAdmin();
$isAgent = $auth->isAgent();
$isClient = $auth->isClient();



$planningModel = new PlanningModel();
$agentModel = new AgentModel();
$message = '';
$error = '';

// Traitement des actions
// Seul l'admin peut modifier le planning
if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $data = [
                    'agent_id' => !empty($_POST['agent_id']) ? $_POST['agent_id'] : null,
                    'jour_semaine' => $_POST['jour_semaine'],
                    'heure_debut' => $_POST['heure_debut'],
                    'heure_fin' => $_POST['heure_fin'],
                    'duree_creneau' => $_POST['duree_creneau'] ?? 30,
                    'actif' => isset($_POST['actif'])
                ];
                // Vérification anti-doublon
                $exists = false;
                foreach ($planningModel->getAllPlannings() as $p) {
                    if (
                        $p['agent_id'] == $data['agent_id'] &&
                        $p['jour_semaine'] == $data['jour_semaine'] &&
                        $p['heure_debut'] == $data['heure_debut'] &&
                        $p['heure_fin'] == $data['heure_fin']
                    ) {
                        $exists = true;
                        break;
                    }
                }
                if ($exists) {
                    $error = 'Un planning/créneau existe déjà pour cet agent, ce jour et cette plage horaire.';
                } else if ($planningModel->createPlanning($data, $auth->getUserId())) {
                    $message = 'Planning créé avec succès.';
                } else {
                    $error = 'Erreur lors de la création du planning';
                }
                break;
            case 'update':
                $id = $_POST['id'];
                $data = [
                    'agent_id' => !empty($_POST['agent_id']) ? $_POST['agent_id'] : null,
                    'jour_semaine' => $_POST['jour_semaine'],
                    'heure_debut' => $_POST['heure_debut'],
                    'heure_fin' => $_POST['heure_fin'],
                    'duree_creneau' => $_POST['duree_creneau'] ?? 30,
                    'actif' => isset($_POST['actif'])
                ];
                if ($planningModel->updatePlanning($id, $data)) {
                    $message = 'Planning mis à jour avec succès';
                } else {
                    $error = 'Erreur lors de la mise à jour';
                }
                break;
            case 'delete':
                $id = $_POST['id'];
                if ($planningModel->deletePlanning($id)) {
                    $message = 'Planning supprimé avec succès';
                } else {
                    $error = 'Erreur lors de la suppression';
                }
                break;
            case 'generate':
                $dateDebut = $_POST['date_debut'];
                $dateFin = $_POST['date_fin'];
                $creneauxGeneres = $planningModel->generateCreneaux($dateDebut, $dateFin);
                $message = $creneauxGeneres . ' créneaux générés avec succès';
                break;
        }
    }
}

// Récupérer l'action et l'ID
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

$plannings = $planningModel->getAllPlannings();
$agents = $agentModel->getAllAgents();
$planning = null;
if ($id) {
    $planning = $planningModel->getPlanningById($id);
}

$joursSemaine = [
    1 => 'Lundi',
    2 => 'Mardi',
    3 => 'Mercredi',
    4 => 'Jeudi',
    5 => 'Vendredi',
    6 => 'Samedi',
    7 => 'Dimanche'
];

include __DIR__ . '/includes/header.php';
?>

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
    <!-- Formulaire de création/édition -->
    <div class="row mb-4">
        <div class="col-12">
            <h1><i class="bi bi-calendar-week"></i> <?php echo $action === 'create' ? 'Nouveau Planning' : 'Modifier Planning'; ?></h1>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Informations du planning</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="<?php echo $action; ?>">
                        <?php if ($action === 'edit'): ?>
                            <input type="hidden" name="id" value="<?php echo $planning['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="agent_id" class="form-label">Agent (laisser vide pour planning global)</label>
                            <select class="form-select" id="agent_id" name="agent_id">
                                <option value="">Planning global (tous agents)</option>
                                <?php foreach ($agents as $agent): ?>
                                    <option value="<?php echo $agent['id']; ?>" 
                                            <?php echo ($planning && $planning['agent_id'] == $agent['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($agent['nom'] . ' ' . $agent['prenom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="jour_semaine" class="form-label">Jour de la semaine *</label>
                            <select class="form-select" id="jour_semaine" name="jour_semaine" required>
                                <?php foreach ($joursSemaine as $num => $jour): ?>
                                    <option value="<?php echo $num; ?>" 
                                            <?php echo ($planning && $planning['jour_semaine'] == $num) ? 'selected' : ''; ?>>
                                        <?php echo $jour; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="heure_debut" class="form-label">Heure de début *</label>
                                    <input type="time" class="form-control" id="heure_debut" name="heure_debut" 
                                        value="<?php echo $planning['heure_debut'] ?? '09:00'; ?>" required placeholder="Ex: 09:00">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="heure_fin" class="form-label">Heure de fin *</label>
                                    <input type="time" class="form-control" id="heure_fin" name="heure_fin" 
                                        value="<?php echo $planning['heure_fin'] ?? '17:00'; ?>" required placeholder="Ex: 17:00">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="duree_creneau" class="form-label">Durée créneau (min) *</label>
                                    <input type="number" class="form-control" id="duree_creneau" name="duree_creneau" 
                                        value="<?php echo $planning['duree_creneau'] ?? 30; ?>" min="15" step="15" required placeholder="Ex: 30">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="actif" name="actif" 
                                       <?php echo (!$planning || $planning['actif']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="actif">
                                    Planning actif
                                </label>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check"></i> <?php echo $action === 'create' ? 'Créer' : 'Enregistrer'; ?>
                            </button>
                            <a href="<?php echo url('plannings.php'); ?>" class="btn btn-secondary">
                                <i class="bi bi-x"></i> Annuler
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
<?php else: ?>
    <!-- Liste des plannings -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1><i class="bi bi-calendar-week"></i> Plannings et disponibilités</h1>
                <?php if ($isAdmin): ?>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#generateModal">
                        <i class="bi bi-calendar-plus"></i> Générer créneaux
                    </button>
                    <a href="<?php echo url('plannings.php?action=create'); ?>" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Nouveau Planning
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Liste des plannings</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($plannings)): ?>
                        <p class="text-muted">Aucun planning enregistré</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Agent</th>
                                        <th>Jour</th>
                                        <th>Heures</th>
                                        <th>Durée créneau</th>
                                        <th>Statut</th>
                                        <?php if ($isAdmin): ?>
                                            <th>Actions</th>
                                        <?php elseif ($isClient): ?>
                                            <th>Réserver</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($plannings as $planning): ?>
                                        <tr>
                                            <td>
                                                <?php if ($planning['agent_id']): ?>
                                                    <?php echo htmlspecialchars($planning['agent_nom'] . ' ' . $planning['agent_prenom']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Global (tous agents)</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $joursSemaine[$planning['jour_semaine']]; ?></td>
                                            <td><?php echo date('H:i', strtotime($planning['heure_debut'])); ?> - <?php echo date('H:i', strtotime($planning['heure_fin'])); ?></td>
                                            <td><?php echo $planning['duree_creneau']; ?> min</td>
                                            <td>
                                                <span class="badge bg-<?php echo $planning['actif'] ? 'success' : 'secondary'; ?>">
                                                    <?php echo $planning['actif'] ? 'Actif' : 'Inactif'; ?>
                                                </span>
                                            </td>
                                            <?php if ($isAdmin): ?>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="<?php echo url('plannings.php?action=edit&id=' . $planning['id']); ?>" 
                                                       class="btn btn-outline-primary" title="Modifier">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?php echo $planning['id']; ?>">
                                                        <button type="submit" class="btn btn-outline-danger" title="Supprimer">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                            <?php elseif ($isClient): ?>
                                            <td>
                                                <a href="<?php echo url('rendez-vous.php?planning_id=' . $planning['id']); ?>" class="btn btn-success btn-sm">
                                                    <i class="bi bi-calendar-check"></i> Réserver
                                                </a>
                                            </td>
                                            <?php endif; ?>
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
    
    <!-- Modal de génération de créneaux -->
    <div class="modal fade" id="generateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Générer les créneaux disponibles</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="generate">
                        <div class="mb-3">
                            <label for="date_debut" class="form-label">Date de début *</label>
                            <input type="date" class="form-control" id="date_debut" name="date_debut" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="date_fin" class="form-label">Date de fin *</label>
                            <input type="date" class="form-control" id="date_fin" name="date_fin" 
                                   value="<?php echo date('Y-m-d', strtotime('+1 month')); ?>" required>
                        </div>
                        <p class="text-muted small">
                            Les créneaux seront générés pour tous les plannings actifs sur cette période.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Générer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal de confirmation de suppression -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmer la suppression</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir supprimer ce planning ?</p>
                </div>
                <div class="modal-footer">
                    <form method="POST" id="deleteForm">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="deleteId">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-danger">Supprimer</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function deletePlanning(id) {
        document.getElementById('deleteId').value = id;
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    }
    </script>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>


