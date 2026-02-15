<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/AuthModel.php';

$auth = new AuthModel();

if ($auth->isLoggedIn()) {

    if ($auth->isAdmin()) {
        header('Location: ' . url('dashboard.php'));
    } elseif ($auth->isAgent()) {
        header('Location: ' . url('dashboard-agent.php'));
    } else {
        header('Location: ' . url('dashboard-client.php'));
    }

    exit;

} else {
    header('Location: ' . url('login.php'));
    exit;
}
