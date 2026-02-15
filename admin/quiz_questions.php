<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/AuthModel.php';
require_once __DIR__ . '/../models/QuizModel.php';

$auth = new AuthModel();
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ' . url('login.php'));
    exit;
}

$quizModel = new QuizModel();
$error = '';
$success = '';

// Traitement des actions (Ajout/Modif/Suppr)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'delete' && isset($_POST['id'])) {
            if ($quizModel->deleteQuestion($_POST['id'])) {
                $success = 'Question supprimée avec succès.';
            } else {
                $error = 'Erreur lors de la suppression.';
            }
        } elseif ($_POST['action'] === 'save') {
           $data = [
               'question' => $_POST['question'],
               'option_a' => $_POST['option_a'],
               'option_b' => $_POST['option_b'],
               'option_c' => $_POST['option_c'],
               'option_d' => $_POST['option_d'],
               'reponse_correcte' => $_POST['reponse_correcte'],
               'categorie' => $_POST['categorie']
           ];

           if (isset($_POST['id']) && !empty($_POST['id'])) {
               if ($quizModel->updateQuestion($_POST['id'], $data)) {
                   $success = 'Question mise à jour.';
               } else {
                   $error = 'Erreur lors de la mise à jour.';
               }
           } else {
               if ($quizModel->addQuestion($data)) {
                   $success = 'Question ajoutée.';
               } else {
                   $error = 'Erreur lors de l\'ajout.';
               }
           }
        }
    }
}

// Récupération des données pour édition (si applicable)
$editQuestion = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $editQuestion = $quizModel->getQuestionById($_GET['id']);
}

$questions = $quizModel->getAllQuestions();

include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Sidebar Navigation (si existante, sinon menu haut) -->
        
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800">Gestion des Questions du Quiz</h1>
                <a href="<?php echo url('admin/quiz_questions.php'); ?>" class="btn btn-secondary btn-sm <?php echo !$editQuestion ? 'd-none' : ''; ?>">
                    <i class="bi bi-plus-circle"></i> Mode Ajout
                </a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <div class="row">
                <!-- Formulaire -->
                <div class="col-lg-4 mb-4">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <?php echo $editQuestion ? 'Modifier la question #' . $editQuestion['id'] : 'Ajouter une nouvelle question'; ?>
                            </h6>
                        </div>
                        <div class="card-body">
                            <form method="post" action="quiz_questions.php">
                                <input type="hidden" name="action" value="save">
                                <?php if ($editQuestion): ?>
                                    <input type="hidden" name="id" value="<?php echo $editQuestion['id']; ?>">
                                <?php endif; ?>

                                <div class="mb-3">
                                    <label class="form-label">Question</label>
                                    <textarea class="form-control" name="question" rows="3" required><?php echo $editQuestion['question'] ?? ''; ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Catégorie</label>
                                    <select class="form-select" name="categorie">
                                        <?php 
                                        $cats = ['Général', 'Culture', 'Géographie', 'Langue', 'Administratif', 'Vie Pratique', 'Santé', 'Sécurité', 'Etudes', 'Economie'];
                                        $currentCat = $editQuestion['categorie'] ?? 'Général';
                                        foreach ($cats as $c) {
                                            $selected = ($c === $currentCat) ? 'selected' : '';
                                            echo "<option value=\"$c\" $selected>$c</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <div class="row g-2 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label text-muted small">Option A</label>
                                        <input type="text" class="form-control form-control-sm" name="option_a" value="<?php echo $editQuestion['option_a'] ?? ''; ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted small">Option B</label>
                                        <input type="text" class="form-control form-control-sm" name="option_b" value="<?php echo $editQuestion['option_b'] ?? ''; ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted small">Option C</label>
                                        <input type="text" class="form-control form-control-sm" name="option_c" value="<?php echo $editQuestion['option_c'] ?? ''; ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted small">Option D</label>
                                        <input type="text" class="form-control form-control-sm" name="option_d" value="<?php echo $editQuestion['option_d'] ?? ''; ?>" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Bonne Réponse</label>
                                    <div class="btn-group w-100" role="group">
                                        <?php foreach (['A', 'B', 'C', 'D'] as $opt): ?>
                                            <input type="radio" class="btn-check" name="reponse_correcte" id="btnradio<?php echo $opt; ?>" value="<?php echo $opt; ?>" <?php echo (isset($editQuestion) && $editQuestion['reponse_correcte'] === $opt) ? 'checked' : ''; ?> required>
                                            <label class="btn btn-outline-primary" for="btnradio<?php echo $opt; ?>"><?php echo $opt; ?></label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-save"></i> Enregistrer
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Liste -->
                <div class="col-lg-8">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Liste des Questions (<?php echo count($questions); ?>)</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th>ID</th>
                                            <th>Question</th>
                                            <th>Cat.</th>
                                            <th>Réponse</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($questions as $q): ?>
                                            <tr>
                                                <td><?php echo $q['id']; ?></td>
                                                <td>
                                                    <div class="fw-bold text-truncate" style="max-width: 300px;"><?php echo htmlspecialchars($q['question']); ?></div>
                                                    <small class="text-muted">
                                                        A: <?php echo htmlspecialchars($q['option_a']); ?> | 
                                                        B: <?php echo htmlspecialchars($q['option_b']); ?>
                                                    </small>
                                                </td>
                                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($q['categorie']); ?></span></td>
                                                <td class="text-center fw-bold text-success"><?php echo $q['reponse_correcte']; ?></td>
                                                <td class="text-center">
                                                    <a href="?action=edit&id=<?php echo $q['id']; ?>" class="btn btn-sm btn-info text-white me-1">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <form method="post" action="quiz_questions.php" class="d-inline" onsubmit="return confirm('Supprimer cette question ?');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?php echo $q['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
