<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/AuthModel.php';
require_once __DIR__ . '/models/MessageModel.php';
require_once __DIR__ . '/models/ClientModel.php';
require_once __DIR__ . '/models/AgentModel.php';

$auth = new AuthModel();
if (!$auth->isLoggedIn()) {
    header('Location: ' . url('login.php'));
    exit;
}

$messageModel = new MessageModel();
$clientModel = new ClientModel();
$agentModel = new AgentModel();

$message = '';
$error = '';

// Traitement de l'envoi de message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send') {
    if (!CSRFToken::verify($_POST['csrf_token'] ?? '')) {
        $error = 'Jeton de sécurité invalide';
    } else {
    $destinataireId = $_POST['destinataire_id'] ?? null;
    
    // Si c'est un client, le destinataire est NULL (Cabinet) par défaut
    if (!$auth->isAgent() && !$auth->isAdmin()) {
        $destinataireId = null; 
    }
    
    $sujet = $_POST['sujet'] ?? '';
    $contenu = $_POST['contenu'] ?? '';
    
    // Pour un agent/admin, destinataireId est requis. Pour un client, c'est NULL donc on valide autrement.
    $isValid = ($destinataireId || (!$auth->isAgent() && !$auth->isAdmin())) && $contenu;

    if ($isValid) {
        try {
            $piecesJointes = [];
            // Gérer l'upload de pièces jointes si nécessaire
            if (isset($_FILES['pieces_jointes']) && !empty($_FILES['pieces_jointes']['name'][0])) {
                foreach ($_FILES['pieces_jointes']['name'] as $key => $name) {
                    if ($_FILES['pieces_jointes']['error'][$key] === UPLOAD_ERR_OK) {
                        $tmpName = $_FILES['pieces_jointes']['tmp_name'][$key];
                        $size = $_FILES['pieces_jointes']['size'][$key];
                        $extension = pathinfo($name, PATHINFO_EXTENSION);
                        
                        if (in_array(strtolower($extension), ALLOWED_EXTENSIONS) && $size <= MAX_FILE_SIZE) {
                            $newName = uniqid() . '_' . $name;
                            $chemin = UPLOAD_DIR . $newName;
                            
                            if (move_uploaded_file($tmpName, $chemin)) {
                                $piecesJointes[] = [
                                    'nom_fichier' => $name,
                                    'chemin_fichier' => $chemin,
                                    'taille_fichier' => $size
                                ];
                            }
                        }
                    }
                }
            }
            
            $messageModel->sendMessage(
                $auth->getUserId(),
                $destinataireId,
                $sujet,
                $contenu,
                $piecesJointes
            );
            
            $message = 'Message envoyé avec succès';
        } catch (Exception $e) {
            $error = 'Erreur : ' . $e->getMessage();
        }
    } else {
        $error = 'Veuillez remplir tous les champs obligatoires';
    }
  }
}

// Marquer un message comme lu
if (isset($_GET['mark_read'])) {
    $messageModel->markAsRead($_GET['mark_read'], $auth->getUserId());
    header('Location: ' . url('messages.php'));
    exit;
}

// Récupérer les messages
$action = $_GET['action'] ?? 'received';
$messageId = $_GET['id'] ?? null;
$viewMessage = null;

if ($messageId && $action === 'view') {
    $viewMessage = $messageModel->getMessageById($messageId, $auth->getUserId());
    if ($viewMessage) {
        // Marquer comme lu si c'est un message reçu
        // Si destinataire_id est NULL (pour les agents), on considère qu'ils peuvent le lire
        if (($viewMessage['destinataire_id'] == $auth->getUserId() || ($viewMessage['destinataire_id'] === null && ($auth->isAgent() || $auth->isAdmin()))) && !$viewMessage['lu']) {
            $messageModel->markAsRead($messageId, $auth->getUserId());
        }
        // Récupérer les pièces jointes
        $piecesJointes = $messageModel->getPiecesJointes($messageId);
    }
} else {
    // Passer le rôle de l'utilisateur pour récupérer les messages broadcast si agent/admin
    $userRole = 'client';
    if ($auth->isAgent()) $userRole = 'agent';
    elseif ($auth->isAdmin()) $userRole = 'admin';
    
    $messages = $messageModel->getMessages($auth->getUserId(), $action, $userRole);
}

