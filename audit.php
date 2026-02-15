<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/AuthModel.php';
require_once __DIR__ . '/models/AuditModel.php';

$auth = new AuthModel();
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ' . url('login.php'));
    exit;
}

$auditModel = new AuditModel();

// Filtres
$filters = [];
if (!empty($_GET['utilisateur_id'])) {
    $filters['utilisateur_id'] = $_GET['utilisateur_id'];
}
if (!empty($_GET['action'])) {
    $filters['action'] = $_GET['action'];
}
if (!empty($_GET['table_affectee'])) {
    $filters['table_affectee'] = $_GET['table_affectee'];
}
if (!empty($_GET['date_debut'])) {
    $filters['date_debut'] = $_GET['date_debut'];
}
if (!empty($_GET['date_fin'])) {
    $filters['date_fin'] = $_GET['date_fin'];
}

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

$logs = $auditModel->getLogs($filters, $limit, $offset);
$stats = $auditModel->getStats($filters['date_debut'] ?? null, $filters['date_fin'] ?? null);
$topActions = $auditModel->getTopActions(10);
$topUsers = $auditModel->getTopUsers(10);

// Récupérer la liste des utilisateurs pour le filtre
$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT id, email, role FROM utilisateurs ORDER BY email");
$users = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h1><i class="bi bi-file-text"></i> Logs d'Audit</h1>
        <p class="text-muted">Historique complet des actions dans le système</p>
    </div>
</div>

<!-- Statistiques -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="display-4 text-primary"><?php echo $stats['total_actions']; ?></div>
                <p class="text-muted mb-0">Total actions</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="display-4 text-info"><?php echo $stats['nb_utilisateurs']; ?></div>
                <p class="text-muted mb-0">Utilisateurs actifs</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="display-4 text-success"><?php echo $stats['nb_actions_differentes']; ?></div>
                <p class="text-muted mb-0">Types d'actions</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="display-4 text-warning"><?php echo $stats['nb_tables_affectees']; ?></div>
                <p class="text-muted mb-0">Tables affectées</p>
            </div>
        </div>
    </div>
</div>

<!-- Filtres -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-funnel"></i> Filtres</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="utilisateur_id" class="form-label">Utilisateur</label>
                        <select class="form-select" id="utilisateur_id" name="utilisateur_id">
                            <option value="">Tous</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>" 
                                        <?php echo ($filters['utilisateur_id'] ?? '') == $user['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['email']); ?> (<?php echo $user['role']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="action" class="form-label">Action</label>
                        <input type="text" class="form-control" id="action" name="action" 
                               value="<?php echo htmlspecialchars($filters['action'] ?? ''); ?>" 
                               placeholder="Ex: create, update, delete">
                    </div>
                    <div class="col-md-2">
                        <label for="table_affectee" class="form-label">Table</label>
                        <input type="text" class="form-control" id="table_affectee" name="table_affectee" 
                               value="<?php echo htmlspecialchars($filters['table_affectee'] ?? ''); ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="date_debut" class="form-label">Date début</label>
                        <input type="date" class="form-control" id="date_debut" name="date_debut" 
                               value="<?php echo htmlspecialchars($filters['date_debut'] ?? ''); ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="date_fin" class="form-label">Date fin</label>
                        <input type="date" class="form-control" id="date_fin" name="date_fin" 
                               value="<?php echo htmlspecialchars($filters['date_fin'] ?? ''); ?>">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Filtrer
                        </button>
                        <a href="<?php echo url('audit.php'); ?>" class="btn btn-secondary">
                            <i class="bi bi-x"></i> Réinitialiser
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Liste des logs -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Historique des actions</h5>
            </div>
            <div class="card-body">
                <?php if (empty($logs)): ?>
                    <p class="text-muted">Aucun log trouvé</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date/Heure</th>
                                    <th>Utilisateur</th>
                                    <th>Action</th>
                                    <th>Table</th>
                                    <th>ID Enregistrement</th>
                                    <th>IP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i:s', strtotime($log['date_action'])); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($log['utilisateur_email']); ?>
                                            <br><small class="text-muted"><?php echo $log['utilisateur_role']; ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo in_array($log['action'], ['create', 'login']) ? 'success' : 
                                                    (in_array($log['action'], ['update']) ? 'warning' : 'danger'); 
                                            ?>">
                                                <?php echo htmlspecialchars($log['action']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($log['table_affectee']); ?></td>
                                        <td><?php echo $log['enregistrement_id'] ?? '-'; ?></td>
                                        <td><small><?php echo htmlspecialchars($log['ip_address'] ?? '-'); ?></small></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                            <small class="text-muted">
                                Page <?php echo $page; ?> - <?php echo count($logs); ?> résultat(s)
                            </small>
                        </div>
                        <div>
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?><?php echo !empty($filters) ? '&' . http_build_query($filters) : ''; ?>" 
                                   class="btn btn-sm btn-outline-primary">Précédent</a>
                            <?php endif; ?>
                            <?php if (count($logs) == $limit): ?>
                                <a href="?page=<?php echo $page + 1; ?><?php echo !empty($filters) ? '&' . http_build_query($filters) : ''; ?>" 
                                   class="btn btn-sm btn-outline-primary">Suivant</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>


