<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/AuthModel.php';
require_once __DIR__ . '/models/ClientModel.php';

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

$clientModel = new ClientModel();
$message = '';
$error = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier le token CSRF
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
                    'adresse' => $_POST['adresse'] ?? '',
                    'date_naissance' => $_POST['date_naissance'] ?? null,
                    'nationalite' => $_POST['nationalite'] ?? '',
                    'agent_id' => $agentId // Assigner automatiquement à l'agent si créé par un agent
                ];
                if ($auth->isAgent()) {
                    $data['created_by'] = $auth->getUserId();
                }
                if ($clientModel->createClient($data)) {
                    $message = 'Client créé avec succès' . ($auth->isAgent() ? ' et assigné à votre compte' : '');
                } else {
                    $error = 'Erreur lors de la création du client';
                }
                break;
                
            case 'update':
                $id = $_POST['id'];
                $data = [
                    'nom' => $_POST['nom'],
                    'prenom' => $_POST['prenom'],
                    'telephone' => $_POST['telephone'] ?? '',
                    'adresse' => $_POST['adresse'] ?? '',
                    'date_naissance' => $_POST['date_naissance'] ?? null,
                    'nationalite' => $_POST['nationalite'] ?? ''
                ];
                // Les agents peuvent assigner le client à leur compte ou changer l'assignation
                if ($auth->isAgent() && isset($_POST['assign_to_me']) && $_POST['assign_to_me'] == '1') {
                    $data['agent_id'] = $agentId;
                } elseif ($auth->isAdmin() && isset($_POST['agent_id'])) {
                    $data['agent_id'] = $_POST['agent_id'] ?: null;
                }
                if ($clientModel->updateClient($id, $data)) {
                    $message = 'Client mis à jour avec succès';
                } else {
                    $error = 'Erreur lors de la mise à jour';
                }
                break;
        }
    }
}

// Récupérer les clients selon le rôle
if ($auth->isAdmin()) {
    $clients = $clientModel->getAllClients();
} else {
    // Agent : voir tous les clients (pas seulement assignés) pour pouvoir les assigner
    $clients = $clientModel->getAllClients();
}

$action = $_GET['action'] ?? 'list';
$clientId = $_GET['id'] ?? null;
$client = $clientId ? $clientModel->getClientById($clientId) : null;

// Pour l'assignation des clients (admin)
require_once __DIR__ . '/models/AgentModel.php';
$agentModel = new AgentModel();
$allAgents = $auth->isAdmin() ? $agentModel->getAllAgents() : [];

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
            <div class="col-md-8 offset-md-2">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 text-gray-800">
                            <i class="bi bi-<?php echo $action === 'create' ? 'person-plus' : 'pencil'; ?> text-primary me-2"></i>
                            <?php echo $action === 'create' ? 'Nouveau client' : 'Modifier le client'; ?>
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST">
                            <?php echo CSRFToken::field(); ?>
                            <input type="hidden" name="action" value="<?php echo $action; ?>">
                            <?php if ($action === 'edit'): ?>
                                <input type="hidden" name="id" value="<?php echo $client['id']; ?>">
                            <?php endif; ?>
                            
                            <?php if ($action === 'create'): ?>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="mot_de_passe" class="form-label">Mot de passe *</label>
                                    <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe" required>
                                </div>
                            <?php endif; ?>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nom" class="form-label">Nom *</label>
                                    <input type="text" class="form-control" id="nom" name="nom" 
                                           value="<?php echo htmlspecialchars($client['nom'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="prenom" class="form-label">Prénom *</label>
                                    <input type="text" class="form-control" id="prenom" name="prenom" 
                                           value="<?php echo htmlspecialchars($client['prenom'] ?? ''); ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="telephone" class="form-label">Téléphone</label>
                                <input type="tel" class="form-control" id="telephone" name="telephone" 
                                       value="<?php echo htmlspecialchars($client['telephone'] ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="adresse" class="form-label">Adresse</label>
                                <textarea class="form-control" id="adresse" name="adresse" rows="2"><?php echo htmlspecialchars($client['adresse'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="date_naissance" class="form-label">Date de naissance</label>
                                    <input type="date" class="form-control" id="date_naissance" name="date_naissance" 
                                           value="<?php echo $client['date_naissance'] ?? ''; ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="nationalite" class="form-label">Nationalité</label>
                                    <input type="text" class="form-control" id="nationalite" name="nationalite" 
                                           value="<?php echo htmlspecialchars($client['nationalite'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2 justify-content-end">
                                <a href="<?php echo url('clients.php'); ?>" class="btn btn-light border">Annuler</a>
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
            <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-people text-primary me-2"></i>Gestion des clients</h1>
            <div class="d-flex gap-2">
                <?php if ($auth->isAdmin()): ?>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="bi bi-download me-2"></i>Exporter
                        </button>
                        <ul class="dropdown-menu shadow border-0">
                            <li>
                                <a class="dropdown-item" href="<?php echo url('api/export_clients.php?format=csv'); ?>">
                                    <i class="bi bi-file-earmark-spreadsheet me-2"></i>CSV (Excel)
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo url('api/export_clients.php?format=json'); ?>">
                                    <i class="bi bi-file-earmark-code me-2"></i>JSON
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php endif; ?>
                <a href="<?php echo url('clients.php?action=create'); ?>" class="btn btn-primary">
                    <i class="bi bi-person-plus me-2"></i>Nouveau client
                </a>
            </div>
        </div>
        
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">ID</th>
                                <th>Nom</th>
                                <th>Prénom</th>
                                <th>Email</th>
                                <th>Téléphone</th>
                                <th>Dossiers</th>
                                <th>Date création</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($clients)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">Aucun client enregistré</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($clients as $client): ?>
                                    <tr>
                                        <td class="ps-4 text-muted">#<?php echo $client['id']; ?></td>
                                        <td class="fw-medium"><?php echo htmlspecialchars($client['nom']); ?></td>
                                        <td class="fw-medium"><?php echo htmlspecialchars($client['prenom']); ?></td>
                                        <td><?php echo htmlspecialchars($client['email']); ?></td>
                                        <td><?php echo htmlspecialchars($client['telephone'] ?? '-'); ?></td>
                                        <td><span class="badge bg-secondary"><?php echo $client['nb_dossiers']; ?></span></td>
                                        <td><?php echo date('d/m/Y', strtotime($client['date_creation'])); ?></td>
                                        <td class="text-end pe-4">
                                            <div class="btn-group">
                                                <a href="<?php echo url('dossiers.php?client_id=' . $client['id']); ?>" class="btn btn-sm btn-outline-secondary" title="Dossiers">
                                                    <i class="bi bi-folder"></i>
                                                </a>
                                                <a href="<?php echo url('clients.php?action=edit&id=' . $client['id']); ?>" class="btn btn-sm btn-outline-primary" title="Modifier">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
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


