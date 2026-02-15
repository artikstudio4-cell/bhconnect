<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/AuthModel.php';

$auth = new AuthModel();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>

    <!-- Meta -->
    <meta name="theme-color" content="#007BFF">
    <meta name="description" content="Application de gestion BH CONNECT">

    <!-- Favicon -->
    <?php if (defined('SHOW_LOGO') && SHOW_LOGO && defined('LOGO_PATH')): ?>
        <link rel="icon" type="image/png" href="<?php echo LOGO_PATH; ?>">
    <?php else: ?>
        <link rel="icon" type="image/png" href="<?php echo url('icons/icon-32x32.png'); ?>">
    <?php endif; ?>

    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo url('css/style.css'); ?>">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>

<body>

<nav class="navbar navbar-expand-lg navbar-dark" style="background:#007BFF;">
    <div class="container-fluid">

        <!-- LOGO -->
        <a class="navbar-brand d-flex align-items-center" href="<?php echo url(''); ?>">
            <?php if (defined('SHOW_LOGO') && SHOW_LOGO && defined('LOGO_PATH')): ?>
                <img
                    src="<?php echo LOGO_PATH; ?>"
                    alt="<?php echo defined('LOGO_ALT') ? LOGO_ALT : APP_NAME; ?>"
                    style="height:40px; max-width:150px; object-fit:contain;"
                    class="me-2"
                >
            <?php else: ?>
                <i class="bi bi-building me-2 fs-4"></i>
                <span><?php echo APP_NAME; ?></span>
            <?php endif; ?>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">

                <?php if ($auth->isLoggedIn()): ?>


                    <?php if ($auth->isAdmin()): ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo url('dashboard.php'); ?>"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo url('agents.php'); ?>"><i class="bi bi-people-fill"></i> Agents</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo url('clients.php'); ?>"><i class="bi bi-people"></i> Clients</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo url('dossiers.php'); ?>"><i class="bi bi-folder"></i> Dossiers</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo url('factures.php'); ?>"><i class="bi bi-receipt"></i> Facturation</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo url('rendez-vous.php'); ?>"><i class="bi bi-calendar-event"></i> RDV</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo url('gestion-creneaux.php'); ?>"><i class="bi bi-calendar-check"></i> Gestion des créneaux</a></li>
                    <?php elseif ($auth->isAgent()): ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo url('dashboard-agent.php'); ?>"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo url('clients.php'); ?>"><i class="bi bi-people"></i> Clients</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo url('dossiers.php'); ?>"><i class="bi bi-folder"></i> Dossiers</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo url('creneaux.php'); ?>"><i class="bi bi-calendar-check"></i> Créneaux</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo url('dashboard-client.php'); ?>"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo url('mon-dossier.php'); ?>"><i class="bi bi-folder"></i> Mon dossier</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo url('mes-rendez-vous.php'); ?>"><i class="bi bi-calendar-event"></i> Mes RDV</a></li>
                    <?php endif; ?>

                    <!-- Onglet Notifications visible pour tous les utilisateurs connectés -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('notifications.php'); ?>">
                            <i class="bi bi-bell"></i> Notifications
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link text-warning" href="<?php echo url('logout.php'); ?>">
                            <i class="bi bi-box-arrow-right"></i> Déconnexion
                        </a>
                    </li>

                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('login.php'); ?>">
                            <i class="bi bi-box-arrow-in-right"></i> Connexion
                        </a>
                    </li>
                <?php endif; ?>

            </ul>
        </div>
    </div>
</nav>

<?php if (isset($_SESSION['success'])): ?>
    <div class="container mt-3 fade-in">
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="container mt-3 fade-in">
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<script>
    window.BASE_PATH = '/';
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo url('js/app.js'); ?>"></script>
