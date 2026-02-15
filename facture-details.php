<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/AuthModel.php';
require_once __DIR__ . '/models/FactureModel.php';

$auth = new AuthModel();
if (!$auth->isLoggedIn()) {
    header('Location: ' . url('login.php'));
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: ' . url('factures.php'));
    exit;
}

$factureModel = new FactureModel();
$facture = $factureModel->getById($id);

if (!$facture) {
    die("Facture introuvable.");
}

// Transformation Proforma -> Facture
if (isset($_POST['action']) && $_POST['action'] === 'validate' && $facture['type'] === 'proforma') {
    $factureModel->transformProformaToInvoice($id);
    header('Location: ' . url('facture-details.php?id=' . $id));
    exit;
}

include __DIR__ . '/includes/header.php';
?>

<div class="container py-4 no-print">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="<?php echo url('factures.php'); ?>" class="btn btn-link text-decoration-none ps-0"><i class="bi bi-arrow-left"></i> Retour</a>
            <h1 class="h3 mb-0">Détails <?php echo ucfirst($facture['type']); ?></h1>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-secondary">
                <i class="bi bi-printer me-2"></i>Imprimer / PDF
            </button>
            <?php if ($facture['type'] === 'proforma'): ?>
                <form method="post" onsubmit="return confirm('Confirmer la transformation en facture définitive ?');">
                    <input type="hidden" name="action" value="validate">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle me-2"></i>Valider en Facture
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Zone Imprimable -->
<div class="container py-4 print-container">
    <div class="card shadow-none border border-secondary">
        <div class="card-body p-5">
            
            <!-- Header Facture -->
            <div class="row mb-5">
                <div class="col-6">
                    <?php if (defined('SHOW_LOGO') && SHOW_LOGO && defined('LOGO_PATH') && file_exists(__DIR__ . '/images/logo.png')): ?>
                        <img src="<?php echo LOGO_PATH; ?>" alt="<?php echo APP_NAME; ?>" height="60" class="mb-3">
                    <?php else: ?>
                        <h2 class="fw-bold text-primary"><?php echo APP_NAME; ?></h2>
                    <?php endif; ?>
                    <address class="text-muted small">
                        <strong><?php echo APP_NAME; ?></strong><br>
                        123 Avenue des Affaires<br>
                        75000 Paris, France<br>
                        contact@bhconnect.com
                    </address>
                </div>
                <div class="col-6 text-end">
                    <h3 class="fw-bold mb-1"><?php echo $facture['type'] === 'proforma' ? 'PROFORMA' : 'FACTURE'; ?></h3>
                    <p class="fs-5 text-muted mb-4"><?php echo $facture['numero_facture']; ?></p>
                    <div class="text-muted small">
                        <div><strong>Date d'émission :</strong> <?php echo date('d/m/Y', strtotime($facture['date_emission'])); ?></div>
                        <?php if ($facture['date_echeance']): ?>
                            <div><strong>Date d'échéance :</strong> <?php echo date('d/m/Y', strtotime($facture['date_echeance'])); ?></div>
                        <?php endif; ?>
                        <div><strong>Dossier :</strong> <?php echo htmlspecialchars($facture['numero_dossier'] ?? '-'); ?></div>
                    </div>
                </div>
            </div>

            <!-- Client Info -->
            <div class="row mb-5">
                <div class="col-6">
                    <h6 class="text-uppercase text-secondary fw-bold small">Facturé à :</h6>
                    <div class="fs-6 fw-bold mb-1"><?php echo htmlspecialchars($facture['client_prenom'] . ' ' . $facture['client_nom']); ?></div>
                    <div class="text-muted"><?php echo htmlspecialchars($facture['client_email']); ?></div>
                    <div class="text-muted"><?php echo htmlspecialchars($facture['client_telephone']); ?></div>
                    <div class="text-muted"><?php echo nl2br(htmlspecialchars($facture['client_adresse'] ?? '')); ?></div>
                </div>
            </div>

            <!-- Tableau Lignes -->
            <div class="table-responsive mb-5">
                <table class="table table-striped table-bordered">
                    <thead class="bg-light">
                            <tr>
                                <th class="py-3">Description</th>
                                <th class="text-center py-3" style="width: 100px;">Qté</th>
                                <th class="text-end py-3" style="width: 150px;">Prix U.</th>
                                <th class="text-end py-3" style="width: 150px;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($facture['lignes'] as $ligne): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($ligne['description']); ?></td>
                                    <td class="text-center"><?php echo $ligne['quantite']; ?></td>
                                    <td class="text-end"><?php echo number_format($ligne['prix_unitaire'], 0, ',', ' '); ?> FCFA</td>
                                    <td class="text-end fw-bold"><?php echo number_format($ligne['total_ligne'], 0, ',', ' '); ?> FCFA</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Totaux -->
                <div class="row mb-5">
                    <div class="col-6">
                        <?php if ($facture['remarque']): ?>
                            <div class="alert alert-light border">
                                <strong>Note :</strong><br>
                                <?php echo nl2br(htmlspecialchars($facture['remarque'])); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-6">
                        <table class="table table-borderless">
                            <tr>
                                <td class="text-end">Total HT</td>
                                <td class="text-end fw-bold" style="width: 150px;"><?php echo number_format($facture['montant_ht'], 0, ',', ' '); ?> FCFA</td>
                            </tr>
                            <tr>
                                <td class="text-end">TVA (<?php echo $facture['tva_taux']; ?>%)</td>
                                <td class="text-end text-muted"><?php echo number_format($facture['montant_ht'] * ($facture['tva_taux'] / 100), 0, ',', ' '); ?> FCFA</td>
                            </tr>
                            <tr class="border-top border-2 border-primary">
                                <td class="text-end fs-5 fw-bold text-primary pt-3">Total TTC</td>
                                <td class="text-end fs-5 fw-bold text-primary pt-3"><?php echo number_format($facture['montant_ttc'], 0, ',', ' '); ?> FCFA</td>
                            </tr>
                        </table>
                    </div>
                </div>

            <!-- Footer Facture -->
            <div class="text-center text-muted small mt-5 pt-4 border-top">
                <?php echo APP_NAME; ?> - Société anonyme au capital de 10 000€ - SIRET 123 456 789 00012
            </div>

        </div>
    </div>
</div>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    .print-container, .print-container * {
        visibility: visible;
    }
    .print-container {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        margin: 0;
        padding: 0;
    }
    .no-print {
        display: none !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
