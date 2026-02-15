<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/AuthModel.php';

// Démarrer la session explicitement
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$auth  = new AuthModel();
$error = '';
$rateLimiter = new RateLimiter(Database::getInstance()->getConnection());

// Déjà connecté → redirection
if ($auth->isLoggedIn()) {
    switch ($_SESSION['role']) {
        case 'admin':
            header('Location: dashboard.php');
            break;
        case 'agent':
            header('Location: dashboard-agent.php');
            break;
        default:
            header('Location: dashboard-client.php');
    }
    exit;
}

// Soumission formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Récupérer le token depuis POST
    $tokenFromPost = $_POST['csrf_token'] ?? '';
    
    // Log pour debug (à réduire en production)
    if (defined('APP_DEBUG') && APP_DEBUG) {
        error_log("LOGIN CSRF DEBUG: Token POST=" . substr($tokenFromPost, 0, 10) . "...");
        error_log("LOGIN CSRF DEBUG: Session Token=" . (isset($_SESSION['csrf_token']) ? substr($_SESSION['csrf_token'], 0, 10) . "..." : "NOT SET"));
    }
    
    // Vérifier le token CSRF
    if (!CSRFToken::verify($tokenFromPost)) {
        $error = 'Jeton de sécurité invalide. Veuillez réessayer.';
        
        // Log d'erreur
        if (defined('APP_DEBUG') && APP_DEBUG) {
            error_log("CSRF VERIFICATION FAILED for IP: " . $_SERVER['REMOTE_ADDR']);
        }
    } else {
        // ============ LOGIN ============
        if ($rateLimiter->isBlocked($_SERVER['REMOTE_ADDR'])) {
            $error = 'Trop de tentatives. Veuillez réessayer dans 5 minutes.';
        } else {
            $email    = trim($_POST['email'] ?? '');
            $password = trim($_POST['password'] ?? '');

            if ($email === '' || $password === '') {
                $error = 'Veuillez remplir tous les champs';
                $rateLimiter->recordAttempt($_SERVER['REMOTE_ADDR']);
            } else {
                $user = $auth->login($email, $password);

                if ($user) {
                    // Réinitialiser le rate limit après succès
                    $rateLimiter->reset($_SERVER['REMOTE_ADDR']);
                    
                    $_SESSION['user_id']   = $user['id'];
                    $_SESSION['email']     = $user['email'];
                    $_SESSION['role']      = $user['role'];
                    $_SESSION['client_id'] = $user['client_id'] ?? null;
                    $_SESSION['agent_id']  = $user['agent_id'] ?? null;

                    // Régénérer l'ID de session après connexion réussie
                    session_regenerate_id(true);

                    switch ($_SESSION['role']) {
                        case 'admin':
                            header('Location: dashboard.php');
                            break;
                        case 'agent':
                            header('Location: dashboard-agent.php');
                            break;
                        default:
                            header('Location: dashboard-client.php');
                    }
                    exit;

                } else {
                    $error = 'Email ou mot de passe incorrect';
                    $rateLimiter->recordAttempt($_SERVER['REMOTE_ADDR']);
                }
            }
        }
    }
}
?>
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion – <?php echo APP_NAME; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .logo-container {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .logo-container img {
            max-height: 80px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">

            <div class="card shadow">
                <div class="card-body p-5">

                    <div class="logo-container">
                        <?php if (file_exists(__DIR__ . '/images/logo.png')): ?>
                            <img src="images/logo.png" alt="<?php echo APP_NAME; ?>">
                        <?php endif; ?>
                        <h3 class="mb-0 fw-bold text-primary">Connexion</h3>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="bi bi-exclamation-circle"></i> 
                            <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="post">
                        <?php echo CSRFToken::field(); ?>
                        
                        <div class="mb-3">
                            <label class="form-label text-muted">Email</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope"></i></span>
                                <input type="email" name="email" class="form-control border-start-0 ps-0" placeholder="votre.email@example.com" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-muted">Mot de passe</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock"></i></span>
                                <input type="password" name="password" id="password" class="form-control border-start-0 border-end-0 ps-0" placeholder="••••••••" required>
                                <span class="input-group-text bg-light border-start-0" style="cursor: pointer;" onclick="togglePassword()">
                                    <i class="bi bi-eye" id="toggleIcon"></i>
                                </span>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mb-3 py-2 fw-bold text-uppercase" style="letter-spacing: 1px;">
                            Se connecter
                        </button>

                        <div class="text-center">
                            Pas encore inscrit? <a href="register.php" class="text-decoration-none fw-bold">Créer un compte</a>
                        </div>
                    </form>

                </div>
            </div>

            <div class="text-center mt-4 text-muted">
                <small>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> - Tous droits réservés</small>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('toggleIcon');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.classList.remove('bi-eye');
            toggleIcon.classList.add('bi-eye-slash');
        } else {
            passwordInput.type = 'password';
            toggleIcon.classList.remove('bi-eye-slash');
            toggleIcon.classList.add('bi-eye');
        }
    }
</script>

</body>
</html>
