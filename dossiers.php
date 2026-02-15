<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/AuthModel.php';
require_once __DIR__ . '/models/DossierModel.php';
require_once __DIR__ . '/models/ClientModel.php';
require_once __DIR__ . '/models/DocumentModel.php';

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

$dossierModel = new DossierModel();
$clientModel = new ClientModel();
$documentModel = new DocumentModel();
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
                    'client_id' => $_POST['client_id'],
                    'type_dossier' => $_POST['type_dossier'],
                    'created_by' => $auth->getUserId(),
                    'agent_id' => $agentId // Assigner automatiquement à l'agent si créé par un agent
                ];
                if ($dossierModel->createDossier($data)) {
                    $message = 'Dossier créé avec succès' . ($auth->isAgent() ? ' et assigné à votre compte' : '');
                } else {
                    $error = 'Erreur lors de la création du dossier';
                }
                break;
                
            case 'update_statut':
                $dossierId = $_POST['dossier_id'];
                $nouveauStatut = $_POST['statut'];
                $progression = $_POST['progression'] ?? null;
                $commentaire = $_POST['commentaire'] ?? '';
                
                if (empty($commentaire)) {
                    $error = 'Un commentaire est obligatoire lors de la mise à jour du statut';
                } else {
                    if ($dossierModel->updateStatut($dossierId, $nouveauStatut, $commentaire, $auth->getUserId(), $progression)) {
                        $message = 'Statut mis à jour avec succès';
                    } else {
                        $error = 'Erreur lors de la mise à jour du statut';
                    }
                }
                break;
        }
    }
}

$action = $_GET['action'] ?? 'list';
$dossierId = $_GET['id'] ?? null;
$clientId = $_GET['client_id'] ?? null;

// Récupérer les dossiers selon le rôle
if ($auth->isAdmin()) {
    $dossiers = $dossierModel->getDossiers($clientId);
} else {
    // Agent : voir tous les dossiers (pas seulement assignés) pour pouvoir les suivre
    $dossiers = $dossierModel->getDossiers($clientId);
}

$dossier = $dossierId ? $dossierModel->getDossierById($dossierId) : null;
$historique = $dossierId ? $dossierModel->getHistorique($dossierId) : [];
$documents = $dossierId ? $documentModel->getDocumentsByDossier($dossierId) : [];

