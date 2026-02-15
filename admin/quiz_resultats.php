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
$participations = $quizModel->getAllParticipations();

include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12 mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0 text-gray-800">Résultats du Quiz de Sélection</h1>
                <div>
                     <a href="<?php echo url('admin/quiz_questions.php'); ?>" class="btn btn-secondary me-2">
                        <i class="bi bi-gear-fill"></i> Gérer les Questions
                    </a>
                    <a href="<?php echo url('admin/export_quiz.php'); ?>" class="btn btn-success">
                        <i class="bi bi-file-earmark-spreadsheet-fill"></i> Exporter CSV
                    </a>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Classement des Participants</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Client</th>
                                    <th>Email</th>
                                    <th>Score / 60</th>
                                    <th>% Réussite</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($participations as $p): 
                                    $percent = ($p['score'] / 60) * 100;
                                    $color = $percent >= 50 ? 'success' : 'danger';
                                ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($p['date_fin'])); ?></td>
                                        <td>
                                            <div class="fw-bold"><?php echo htmlspecialchars($p['prenom'] . ' ' . $p['nom']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($p['telephone']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($p['email']); ?></td>
                                        <td class="text-center fw-bold fs-5"><?php echo $p['score']; ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-<?php echo $color; ?>"><?php echo number_format($percent, 1); ?>%</span>
                                        </td>
                                        <td>
                                            <?php if ($percent >= 80): ?>
                                                <span class="text-success"><i class="bi bi-star-fill"></i> Excellent</span>
                                            <?php elseif ($percent >= 50): ?>
                                                <span class="text-info"><i class="bi bi-check-circle"></i> Admis</span>
                                            <?php else: ?>
                                                <span class="text-muted">Insuffisant</span>
                                            <?php endif; ?>
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

<?php include __DIR__ . '/../includes/footer.php'; ?>
