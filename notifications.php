<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/AuthModel.php';
require_once __DIR__ . '/models/NotificationModel.php';

$auth = new AuthModel();
if (!$auth->isLoggedIn()) {
    header('Location: ' . url('login.php'));
    exit;
}

$notificationModel = new NotificationModel();

// Marquer comme lu
if (isset($_GET['mark_read'])) {
    $notificationModel->markAsRead($_GET['mark_read'], $auth->getUserId());
    header('Location: ' . url('notifications.php'));
    exit;
}

// Marquer tout comme lu
if (isset($_GET['action']) && $_GET['action'] === 'mark_all_read') {
    $notificationModel->markAllAsRead($auth->getUserId());
    header('Location: ' . url('notifications.php'));
    exit;
}

// Supprimer une notification
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $notificationModel->delete($_GET['id'], $auth->getUserId());
    header('Location: ' . url('notifications.php'));
    exit;
}

$notifications = $notificationModel->getNotifications($auth->getUserId());
$unreadCount = $notificationModel->getUnreadCount($auth->getUserId());

include __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row mb-5">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 fw-bold mb-2"><i class="bi bi-bell me-2 text-primary"></i> Notifications</h1>
                    <p class="text-muted mb-0">Restez informé des mises à jour de votre dossier</p>
                </div>
                <?php if ($unreadCount > 0): ?>
                    <div class="d-flex gap-2">
                        <a href="<?php echo url('notifications.php?action=mark_all_read'); ?>" class="btn btn-outline-primary rounded-pill">
                            <i class="bi bi-check-all me-2"></i> Tout marquer comme lu
                        </a>
                        <span class="badge bg-danger rounded-pill d-flex align-items-center px-3">
                            <?php echo $unreadCount; ?> non lu(s)
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white py-3 px-4">
                    <h5 class="mb-0 text-primary"><i class="bi bi-list-ul me-2"></i>Mes notifications</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($notifications)): ?>
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="bi bi-bell-slash text-muted" style="font-size: 3rem;"></i>
                            </div>
                            <h5 class="text-muted">Aucune notification</h5>
                            <p class="text-muted small">Vous êtes à jour !</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($notifications as $notif): ?>
                                <div class="list-group-item p-4 border-bottom <?php echo !$notif['lu'] ? 'bg-light' : ''; ?>">
                                    <div class="d-flex w-100 justify-content-between align-items-start">
                                        <div class="d-flex align-items-start gap-3 flex-grow-1">
                                            <div class="rounded-circle p-2 <?php 
                                                if ($notif['type'] === 'rdv_confirme' || $notif['type'] === 'document_valide') echo 'bg-success-subtle text-success';
                                                elseif ($notif['type'] === 'rdv_refuse' || $notif['type'] === 'document_rejete') echo 'bg-danger-subtle text-danger';
                                                else echo 'bg-primary-subtle text-primary';
                                            ?>">
                                                <?php
                                                $icon = 'bi-info-circle';
                                                if ($notif['type'] === 'rdv_confirme') $icon = 'bi-calendar-check';
                                                elseif ($notif['type'] === 'rdv_refuse') $icon = 'bi-calendar-x';
                                                elseif ($notif['type'] === 'document_valide') $icon = 'bi-file-check';
                                                elseif ($notif['type'] === 'message') $icon = 'bi-envelope';
                                                ?>
                                                <i class="bi <?php echo $icon; ?> fs-5"></i>
                                            </div>
                                            
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center mb-1">
                                                    <h6 class="mb-0 fw-bold <?php echo !$notif['lu'] ? 'text-dark' : 'text-secondary'; ?>">
                                                        <?php echo htmlspecialchars($notif['titre']); ?>
                                                    </h6>
                                                    <?php if (!$notif['lu']): ?>
                                                        <span class="badge bg-danger ms-2" style="font-size: 0.6em;">NOUVEAU</span>
                                                    <?php endif; ?>
                                                </div>
                                                <p class="mb-2 text-muted"><?php echo htmlspecialchars($notif['message']); ?></p>
                                                <small class="text-muted text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                                    <i class="bi bi-clock me-1"></i> <?php echo date('d/m/Y à H:i', strtotime($notif['date_creation'])); ?>
                                                </small>
                                            </div>
                                        </div>

                                        <div class="d-flex flex-column gap-2 ms-3">
                                            <?php if (!$notif['lu']): ?>
                                                <a href="<?php echo url('notifications.php?mark_read=' . $notif['id']); ?>" 
                                                   class="btn btn-sm btn-outline-primary rounded-pill" title="Marquer comme lu">
                                                    <i class="bi bi-check-lg"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($notif['lien']): ?>
                                                <a href="<?php echo htmlspecialchars($notif['lien']); ?>" 
                                                   class="btn btn-sm btn-primary rounded-pill shadow-sm">
                                                    <i class="bi bi-arrow-right"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="<?php echo url('notifications.php?action=delete&id=' . $notif['id']); ?>" 
                                               class="btn btn-sm btn-outline-danger rounded-pill" title="Supprimer"
                                               onclick="return confirm('Supprimer cette notification ?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>


