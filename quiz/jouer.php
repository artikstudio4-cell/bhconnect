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

// Vérifier participation
$participation = $quizModel->hasParticipated($userId);

// Si pas de participation, on la crée (si action start) ou on redirige
if (!$participation) {
    if (isset($_GET['action']) && $_GET['action'] === 'start') {
        $quizModel->startQuiz($userId);
    } else {
        header('Location: intro.php');
        exit;
    }
} elseif ($participation['statut'] === 'termine') {
    header('Location: resultat.php');
    exit;
}

// Traitement du formulaire (Soumission)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_quiz'])) {
    // Récupérer les réponses (ex: q_1 => 'A')
    $answers = [];
    // Récupérer les réponses (ex: q_1 => 'A')
    $answers = [];
    foreach ($_POST as $key => $value) {
        // Ignorer les champs timeout ou autres qui ne sont pas strictement q_ID
        if (strpos($key, 'q_') === 0 && strpos($key, '_timeout') === false) {
            $qId = (int)str_replace('q_', '', $key);
            // S'assurer qu'on ne garde que les réponses valides (A, B, C, D) ou vide si besoin
            if (!empty($value) && $value !== '0') {
                $answers[$qId] = $value;
            }
        }
    }

    // Sauvegarder et calculer
    $quizModel->submitQuiz($userId, $answers);
    header('Location: resultat.php');
    exit;
}

// Récupérer les questions (On suppose qu'on les charge à chaque fois, 
// idéalement on devrait les stocker en session ou avoir une table de liaison pour figer les questions par participation,
// mais pour faire simple ici, on reprend 60 questions aléatoires ou statiques.
// Note: Si on fait ORDER BY RAND() à chaque refresh, les questions changent si l'user F5.
// Pour la V1, on assume qu'il le fait en une fois. 
$questions = $quizModel->getQuestions(60);

include __DIR__ . '/../includes/header.php';
?>

