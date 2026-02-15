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

$factureModel = new FactureModel();
$filterType = $_GET['type'] ?? null;
$factures = $factureModel->getAll($filterType, 50);

include __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-receipt text-primary me-2"></i>Facturation</h1>
        <a href="<?php echo url('facture-creer.php'); ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg me-2"></i>Nouvelle Facture
        </a>
    </div>

    <!-- Filtres -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-3">
            <div class="btn-group" role="group">
                <a href="<?php echo url('factures.php'); ?>" class="btn btn-outline-secondary <?php echo !$filterType ? 'active' : ''; ?>">Tout</a>
                <a href="<?php echo url('factures.php?type=facture'); ?>" class="btn btn-outline-secondary <?php echo $filterType === 'facture' ? 'active' : ''; ?>">Factures</a>
                <a href="<?php echo url('factures.php?type=proforma'); ?>" class="btn btn-outline-secondary <?php echo $filterType === 'proforma' ? 'active' : ''; ?>">Proformas</a>
            </div>
        </div>
    </div>

    <!-- Tableau -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Numéro</th>
                            <th>Date</th>
                            <th>Client</th>
                            <th>Dossier</th>
                            <th>Montant TTC</th>
                            <th>Statut</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($factures)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">Auccune facture trouvée.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($factures as $facture): ?>
                                <tr>
                                    <td class="ps-4 fw-medium">
                                        <a href="<?php echo url('facture-details.php?id=' . $facture['id']); ?>" class="text-decoration-none text-primary">
                                            <?php echo htmlspecialchars($facture['numero_facture']); ?>
                                        </a>
                                        <?php if ($facture['type'] === 'proforma'): ?>
                                            <span class="badge bg-secondary ms-2" style="font-size: 0.65rem;">PROFORMA</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($facture['date_emission'])); ?></td>
                                    <td><?php echo htmlspecialchars($facture['client_prenom'] . ' ' . $facture['client_nom']); ?></td>
                                    <td>
                                        <?php if ($facture['numero_dossier']): ?>
                                            <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($facture['numero_dossier']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-bold"><?php echo number_format($facture['montant_ttc'], 2, ',', ' '); ?> €</td>
                                    <td>
                                        <?php
                                        $badges = [
                                            'brouillon' => 'bg-secondary',
                                            'envoyee'   => 'bg-info text-dark',
                                            'payee'     => 'bg-success',
                                            'annulee'   => 'bg-danger'
                                        ];
                                        $class = $badges[$facture['statut']] ?? 'bg-secondary';
                                        $label = ucfirst($facture['statut']);
                                        ?>
                                        <span class="badge <?php echo $class; ?>"><?php echo $label; ?></span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <a href="<?php echo url('facture-details.php?id=' . $facture['id']); ?>" class="btn btn-sm btn-outline-primary" title="Voir">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
