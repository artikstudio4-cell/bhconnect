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

// Vérifier si déjà participé
$participation = $quizModel->hasParticipated($userId);

// Si terminé, rediriger vers le résultat
if ($participation && $participation['statut'] === 'termine') {
    header('Location: resultat.php');
    exit;
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
                <div class="card-header bg-primary text-white p-5 text-center position-relative">
                    <div class="position-absolute top-0 start-0 w-100 h-100" style="background: linear-gradient(45deg, rgba(0,0,0,0.2), transparent);"></div>
                    <i class="bi bi-pencil-square display-1 mb-3"></i>
                    <h1 class="fw-bold">Test de Sélection</h1>
                    <p class="lead mb-0">Évaluez votre aptitude à la vie à l'étranger</p>
                </div>
                
                <div class="card-body p-5">
                    <h3 class="text-primary mb-4"><i class="bi bi-info-circle-fill"></i> À propos de ce test</h3>
                    
                    <div class="alert alert-warning mb-4">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>Attention :</strong> Vous ne pouvez passer ce test qu'une seule fois. Assurez-vous d'avoir du temps devant vous.
                    </div>

                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <div class="bg-light p-3 rounded-circle me-3 text-primary">
                                    <i class="bi bi-list-ol fs-4"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0">60 Questions</h5>
                                    <small class="text-muted">QCM Variés</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <div class="bg-light p-3 rounded-circle me-3 text-primary">
                                    <i class="bi bi-stopwatch fs-4"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0">Pas de limite</h5>
                                    <small class="text-muted">Prenez votre temps</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <div class="bg-light p-3 rounded-circle me-3 text-primary">
                                    <i class="bi bi-globe fs-4"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0">Thématiques</h5>
                                    <small class="text-muted">Culture, Admin, Vie Pratique</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <div class="bg-light p-3 rounded-circle me-3 text-primary">
                                    <i class="bi bi-award fs-4"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0">Score Immédiat</h5>
                                    <small class="text-muted">Résultat à la fin</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="text-center mt-4">
                        <p class="mb-3">En cliquant sur "Commencer", votre participation sera enregistrée.</p>
                        <a href="jouer.php?action=start" class="btn btn-success btn-lg px-5 py-3 rounded-pill shadow-sm hover-scale">
                            <i class="bi bi-play-fill me-2"></i> Commencer le Test
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .hover-scale {
        transition: transform 0.2s;
    }
    .hover-scale:hover {
        transform: scale(1.05);
    }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