<style>
    .question-card {
        display: none; /* Masquer toutes les questions par défaut */
    }
    .question-card.active {
        display: block; /* Afficher uniquement la question active */
        animation: fadeIn 0.5s;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .custom-option {
        position: relative;
        transition: all 0.2s;
        cursor: pointer;
        border: 2px solid #e9ecef;
    }
    .custom-option:hover {
        background-color: #f8f9fa;
        border-color: #dee2e6;
    }
    .form-check-input:checked + .form-check-label {
        color: var(--primary);
        font-weight: bold;
    }
    .custom-option:has(.form-check-input:checked) {
        border-color: var(--primary) !important;
        background-color: #e8f0fe;
    }
    .timer-bar-container {
        height: 5px;
        background-color: #e9ecef;
        border-radius: 5px;
        overflow: hidden;
        margin-bottom: 20px;
    }
    .timer-bar {
        height: 100%;
        background-color: var(--primary);
        width: 100%;
        transition: width 1s linear;
    }
</style>

<div class="container py-4">
    <!-- Header du Quiz -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-1 text-primary fw-bold">Test d'aptitude</h4>
                        <p class="text-muted mb-0 small">Question <span id="current-q-num">1</span> sur <?php echo count($questions); ?></p>
                    </div>
                   <div class="text-center">
                        <div class="h2 mb-0 fw-bold text-danger" id="timer-display">10</div>
                        <small class="text-muted">secondes</small>
                   </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Feedback visuel (Toast/Alert) -->
    <div id="feedback-area"></div>

    <form method="post" id="quizForm">
        <?php foreach ($questions as $index => $q): ?>
            <div class="card shadow border-0 question-card" id="q-card-<?php echo $index; ?>" data-index="<?php echo $index; ?>">
                
                <!-- Barre de temps pour la question -->
                <div class="timer-bar-container">
                    <div class="timer-bar" id="timer-bar-<?php echo $index; ?>"></div>
                </div>

                <div class="card-body p-5">
                    <h4 class="mb-4">
                        <span class="badge bg-light text-dark border me-2"><?php echo $index + 1; ?>.</span>
                        <?php echo htmlspecialchars($q['question']); ?>
                    </h4>
                    
                    <div class="row g-3">
                        <?php foreach (['A', 'B', 'C', 'D'] as $opt): ?>
                            <?php $optText = $q['option_' . strtolower($opt)]; ?>
                            <div class="col-md-6">
                                <div class="form-check custom-option p-4 rounded bg-white">
                                    <input class="form-check-input" type="radio" 
                                           name="q_<?php echo $q['id']; ?>" 
                                           id="opt_<?php echo $q['id'] . '_' . $opt; ?>" 
                                           value="<?php echo $opt; ?>"
                                           onchange="handleSelection(<?php echo $index; ?>)"> <!-- Auto-next on click -->
                                    <label class="form-check-label w-100 stretched-link" for="opt_<?php echo $q['id'] . '_' . $opt; ?>">
                                        <?php echo htmlspecialchars($optText); ?>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Champ caché pour indiquer "pas de réponse" si timeout (géré par JS) -->
                    <input type="hidden" name="q_<?php echo $q['id']; ?>_timeout" id="timeout_<?php echo $index; ?>" value="0">

                </div>
                <!-- Pas de bouton "Suivant" manuel car on veut du flux rapide ou timeout -->
            </div>
        <?php endforeach; ?>
        
        <!-- Champ caché pour valider la soumission PHP -->
        <input type="hidden" name="submit_quiz" value="1">
        
        <!-- Bouton Submit caché, déclenché à la fin -->
        <button type="submit" id="final-submit" style="display: none;">Envoyer</button>
    </form>
</div>

<script>
    const totalQuestions = <?php echo count($questions); ?>;
    let currentQuestionIndex = 0;
    let timeLeft = 10;
    let timerInterval;

    // Initialisation
    document.addEventListener('DOMContentLoaded', () => {
        showQuestion(currentQuestionIndex);
    });

    function showQuestion(index) {
        // Masquer toutes les questions
        document.querySelectorAll('.question-card').forEach(el => el.classList.remove('active'));
        
        // Afficher la courante
        const currentCard = document.getElementById('q-card-' + index);
        if (currentCard) {
            currentCard.classList.add('active');
            
            // Update Headers
            document.getElementById('current-q-num').textContent = index + 1;
            
            // Reset Timer
            resetTimer(index);
        } else {
            // Fin du quiz
            finishQuiz();
        }
    }

    function resetTimer(index) {
        clearInterval(timerInterval);
        timeLeft = 10;
        updateTimerDisplay();
        
        // Barre de progression
        const bar = document.getElementById('timer-bar-' + index);
        if(bar) bar.style.width = '100%';

        timerInterval = setInterval(() => {
            timeLeft--;
            updateTimerDisplay();
            
            // Update Barre
            if(bar) {
                const percentage = (timeLeft / 10) * 100;
                bar.style.width = percentage + '%';
                if(timeLeft <= 3) bar.style.backgroundColor = '#dc3545'; // Rouge à la fin
            }

            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                handleTimeout(index);
            }
        }, 1000);
    }

    function updateTimerDisplay() {
        document.getElementById('timer-display').textContent = timeLeft;
    }

    function handleSelection(index) {
        // Arrêter le chrono dès qu'une sélection est faite
        clearInterval(timerInterval);
        
        // Petit délai pour feedback visuel puis next
        setTimeout(() => {
            nextQuestion();
        }, 300); // 300ms de pause
    }

    function handleTimeout(index) {
        // Marquer comme timeout (input hidden ou juste laisser vide)
        // Passer à la suivante
        nextQuestion();
    }

    function nextQuestion() {
        currentQuestionIndex++;
        if (currentQuestionIndex < totalQuestions) {
            showQuestion(currentQuestionIndex);
        } else {
            finishQuiz();
        }
    }

    function finishQuiz() {
        clearInterval(timerInterval);
        document.getElementById('quizForm').submit();
    }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
