<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/models/DestinationModel.php';
require_once __DIR__ . '/models/AuthModel.php';

$auth = new AuthModel();
$destinationModel = new DestinationModel();
$destinations = $destinationModel->getAll(); // Fetch data from DB

// Simulation d'images si non présentes en DB (pour la démo)
$images = [
    'Canada' => 'https://images.unsplash.com/photo-1517935706615-2717063c2225?q=80&w=400&auto=format&fit=crop', // Canada
    'France' => 'https://images.unsplash.com/photo-1502602898657-3e91760cbb34?q=80&w=400&auto=format&fit=crop', // Paris
    'USA' => 'https://images.unsplash.com/photo-1550684848-fac1c5b4e853?q=80&w=400&auto=format&fit=crop', // USA
    'Belgique' => 'https://images.unsplash.com/photo-1563806969-f83df8c0678d?q=80&w=400&auto=format&fit=crop', // Belgium (Bruges)
    'Suisse' => 'https://images.unsplash.com/photo-1527668752968-14dc70a27c95?q=80&w=400&auto=format&fit=crop' // Switzerland
];

include __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="text-center mb-5">
        <h1 class="display-4 fw-bold text-primary">Nos Destinations d'Études</h1>
        <p class="lead text-muted">Explorez les opportunités académiques à l'international sélectionnées pour vous.</p>
    </div>

    <div class="row g-4">
        <?php foreach ($destinations as $dest): ?>
            <?php 
                $pays = $dest['pays']; 
                $img = $images[$pays] ?? 'https://via.placeholder.com/400x300?text=' . urlencode($pays);
            ?>
            <div class="col-md-4">
                <div class="card h-100 shadow-sm hover-card border-0">
                    <div style="height: 200px; overflow: hidden; border-top-left-radius: 15px; border-top-right-radius: 15px;">
                        <img src="<?php echo $img; ?>" class="card-img-top w-100 h-100 object-fit-cover" alt="<?php echo htmlspecialchars($pays); ?>">
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h3 class="card-title h4 mb-0"><?php echo htmlspecialchars($pays); ?></h3>
                            <span class="badge bg-info text-dark rounded-pill">
                                <i class="bi bi-mortarboard"></i> <?php echo htmlspecialchars($dest['universite_partenariat']); ?>
                            </span>
                        </div>
                        <p class="card-text text-muted"><?php echo htmlspecialchars($dest['description']); ?></p>
                        
                        <hr class="my-3 text-muted">
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span><i class="bi bi-cash-coin text-success"></i> Frais estimés:</span>
                            <span class="fw-bold"><?php echo number_format($dest['frais'], 0, ',', ' '); ?> €</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span><i class="bi bi-people text-primary"></i> Places:</span>
                            <span class="fw-bold"><?php echo $dest['places_disponibles']; ?></span>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-0 pb-4 pt-0">
                        <?php if ($auth->isLoggedIn()): ?>
                            <!-- Lien vers candidature (à implémenter plus tard si besoin) ou info -->
                             <button class="btn btn-primary w-100 rounded-pill">
                                <i class="bi bi-info-circle"></i> Voir les détails
                             </button>
                        <?php else: ?>
                            <a href="register.php" class="btn btn-outline-primary w-100 rounded-pill">S'inscrire pour postuler</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
    .hover-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border-radius: 15px;
    }
    .hover-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.1) !important;
    }
    .object-fit-cover {
        object-fit: cover;
    }
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
