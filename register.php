<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/AuthModel.php';
require_once __DIR__ . '/models/ClientModel.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$auth  = new AuthModel();
$error = '';
$success = '';

// Déjà connecté → redirection
if ($auth->isLoggedIn()) {
    header('Location: dashboard-client.php');
    exit;
}

// Soumission formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Vérifier le token CSRF
    if (!CSRFToken::verify($_POST['csrf_token'] ?? '')) {
        $error = 'Jeton de sécurité invalide. Veuillez réessayer.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $password_confirm = trim($_POST['password_confirm'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $nom = trim($_POST['nom'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $adresse = trim($_POST['adresse'] ?? '');

        // Validation
        if (empty($email) || empty($password) || empty($prenom) || empty($nom)) {
            $error = 'Veuillez remplir tous les champs (Email, Mot de passe, Prénom, Nom)';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email invalide';
        } elseif (strlen($password) < 6) {
            $error = 'Le mot de passe doit contenir au moins 6 caractères';
        } elseif ($password !== $password_confirm) {
            $error = 'Les mots de passe ne correspondent pas';
        } else {
            // Vérifier que l'email n'existe pas déjà
            try {
                $db = Database::getInstance()->getConnection();
                $stmt = $db->prepare("SELECT id FROM utilisateurs WHERE email = ?");
                $stmt->execute([$email]);
                
                if ($stmt->fetch()) {
                    $error = 'Cet email est déjà utilisé';
                } else {
                    // Créer le client
                    $clientModel = new ClientModel();
                    $userId = $clientModel->createClient([
                        'email' => $email,
                        'mot_de_passe' => $password,
                        'prenom' => $prenom,
                        'nom' => $nom,
                        'telephone' => $telephone,
                        'adresse' => $adresse,
                        'created_by' => null
                    ]);

                    if ($userId) {
                        $success = 'Inscription réussie! Vous pouvez maintenant vous connecter.';
                        // Redirection vers login après 2 secondes ou affichage message
                        header("refresh:2;url=login.php");
                    } else {
                        $error = 'Erreur lors de l\'inscription. Veuillez réessayer.';
                    }
                }
            } catch (Exception $e) {
                error_log("Erreur inscription: " . $e->getMessage() . "\n" . $e->getTraceAsString());
                
                // En debug, afficher le vrai message
                if (defined('APP_DEBUG') && APP_DEBUG) {
                    $error = 'Erreur: ' . $e->getMessage();
                } else {
                    $error = 'Erreur lors de l\'inscription. Veuillez réessayer.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription – <?php echo APP_NAME; ?></title>
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
        <div class="col-md-6">

            <div class="card shadow">
                <div class="card-body p-5">

                    <div class="logo-container">
                        <?php if (file_exists(__DIR__ . '/images/logo.png')): ?>
                            <img src="images/logo.png" alt="<?php echo APP_NAME; ?>">
                        <?php endif; ?>
                        <h3 class="mb-0 fw-bold text-primary">Créer un compte</h3>
                        <p class="text-muted">Rejoignez <?php echo APP_NAME; ?></p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="bi bi-exclamation-circle"></i> 
                            <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="bi bi-check-circle"></i> 
                            <?php echo htmlspecialchars($success); ?>
                            <div class="mt-2 text-small">Redirection vers la connexion...</div>
                        </div>
                    <?php else: ?>

                        <form method="post">
                            <?php echo CSRFToken::field(); ?>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">Prénom *</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-person"></i></span>
                                        <input type="text" name="prenom" class="form-control border-start-0 ps-0" 
                                               value="<?php echo htmlspecialchars($_POST['prenom'] ?? ''); ?>"
                                               placeholder="Jean" required>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">Nom *</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-person"></i></span>
                                        <input type="text" name="nom" class="form-control border-start-0 ps-0" 
                                               value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>"
                                               placeholder="Dupont" required>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-muted">Email *</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope"></i></span>
                                    <input type="email" name="email" class="form-control border-start-0 ps-0" 
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                           placeholder="votre.email@example.com" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">Mot de passe *</label>
                                    <div class="input-group">
                                        <input type="password" name="password" id="password" class="form-control border-end-0" 
                                               placeholder="Min. 6 caractères" minlength="6" required>
                                        <span class="input-group-text bg-light border-start-0" style="cursor: pointer;" onclick="togglePassword('password', 'icon1')">
                                            <i class="bi bi-eye" id="icon1"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">Confirmer *</label>
                                    <div class="input-group">
                                        <input type="password" name="password_confirm" id="confirm_password" class="form-control border-end-0" 
                                               placeholder="Répéter" minlength="6" required>
                                        <span class="input-group-text bg-light border-start-0" style="cursor: pointer;" onclick="togglePassword('confirm_password', 'icon2')">
                                            <i class="bi bi-eye" id="icon2"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-muted">Téléphone</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-telephone"></i></span>
                                    <input type="tel" name="telephone" class="form-control border-start-0 ps-0" 
                                           value="<?php echo htmlspecialchars($_POST['telephone'] ?? ''); ?>"
                                           placeholder="+33 6 12 34 56 78">
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label text-muted">Adresse</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-geo-alt"></i></span>
                                    <input type="text" name="adresse" class="form-control border-start-0 ps-0" 
                                           value="<?php echo htmlspecialchars($_POST['adresse'] ?? ''); ?>"
                                           placeholder="123 Rue de l'Exemple">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 mb-3 py-2 fw-bold text-uppercase" style="letter-spacing: 1px;">
                                S'inscrire
                            </button>

                            <div class="text-center">
                                Déjà inscrit? <a href="login.php" class="text-decoration-none fw-bold">Se connecter</a>
                            </div>
                        </form>
                    <?php endif; ?>

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
    function togglePassword(inputId, iconId) {
        const passwordInput = document.getElementById(inputId);
        const toggleIcon = document.getElementById(iconId);
        
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