// Récupérer les clients selon le rôle
if ($auth->isAdmin()) {
    $clients = $clientModel->getAllClients();
} else {
    // Agent : voir tous les clients pour créer des dossiers
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
            <div class="col-md-8 offset-md-2">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 text-gray-800">
                            <i class="bi bi-folder-plus text-primary me-2"></i>
                            <?php echo $action === 'create' ? 'Nouveau Dossier' : 'Modifier le Dossier'; ?>
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST">
                            <?php echo CSRFToken::field(); ?>
                            <input type="hidden" name="action" value="create">
                            
                            <div class="mb-3">
                                <label for="client_id" class="form-label">Client *</label>
                                <select class="form-select" id="client_id" name="client_id" required>
                                    <option value="">Sélectionner un client</option>
                                    <?php foreach ($clients as $c): ?>
                                        <option value="<?php echo $c['id']; ?>" <?php echo ($client_id == $c['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($c['nom'] . ' ' . $c['prenom']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="type_procedure" class="form-label">Type de procédure *</label>
                                <select class="form-select" id="type_procedure" name="type_procedure" required>
                                    <option value="">Sélectionner le type</option>
                                    <option value="Visa Étudiant">Visa Étudiant</option>
                                    <option value="Visa Touriste">Visa Touriste</option>
                                    <option value="Regroupement Familial">Regroupement Familial</option>
                                    <option value="Travail">Travail</option>
                                    <option value="Autre">Autre</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="destination_id" class="form-label">Destination *</label>
                                <select class="form-select" id="destination_id" name="destination_id" required>
                                    <option value="">Sélectionner la destination</option>
                                    <?php foreach ($destinations as $dest): ?>
                                        <option value="<?php echo $dest['id']; ?>">
                                            <?php echo htmlspecialchars($dest['pays']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="d-flex gap-2 justify-content-end">
                                <a href="<?php echo url('dossiers.php'); ?>" class="btn btn-light border">Annuler</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-lg me-2"></i>Créer le dossier
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    <?php elseif ($action === 'view' && $dossier): ?>
        <!-- Vue détaillée du dossier (Structure existante préservée mais encapsulée) -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="bi bi-folder-fill text-primary me-2"></i>Dossier <?php echo htmlspecialchars($dossier['numero_dossier']); ?>
            </h1>
            <a href="<?php echo url('dossiers.php'); ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Retour
            </a>
        </div>

        <div class="row">
            <!-- Informations Client -->
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 text-gray-800"><i class="bi bi-person me-2"></i>Information Client</h5>
                    </div>
                    <div class="card-body">
                        <!-- ... contenu client ... -->
                        <p><strong>Nom:</strong> <?php echo htmlspecialchars(($dossier['client_nom'] ?? '') . ' ' . ($dossier['client_prenom'] ?? '')); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($dossier['client_email'] ?? ''); ?></p>
                        <p><strong>Téléphone:</strong> <?php echo htmlspecialchars($dossier['client_telephone'] ?? 'N/A'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Informations Dossier -->
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 text-gray-800"><i class="bi bi-info-circle me-2"></i>Détails Procédure</h5>
                    </div>
                    <div class="card-body">
                         <!-- ... contenu dossier ... -->
                         <p><strong>Type:</strong> <?php echo htmlspecialchars($dossier['type_procedure'] ?? 'Non défini'); ?></p>
                         <p><strong>Destination:</strong> <?php echo htmlspecialchars($dossier['destination_pays'] ?? 'Non définie'); ?></p>
                         <p><strong>Statut:</strong> 
                            <span class="badge bg-<?php echo Constants::getDossierStatusColor($dossier['statut'] ?? Constants::DOSSIER_NOUVEAU); ?>">
                                <?php echo htmlspecialchars($dossier['statut'] ?? Constants::DOSSIER_NOUVEAU); ?>
                            </span>
                         </p>
                         <p><strong>Progression:</strong> <?php echo $dossier['progression'] ?? 0; ?>%</p>
                         <div class="progress mb-3">
                             <div class="progress-bar" style="width: <?php echo $dossier['progression'] ?? 0; ?>%"></div>
                         </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white py-3">
                         <h5 class="mb-0 text-gray-800"><i class="bi bi-gear me-2"></i>Actions</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <?php echo CSRFToken::field(); ?>
                            <input type="hidden" name="action" value="update_statut">
                            <input type="hidden" name="dossier_id" value="<?php echo $dossier['id']; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Changer le statut</label>
                                <select name="statut" class="form-select mb-2">
                                    <?php
                                    foreach (Constants::getDossierStatuses() as $s) {
                                        $selected = ($dossier['statut'] === $s) ? 'selected' : '';
                                        echo "<option value=\"$s\" $selected>$s</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Progression (%)</label>
                                <input type="number" name="progression" class="form-control mb-2" 
                                       value="<?php echo $dossier['progression'] ?? 0; ?>" min="0" max="100">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Commentaire (obligatoire pour l'historique)</label>
                                <textarea name="commentaire" class="form-control" rows="2" required></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Mettre à jour</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Documents et Rendez-vous -->
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-gray-800"><i class="bi bi-file-earmark-text me-2"></i>Documents</h5>
                        <a href="<?php echo url('documents.php?dossier_id=' . $dossier['id']); ?>" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus-lg me-1"></i>Gérer les documents
                        </a>
                    </div>
                    <div class="card-body p-0">
                         <!-- Liste simplifiée des documents ou lien vers documents.php -->
                         <div class="p-4 text-center text-muted">
                             <a href="<?php echo url('documents.php?dossier_id=' . $dossier['id']); ?>">Voir les documents associés</a>
                         </div>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Liste des dossiers -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-folder text-primary me-2"></i>Gestion des Dossiers</h1>
            <a href="<?php echo url('dossiers.php?action=create'); ?>" class="btn btn-primary">
                <i class="bi bi-folder-plus me-2"></i>Nouveau Dossier
            </a>
        </div>
        
        <!-- Filtres -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-3">
                 <form method="GET" class="row g-3 align-items-end">
                     <div class="col-md-3">
                         <label class="form-label">Statut</label>
                         <select name="statut" class="form-select">
                             <option value="">Tous</option>
                             <!-- Options statut... -->
                             <?php foreach ($statuts as $s): ?>
                                 <option value="<?php echo $s; ?>" <?php echo $filterStatut === $s ? 'selected' : ''; ?>><?php echo $s; ?></option>
                             <?php endforeach; ?>
                         </select>
                     </div>
                     <?php if ($auth->isAdmin()): ?>
                     <div class="col-md-3">
                          <label class="form-label">Agent</label>
                          <!-- Select Agent -->
                          <select name="agent_id" class="form-select">
                              <option value="">Tous</option>
                              <?php foreach ($agents as $agent): ?>
                                  <option value="<?php echo $agent['id']; ?>" <?php echo $filterAgent == $agent['id'] ? 'selected' : ''; ?>>
                                      <?php echo htmlspecialchars($agent['nom'] . ' ' . $agent['prenom']); ?>
                                  </option>
                              <?php endforeach; ?>
                          </select>
                     </div>
                     <?php endif; ?>
                     <div class="col-md-2">
                         <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                     </div>
                 </form>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Numéro</th>
                                <th>Client</th>
                                <th>Type</th>
                                <th>Statut</th>
                                <th>Progression</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($dossiers)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">Aucun dossier trouvé</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($dossiers as $dossier): ?>
                                    <tr>
                                        <td class="ps-4 fw-medium"><?php echo htmlspecialchars($dossier['numero_dossier']); ?></td>
                                        <td><?php echo htmlspecialchars(($dossier['nom'] ?? '') . ' ' . ($dossier['prenom'] ?? '')); ?></td>
                                        <td><?php echo htmlspecialchars($dossier['type_dossier'] ?? ''); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo Constants::getDossierStatusColor($dossier['statut']); ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $dossier['statut'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="progress" style="height: 5px;">
                                                <div class="progress-bar" role="progressbar" style="width: <?php echo $dossier['progression'] ?? 0; ?>%"></div>
                                            </div>
                                            <small class="text-muted"><?php echo $dossier['progression'] ?? 0; ?>%</small>
                                        </td>
                                        <td class="text-end pe-4">
                                            <a href="<?php echo url('dossiers.php?action=view&id=' . $dossier['id']); ?>" class="btn btn-sm btn-outline-primary" title="Voir">
                                                <i class="bi bi-eye"></i>
                                            </a>
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

<?php include __DIR__ . '/includes/footer.php'; ?>

