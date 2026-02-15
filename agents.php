<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/AuthModel.php';
require_once __DIR__ . '/models/AgentModel.php';

$auth = new AuthModel();
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ' . url('login.php'));
    exit;
}

$agentModel = new AgentModel();
$message = '';
$error = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRFToken::verify($_POST['csrf_token'] ?? '')) {
        $error = 'Jeton de sécurité invalide';
    } elseif (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $data = [
                    'email' => $_POST['email'],
                    'mot_de_passe' => $_POST['mot_de_passe'],
                    'nom' => $_POST['nom'],
                    'prenom' => $_POST['prenom'],
                    'telephone' => $_POST['telephone'] ?? '',
                    'specialite' => $_POST['specialite'] ?? ''
                ];
                try {
                    if ($agentModel->createAgent($data, $auth->getUserId())) {
                        $message = 'Agent créé avec succès';
                    } else {
                        $error = 'Erreur lors de la création de l\'agent';
                    }
                } catch (Exception $e) {
                    $error = 'Erreur : ' . $e->getMessage();
                }
                break;
                
            case 'update':
                $id = $_POST['id'];
                $data = [
                    'email' => $_POST['email'],
                    'nom' => $_POST['nom'],
                    'prenom' => $_POST['prenom'],
                    'telephone' => $_POST['telephone'] ?? '',
                    'specialite' => $_POST['specialite'] ?? ''
                ];
                if (!empty($_POST['mot_de_passe'])) {
                    $data['mot_de_passe'] = $_POST['mot_de_passe'];
                }
                try {
                    if ($agentModel->updateAgent($id, $data)) {
                        $message = 'Agent mis à jour avec succès';
                    } else {
                        $error = 'Erreur lors de la mise à jour';
                    }
                } catch (Exception $e) {
                    $error = 'Erreur : ' . $e->getMessage();
                }
                break;
                
            case 'delete':
                $id = $_POST['id'];
                if ($agentModel->deleteAgent($id)) {
                    $message = 'Agent désactivé avec succès';
                } else {
                    $error = 'Erreur lors de la désactivation';
                }
                break;
        }
    }
}

// Récupérer l'action et l'ID
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

$agents = $agentModel->getAllAgents();
$agent = null;
if ($id) {
    $agent = $agentModel->getAgentById($id);
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
        <!-- Formulaire de création/édition -->
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 text-gray-800">
                            <i class="bi bi-<?php echo $action === 'create' ? 'person-plus' : 'pencil'; ?> text-primary me-2"></i>
                            <?php echo $action === 'create' ? 'Nouvel Agent' : 'Modifier Agent'; ?>
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST">
                            <?php echo CSRFToken::field(); ?>
                            <input type="hidden" name="action" value="<?php echo $action; ?>">
                            <?php if ($action === 'edit'): ?>
                                <input type="hidden" name="id" value="<?php echo $agent['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nom" class="form-label">Nom *</label>
                                    <input type="text" class="form-control" id="nom" name="nom" 
                                           value="<?php echo htmlspecialchars($agent['nom'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="prenom" class="form-label">Prénom *</label>
                                    <input type="text" class="form-control" id="prenom" name="prenom" 
                                           value="<?php echo htmlspecialchars($agent['prenom'] ?? ''); ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($agent['email'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="mot_de_passe" class="form-label">
                                    Mot de passe <?php echo $action === 'edit' ? '(laisser vide pour ne pas changer)' : '*'; ?>
                                </label>
                                <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe" 
                                       <?php echo $action === 'create' ? 'required' : ''; ?>>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="telephone" class="form-label">Téléphone</label>
                                    <input type="tel" class="form-control" id="telephone" name="telephone" 
                                           value="<?php echo htmlspecialchars($agent['telephone'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="specialite" class="form-label">Spécialité</label>
                                <input type="text" class="form-control" id="specialite" name="specialite" 
                                       value="<?php echo htmlspecialchars($agent['specialite'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2 justify-content-end">
                            <a href="<?php echo url('agents.php'); ?>" class="btn btn-light border">Annuler</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-2"></i><?php echo $action === 'create' ? 'Créer' : 'Enregistrer'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
<?php else: ?>
    <!-- Liste des agents -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-people-fill text-primary me-2"></i>Gestion des Agents</h1>
        <a href="<?php echo url('agents.php?action=create'); ?>" class="btn btn-primary">
            <i class="bi bi-person-plus me-2"></i>Nouvel Agent
        </a>
    </div>
    
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <?php if (empty($agents)): ?>
                <div class="p-4 text-center text-muted">Aucun agent enregistré</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Nom</th>
                                <th>Email</th>
                                <th>Téléphone</th>
                                <th>Spécialité</th>
                                <th>Statut</th>
                                <th>Dernière connexion</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($agents as $agent): ?>
                                <tr>
                                    <td class="ps-4 fw-medium"><?php echo htmlspecialchars($agent['nom'] . ' ' . $agent['prenom']); ?></td>
                                    <td><?php echo htmlspecialchars($agent['email']); ?></td>
                                    <td><?php echo htmlspecialchars($agent['telephone'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($agent['specialite'] ?? '-'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $agent['actif'] ? 'success' : 'danger'; ?>">
                                            <?php echo $agent['actif'] ? 'Actif' : 'Inactif'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo $agent['derniere_connexion'] ? date('d/m/Y H:i', strtotime($agent['derniere_connexion'])) : 'Jamais'; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?php echo url('agents.php?action=edit&id=' . $agent['id']); ?>" 
                                               class="btn btn-outline-primary" title="Modifier">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="<?php echo url('impersonate.php?user_id=' . $agent['utilisateur_id']); ?>" 
                                               class="btn btn-outline-info" title="Se connecter en tant que">
                                                <i class="bi bi-person-badge"></i>
                                            </a>
                                            <?php if ($agent['actif']): ?>
                                                <button type="button" class="btn btn-outline-danger" 
                                                        onclick="deleteAgent(<?php echo $agent['id']; ?>)" title="Désactiver">
                                                    <i class="bi bi-trash"></i>
                                                </button>
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
    
    <!-- Modal de confirmation de suppression -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmer la désactivation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir désactiver cet agent ?</p>
                </div>
                <div class="modal-footer">
                    <form method="POST" id="deleteForm">
                        <?php echo CSRFToken::field(); ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="deleteId">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-danger">Désactiver</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function deleteAgent(id) {
        document.getElementById('deleteId').value = id;
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    }
    </script>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>


