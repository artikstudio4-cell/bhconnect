<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/AuthModel.php';
require_once __DIR__ . '/../models/QuizModel.php';

$auth = new AuthModel();
if (!$auth->isLoggedIn() || !$auth->isClient()) {
    header('Location: ' . url('login.php'));
    exit;
}

$quizModel = new QuizModel();
$userId = $auth->getUserId();
$participation = $quizModel->hasParticipated($userId);

// Si pas de participation ou pas finie, rediriger
if (!$participation || $participation['statut'] !== 'termine') {
    header('Location: intro.php');
    exit;
}

$score = $participation['score'];
$total = 60; // On suppose 60 questions
$percentage = ($score / $total) * 100;

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 text-center">
            
            <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
                <div class="card-body p-5">
                    
                    <div class="mb-4">
                        <?php if ($percentage >= 50): ?>
                            <i class="bi bi-trophy-fill display-1 text-warning bounce-animation"></i>
                        <?php else: ?>
                            <i class="bi bi-clipboard-data display-1 text-muted"></i>
                        <?php endif; ?>
                    </div>

                    <h2 class="mb-3">Quiz Terminé !</h2>
                    <p class="text-muted mb-4">Voici votre résultat final.</p>

                    <div class="display-3 fw-bold <?php echo $percentage >= 50 ? 'text-success' : 'text-danger'; ?> mb-2">
                        <?php echo $score; ?> <span class="fs-4 text-muted">/ <?php echo $total; ?></span>
                    </div>
                    
                    <div class="progress mb-4" style="height: 10px;">
                        <div class="progress-bar <?php echo $percentage >= 50 ? 'bg-success' : 'bg-danger'; ?>" 
                             role="progressbar" 
                             style="width: <?php echo $percentage; ?>%"></div>
                    </div>

                    <?php if ($percentage >= 80): ?>
                        <div class="alert alert-success">
                            <strong>Excellent !</strong> Vous êtes prêt pour l'aventure internationale.
                        </div>
                    <?php elseif ($percentage >= 50): ?>
                        <div class="alert alert-info">
                            <strong>Bien joué !</strong> Vous avez de bonnes bases, continuez à vous préparer.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <strong>Encourageant.</strong> Il vous reste encore des choses à apprendre sur la vie à l'étranger.
                        </div>
                    <?php endif; ?>



                    <div class="mt-5">
                        <a href="<?php echo url('dashboard-client.php'); ?>" class="btn btn-primary rounded-pill px-4">
                            <i class="bi bi-house-fill me-2"></i> Retour au Tableau de bord
                        </a>
                        <a href="<?php echo url('destinations.php'); ?>" class="btn btn-outline-primary rounded-pill px-4 ms-2">
                            <i class="bi bi-geo-alt-fill me-2"></i> Voir les Destinations
                        </a>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<style>
    @keyframes bounce {
        0%, 20%, 50%, 80%, 100% {transform: translateY(0);}
        40% {transform: translateY(-20px);}
        60% {transform: translateY(-10px);}
    }
    .bounce-animation {
        animation: bounce 2s infinite;
    }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
