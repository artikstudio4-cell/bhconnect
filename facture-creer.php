<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/AuthModel.php';
require_once __DIR__ . '/models/FactureModel.php';
require_once __DIR__ . '/models/ClientModel.php';
require_once __DIR__ . '/models/DossierModel.php';

$auth = new AuthModel();
if (!$auth->isLoggedIn()) {
    header('Location: ' . url('login.php'));
    exit;
}

$clientModel = new ClientModel();
$dossierModel = new DossierModel();
$clients = $clientModel->getAllClients();

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $factureModel = new FactureModel();
        
        $lignes = [];
        if (isset($_POST['lignes']) && is_array($_POST['lignes'])) {
            foreach ($_POST['lignes'] as $ligne) {
                if (!empty($ligne['desc']) && !empty($ligne['prix'])) {
                    $qty = (int)$ligne['qty'];
                    $prix = (float)$ligne['prix'];
                    $total = $qty * $prix;
                    $lignes[] = [
                        'description' => $ligne['desc'],
                        'quantite' => $qty,
                        'prix_unitaire' => $prix,
                        'total_ligne' => $total
                    ];
                }
            }
        }

        $totalHT = array_sum(array_column($lignes, 'total_ligne'));
        $tva = 0.00; // Pas de TVA pour le Cameroun / régime actuel
        $totalTTC = $totalHT * (1 + ($tva / 100));

        $data = [
            'type' => $_POST['type'],
            'client_id' => $_POST['client_id'],
            'dossier_id' => $_POST['dossier_id'], // À lier dynamiquement selon client, ici simplifié
            'date_emission' => $_POST['date_emission'],
            'date_echeance' => $_POST['date_echeance'],
            'remarque' => $_POST['remarque'],
            'statut' => 'brouillon',
            'montant_ht' => $totalHT,
            'tva_taux' => $tva,
            'montant_ttc' => $totalTTC
        ];

        $id = $factureModel->create($data, $lignes);
        header('Location: ' . url('facture-details.php?id=' . $id));
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex align-items-center mb-4">
        <a href="<?php echo url('factures.php'); ?>" class="btn btn-link text-decoration-none ps-0 me-2"><i class="bi bi-arrow-left"></i> Retour</a>
        <h1 class="h3 mb-0">Créer une Facture / Proforma</h1>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post" id="factureForm">
        <div class="row">
            <!-- Informations Générales -->
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0 text-primary">Infos Générales</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Type de document</label>
                            <select name="type" class="form-select" required>
                                <option value="proforma">Proforma (Devis)</option>
                                <option value="facture">Facture Définitive</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Client</label>
                            <select name="client_id" class="form-select" required>
                                <option value="">Choisir un client...</option>
                                <?php foreach ($clients as $client): ?>
                                    <option value="<?php echo $client['id']; ?>">
                                        <?php echo htmlspecialchars($client['nom'] . ' ' . $client['prenom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Simplifié: Dossier ID manuel ou select statique pour l'instant -->
                        <div class="mb-3">
                            <label class="form-label">Dossier ID (Temporaire)</label>
                            <input type="number" name="dossier_id" class="form-control" required placeholder="ID du dossier">
                            <div class="form-text">Entrez l'ID du dossier relié.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Date d'émission</label>
                            <input type="date" name="date_emission" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Date d'échéance</label>
                            <input type="date" name="date_echeance" class="form-control">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lignes de Facture -->
            <div class="col-md-8 mb-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0 text-primary">Articles & Services</h5>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddLine">
                            <i class="bi bi-plus-lg"></i> Ajouter une ligne
                        </button>
                    </div>
                    <div class="card-body">
                        <table class="table" id="linesTable">
                            <thead>
                                <tr>
                                    <th style="width: 50%;">Description</th>
                                    <th style="width: 15%;">Qté</th>
                                    <th style="width: 20%;">Prix U.</th>
                                    <th style="width: 10%;">Total</th>
                                    <th style="width: 5%;"></th>
                                </tr>
                            </thead>
                            <tbody id="linesContainer">
                                <!-- Ligne par défaut -->
                                <tr>
                                    <td><input type="text" name="lignes[0][desc]" class="form-control" required placeholder="Service..."></td>
                                    <td><input type="number" name="lignes[0][qty]" class="form-control qty-input" value="1" min="1" required></td>
                                    <td><input type="number" name="lignes[0][prix]" class="form-control price-input" value="0.00" step="100" required></td>
                                    <td class="text-end fw-bold total-cell">0 FCFA</td>
                                    <td></td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end fw-bold">Total HT</td>
                                    <td class="text-end fw-bold" id="totalHT">0 FCFA</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end text-muted">TVA (0%)</td>
                                    <td class="text-end text-muted" id="totalTVA">0 FCFA</td>
                                    <td></td>
                                </tr>
                                <tr class="table-primary">
                                    <td colspan="3" class="text-end fw-bold text-primary">TOTAL TTC</td>
                                    <td class="text-end fw-bold text-primary" id="totalTTC">0 FCFA</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>

                        <div class="mb-3 mt-4">
                            <label class="form-label">Notes / Remarques</label>
                            <textarea name="remarque" class="form-control" rows="3" placeholder="Conditions de paiement, virement, etc."></textarea>
                        </div>

                    </div>
                    <div class="card-footer bg-white text-end py-3">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="bi bi-check-lg me-2"></i>Créer la facture
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('linesContainer');
    const btnAdd = document.getElementById('btnAddLine');
    let lineCount = 1;

    function formatPrice(price) {
        return new Intl.NumberFormat('fr-FR').format(price) + ' FCFA';
    }

    function calculateTotals() {
        let totalHT = 0;
        document.querySelectorAll('#linesContainer tr').forEach(row => {
            const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
            const price = parseFloat(row.querySelector('.price-input').value) || 0;
            const lineTotal = qty * price;
            
            row.querySelector('.total-cell').textContent = formatPrice(lineTotal);
            totalHT += lineTotal;
        });

        const tva = totalHT * 0; // 0%
        const ttc = totalHT + tva;

        document.getElementById('totalHT').textContent = formatPrice(totalHT);
        document.getElementById('totalTVA').textContent = formatPrice(tva);
        document.getElementById('totalTTC').textContent = formatPrice(ttc);
    }

    btnAdd.addEventListener('click', function() {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><input type="text" name="lignes[${lineCount}][desc]" class="form-control" required placeholder="Service..."></td>
            <td><input type="number" name="lignes[${lineCount}][qty]" class="form-control qty-input" value="1" min="1" required></td>
            <td><input type="number" name="lignes[${lineCount}][prix]" class="form-control price-input" value="0.00" step="100" required></td>
            <td class="text-end fw-bold total-cell">0 FCFA</td>
            <td>
                <button type="button" class="btn btn-sm btn-outline-danger remove-line">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        container.appendChild(tr);
        lineCount++;
    });

    container.addEventListener('click', function(e) {
        if (e.target.closest('.remove-line')) {
            e.target.closest('tr').remove();
            calculateTotals();
        }
    });

    container.addEventListener('input', function(e) {
        if (e.target.classList.contains('qty-input') || e.target.classList.contains('price-input')) {
            calculateTotals();
        }
    });

    calculateTotals();
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
