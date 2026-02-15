<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/AuthModel.php';

$auth = new AuthModel();
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ' . url('login.php'));
    exit;
}

$userId = $_GET['user_id'] ?? null;

if ($userId) {
    if ($auth->startImpersonation($userId)) {
        // Rediriger selon le rôle de l'utilisateur impersonné
        if ($auth->isAdmin()) {
            header('Location: ' . url('dashboard.php'));
        } elseif ($auth->isAgent()) {
            header('Location: ' . url('dashboard-agent.php'));
        } else {
            header('Location: ' . url('dashboard-client.php'));
        }
        exit;
    } else {
        $_SESSION['error'] = 'Impossible de se connecter en tant que cet utilisateur';
        header('Location: ' . url('dashboard.php'));
        exit;
    }
} else {
    // Arrêter l'impersonation
    if ($auth->isImpersonating()) {
        $auth->stopImpersonation();
    }
    header('Location: ' . url('dashboard.php'));
    exit;
}