$unreadCount = $messageModel->getUnreadCount($auth->getUserId());

// Récupérer les destinataires possibles (seulement pour agents/admin)
$destinataires = [];
if ($auth->isAgent() || $auth->isAdmin()) {
    $destinataires = $clientModel->getAllClients(); 
    // On pourrait ajouter les autres agents aussi
}
$destinataires = [];
if ($auth->isClient()) {
    $client = $clientModel->getClientByUserId($auth->getUserId(), true);
    if ($client && isset($client['agent_id']) && $client['agent_id']) {
        $agent = $agentModel->getAgentById($client['agent_id']);
        if ($agent) {
            $destinataires[] = [
                'id' => $agent['utilisateur_id'],
                'nom' => $agent['nom'] . ' ' . $agent['prenom'],
                'role' => 'agent'
            ];
        }
    }
} elseif ($auth->isAgent()) {
    $agent = $agentModel->getAgentByUserId($auth->getUserId());
    $clients = $agentModel->getClientsByAgent($agent['id']);
    foreach ($clients as $client) {
        $destinataires[] = [
            'id' => $client['utilisateur_id'],
            'nom' => $client['nom'] . ' ' . $client['prenom'],
            'role' => 'client'
        ];
    }
    // Ajouter l'admin
    $stmt = Database::getInstance()->getConnection()->query("
        SELECT id, email FROM utilisateurs WHERE role = 'admin' LIMIT 1
    ");
    $admin = $stmt->fetch();
    if ($admin) {
        $destinataires[] = [
            'id' => $admin['id'],
            'nom' => 'Administrateur',
            'role' => 'admin'
        ];
    }
} elseif ($auth->isAdmin()) {
    // Admin peut envoyer à tous
    $agents = $agentModel->getAllAgents();
    foreach ($agents as $agent) {
        $destinataires[] = [
            'id' => $agent['utilisateur_id'],
            'nom' => $agent['nom'] . ' ' . $agent['prenom'],
            'role' => 'agent'
        ];
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row mb-5">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 fw-bold mb-2"><i class="bi bi-envelope me-2 text-primary"></i> Messagerie</h1>
                    <p class="text-muted mb-0">Vos échanges sécurisés avec le cabinet</p>
                </div>
                <?php if ($unreadCount > 0): ?>
                    <span class="badge bg-danger rounded-pill px-3 py-2 shadow-sm">
                        <?php echo $unreadCount; ?> non lu(s)
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4">
        <i class="bi bi-check-circle me-2"></i> <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4">
        <i class="bi bi-exclamation-triangle me-2"></i> <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($viewMessage): ?>
    <!-- Vue d'un message -->
    <div class="row g-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                <div class="card-header bg-light py-3 px-4 border-bottom">
                    <h5 class="mb-0 text-gray-800"><i class="bi bi-grid me-2"></i>Menu</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="<?php echo url('messages.php?action=received'); ?>" class="list-group-item list-group-item-action border-0 px-4 py-3">
                        <i class="bi bi-arrow-left me-2"></i> Retour
                    </a>
                    <a href="<?php echo url('messages.php?action=received'); ?>" 
                       class="list-group-item list-group-item-action border-0 px-4 py-3">
                        <i class="bi bi-inbox me-2"></i> Reçus
                    </a>
                    <a href="<?php echo url('messages.php?action=sent'); ?>" 
                       class="list-group-item list-group-item-action border-0 px-4 py-3">
                        <i class="bi bi-send me-2"></i> Envoyés
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">
                        <?php echo htmlspecialchars($viewMessage['sujet'] ?: 'Sans objet'); ?>
                    </h5>
                    <a href="<?php echo url('messages.php?action=received'); ?>" class="btn btn-sm btn-outline-secondary rounded-pill">
                        <i class="bi bi-x"></i> Fermer
                    </a>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between mb-4 pb-3 border-bottom">
                        <div>
                            <div class="mb-1"><strong>De :</strong> <?php echo htmlspecialchars($viewMessage['expediteur_email']); ?></div>
                            <div class="mb-0"><strong>À :</strong> <?php echo htmlspecialchars($viewMessage['destinataire_email']); ?></div>
                        </div>
                        <div class="text-muted small">
                            <i class="bi bi-clock me-1"></i> <?php echo date('d/m/Y à H:i', strtotime($viewMessage['date_envoi'])); ?>
                        </div>
                    </div>
                    
                    <div class="mb-4 p-4 bg-light rounded-3">
                        <?php echo nl2br(htmlspecialchars($viewMessage['contenu'])); ?>
                    </div>
                    
                    <?php if (!empty($piecesJointes)): ?>
                        <div class="mb-4">
                            <h6 class="fw-bold mb-3"><i class="bi bi-paperclip me-2"></i> Pièces jointes</h6>
                            <div class="list-group">
                                <?php foreach ($piecesJointes as $pj): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center border-0 bg-light rounded-3 mb-2">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-file-earmark-text fs-4 text-primary me-3"></i>
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($pj['nom_fichier']); ?></div>
                                                <small class="text-muted"><?php echo number_format($pj['taille_fichier'] / 1024, 2); ?> Ko</small>
                                            </div>
                                        </div>
                                        <a href="<?php echo url('uploads/' . basename($pj['chemin_fichier'])); ?>" 
                                           target="_blank" class="btn btn-sm btn-outline-primary rounded-pill">
                                            <i class="bi bi-download me-2"></i> Télécharger
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-4 pt-3 border-top">
                        <a href="<?php echo url('messages.php?action=send&reply_to=' . $viewMessage['id']); ?>" 
                           class="btn btn-primary rounded-pill px-4">
                            <i class="bi bi-reply me-2"></i> Répondre
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- Liste des messages -->
    <div class="row g-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0 rounded-4 overflow-hidden h-100">
                <div class="card-header bg-light py-3 px-4 border-bottom">
                    <h5 class="mb-0 text-gray-800"><i class="bi bi-grid me-2"></i>Menu</h5>
                </div>
                <div class="list-group list-group-flush flex-grow-1">
                    <a href="<?php echo url('messages.php?action=received'); ?>" 
                       class="list-group-item list-group-item-action border-0 px-4 py-3 <?php echo $action === 'received' ? 'bg-primary-subtle text-primary fw-bold' : ''; ?>">
                        <div class="d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-inbox me-2"></i> Reçus</span>
                            <?php if ($action === 'received' && $unreadCount > 0): ?>
                                <span class="badge bg-danger rounded-pill"><?php echo $unreadCount; ?></span>
                            <?php endif; ?>
                        </div>
                    </a>
                    <a href="<?php echo url('messages.php?action=sent'); ?>" 
                       class="list-group-item list-group-item-action border-0 px-4 py-3 <?php echo $action === 'sent' ? 'bg-primary-subtle text-primary fw-bold' : ''; ?>">
                        <i class="bi bi-send me-2"></i> Envoyés
                    </a>
                    <div class="p-3 mt-auto">
                        <button type="button" class="btn btn-primary w-100 rounded-pill shadow-sm" 
                                data-bs-toggle="modal" data-bs-target="#newMessageModal">
                            <i class="bi bi-pencil-square me-2"></i> Nouveau message
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="card shadow-sm border-0 rounded-4 h-100">
                <div class="card-header bg-white py-3 px-4 border-bottom">
                    <h5 class="mb-0 fw-bold text-gray-800">
                        <?php echo $action === 'received' ? '<i class="bi bi-inbox me-2"></i>Messages reçus' : '<i class="bi bi-send me-2"></i>Messages envoyés'; ?>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($messages)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-inbox text-muted mb-3" style="font-size: 3rem;"></i>
                            <h5 class="text-muted">Aucun message</h5>
                            <p class="text-muted small">Votre boîte de réception est vide</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($messages as $msg): ?>
                                <a href="<?php echo url('messages.php?action=view&id=' . $msg['id']); ?>" 
                                   class="list-group-item list-group-item-action p-4 border-bottom <?php echo !$msg['lu'] && $action === 'received' ? 'bg-warning-subtle' : ''; ?>">
                                    <div class="d-flex w-100 justify-content-between align-items-center">
                                        <div class="d-flex align-items-center flex-grow-1 overflow-hidden">
                                            <div class="rounded-circle p-3 me-3 <?php echo !$msg['lu'] && $action === 'received' ? 'bg-warning text-dark' : 'bg-light text-secondary'; ?>">
                                                <i class="bi bi-envelope<?php echo !$msg['lu'] && $action === 'received' ? '-exclamation' : ''; ?> fs-4"></i>
                                            </div>
                                            <div class="flex-grow-1 overflow-hidden" style="min-width: 0;">
                                                <div class="d-flex align-items-center mb-1">
                                                    <h6 class="mb-0 text-truncate me-2 fw-bold">
                                                        <?php if ($action === 'received'): ?>
                                                            <?php echo htmlspecialchars($msg['expediteur_nom'] ?? $msg['expediteur_email']); ?>
                                                        <?php else: ?>
                                                            À : <?php echo htmlspecialchars($msg['destinataire_nom'] ?? $msg['destinataire_email']); ?>
                                                        <?php endif; ?>
                                                    </h6>
                                                    <?php if (!$msg['lu'] && $action === 'received'): ?>
                                                        <span class="badge bg-danger rounded-pill" style="font-size: 0.6rem;">NOUVEAU</span>
                                                    <?php endif; ?>
                                                </div>
                                                <p class="mb-0 fw-medium text-dark text-truncate">
                                                    <?php echo htmlspecialchars($msg['sujet'] ?: 'Sans objet'); ?>
                                                </p>
                                                <small class="text-muted text-truncate d-block">
                                                    <?php echo htmlspecialchars(substr($msg['contenu'], 0, 100)); ?>...
                                                </small>
                                            </div>
                                        </div>
                                        <div class="text-end ps-3" style="min-width: 120px;">
                                            <small class="text-muted d-block mb-1">
                                                <?php echo date('d/m/Y', strtotime($msg['date_envoi'])); ?>
                                            </small>
                                            <small class="text-muted">
                                                <?php echo date('H:i', strtotime($msg['date_envoi'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
</div>

<!-- Modal nouveau message -->
<div class="modal fade" id="newMessageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nouveau message</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <?php echo CSRFToken::field(); ?>
                <div class="modal-body">
                    <input type="hidden" name="action" value="send">
                    
                    <div class="mb-3">
                    <div class="mb-3">
                        <label for="destinataire_id" class="form-label">Destinataire *</label>
                        <?php if ($auth->isAgent() || $auth->isAdmin()): ?>
                            <select class="form-select" id="destinataire_id" name="destinataire_id" required>
                                <option value="">Sélectionner un destinataire</option>
                                <?php foreach ($destinataires as $dest): ?>
                                    <option value="<?php echo $dest['id']; ?>">
                                        <?php echo htmlspecialchars($dest['nom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <input type="text" class="form-control" value="Cabinet (Tous les agents)" disabled readonly>
                            <small class="text-muted">Votre message sera visible par toute l'équipe.</small>
                        <?php endif; ?>
                    </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="sujet" class="form-label">Sujet</label>
                        <input type="text" class="form-control" id="sujet" name="sujet">
                    </div>
                    
                    <div class="mb-3">
                        <label for="contenu" class="form-label">Message *</label>
                        <textarea class="form-control" id="contenu" name="contenu" rows="5" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="pieces_jointes" class="form-label">Pièces jointes (optionnel)</label>
                        <input type="file" class="form-control" id="pieces_jointes" name="pieces_jointes[]" multiple>
                        <small class="text-muted">Formats autorisés : PDF, JPG, PNG (max 5 Mo par fichier)</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Envoyer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

