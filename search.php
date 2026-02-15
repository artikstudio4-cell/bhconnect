<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/AuthModel.php';
require_once __DIR__ . '/models/ClientModel.php';
require_once __DIR__ . '/models/DossierModel.php';
require_once __DIR__ . '/models/RendezVousModel.php';

header('Content-Type: application/json');

$auth = new AuthModel();
if (!$auth->isLoggedIn()) {
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

$query = $_GET['q'] ?? '';
$query = trim($query);

// Si la requête est vide, afficher les suggestions populaires
if (strlen($query) < 2) {
    $results = [];
    $suggestions = $_GET['suggestions'] ?? false;
    
    if ($suggestions) {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Récupérer les clients/dossiers/RDV populaires ou récents
            if ($auth->isAdmin()) {
                // Clients récents
                $stmt = $db->prepare("
                    SELECT 
                        'client' as type,
                        c.id,
                        CONCAT(c.nom, ' ', c.prenom) as titre,
                        u.email as subtitle,
                        CONCAT('clients.php?action=edit&id=', c.id) as url,
                        c.nom,
                        c.prenom
                    FROM clients c
                    INNER JOIN utilisateurs u ON u.id = c.utilisateur_id
                    ORDER BY c.date_creation DESC
                    LIMIT 4
                ");
                $stmt->execute();
                $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($clients as $client) {
                    $results[] = [
                        'type' => 'client',
                        'id' => $client['id'],
                        'titre' => $client['titre'],
                        'subtitle' => 'Client',
                        'url' => url($client['url']),
                        'icon' => 'bi-person'
                    ];
                }
                
                // Dossiers récents
                $stmt = $db->prepare("
                    SELECT 
                        'dossier' as type,
                        d.id,
                        d.numero_dossier as titre,
                        CONCAT('Type: ', d.type_dossier) as subtitle,
                        CONCAT('dossiers.php?action=view&id=', d.id) as url,
                        d.statut
                    FROM dossiers d
                    ORDER BY d.date_modification DESC
                    LIMIT 4
                ");
                $stmt->execute();
                $dossiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($dossiers as $dossier) {
                    $results[] = [
                        'type' => 'dossier',
                        'id' => $dossier['id'],
                        'titre' => $dossier['titre'],
                        'subtitle' => $dossier['subtitle'],
                        'url' => url($dossier['url']),
                        'icon' => 'bi-folder',
                        'statut' => $dossier['statut']
                    ];
                }
                
                // Rendez-vous à venir
                $stmt = $db->prepare("
                    SELECT 
                        'rendez-vous' as type,
                        r.id,
                        CONCAT('RDV - ', c.nom, ' ', c.prenom) as titre,
                        CONCAT('Le ', DATE_FORMAT(r.date_heure, '%d/%m/%Y à %H:%i')) as subtitle,
                        CONCAT('rendez-vous.php?action=view&id=', r.id) as url,
                        r.date_heure
                    FROM rendez_vous r
                    INNER JOIN clients c ON c.id = r.client_id
                    WHERE r.date_heure >= NOW()
                    ORDER BY r.date_heure ASC
                    LIMIT 4
                ");
                $stmt->execute();
                $rdvs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($rdvs as $rdv) {
                    $results[] = [
                        'type' => 'rendez-vous',
                        'id' => $rdv['id'],
                        'titre' => $rdv['titre'],
                        'subtitle' => $rdv['subtitle'],
                        'url' => url($rdv['url']),
                        'icon' => 'bi-calendar-event'
                    ];
                }
            } else {
                // Pour les clients non-admin
                $clientId = $auth->getClientId();
                
                if ($clientId) {
                    // Dossiers du client
                    $stmt = $db->prepare("
                        SELECT 
                            'dossier' as type,
                            d.id,
                            d.numero_dossier as titre,
                            CONCAT('Type: ', d.type_dossier) as subtitle,
                            CONCAT('mon-dossier.php?id=', d.id) as url,
                            d.statut
                        FROM dossiers d
                        WHERE d.client_id = ?
                        ORDER BY d.date_modification DESC
                        LIMIT 5
                    ");
                    $stmt->execute([$clientId]);
                    $dossiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($dossiers as $dossier) {
                        $results[] = [
                            'type' => 'dossier',
                            'id' => $dossier['id'],
                            'titre' => $dossier['titre'],
                            'subtitle' => $dossier['subtitle'],
                            'url' => url($dossier['url']),
                            'icon' => 'bi-folder',
                            'statut' => $dossier['statut']
                        ];
                    }
                    
                    // Rendez-vous à venir
                    $stmt = $db->prepare("
                        SELECT 
                            'rendez-vous' as type,
                            r.id,
                            CONCAT('RDV - ', DATE_FORMAT(r.date_heure, '%d/%m/%Y')) as titre,
                            CONCAT('Le ', DATE_FORMAT(r.date_heure, '%d/%m/%Y à %H:%i')) as subtitle,
                            CONCAT('mes-rendez-vous.php?id=', r.id) as url
                        FROM rendez_vous r
                        WHERE r.client_id = ? AND r.date_heure >= NOW()
                        ORDER BY r.date_heure ASC
                        LIMIT 5
                    ");
                    $stmt->execute([$clientId]);
                    $rdvs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($rdvs as $rdv) {
                        $results[] = [
                            'type' => 'rendez-vous',
                            'id' => $rdv['id'],
                            'titre' => $rdv['titre'],
                            'subtitle' => $rdv['subtitle'],
                            'url' => url($rdv['url']),
                            'icon' => 'bi-calendar-event'
                        ];
                    }
                }
            }
        } catch (Exception $e) {
            // Silently fail for suggestions
        }
    }
    
    echo json_encode(['results' => $results]);
    exit;
}

$results = [];

try {
    $db = Database::getInstance()->getConnection();
    $searchTerm = '%' . $query . '%';
    
    // Recherche dans les clients
    if ($auth->isAdmin()) {
        $stmt = $db->prepare("
            SELECT 
                'client' as type,
                c.id,
                CONCAT(c.nom, ' ', c.prenom) as titre,
                u.email as subtitle,
                CONCAT('clients.php?action=edit&id=', c.id) as url,
                c.nom,
                c.prenom
            FROM clients c
            INNER JOIN utilisateurs u ON u.id = c.utilisateur_id
            WHERE c.nom LIKE ? OR c.prenom LIKE ? OR u.email LIKE ? OR c.telephone LIKE ?
            ORDER BY c.nom, c.prenom
            LIMIT 5
        ");
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($clients as $client) {
            $results[] = [
                'type' => 'client',
                'id' => $client['id'],
                'titre' => $client['titre'],
                'subtitle' => $client['subtitle'],
                'url' => url($client['url']),
                'icon' => 'bi-person'
            ];
        }
        
        // Recherche dans les dossiers
        $stmt = $db->prepare("
            SELECT 
                'dossier' as type,
                d.id,
                d.numero_dossier as titre,
                CONCAT('Type: ', d.type_dossier, ' - Client: ', c.nom, ' ', c.prenom) as subtitle,
                CONCAT('dossiers.php?action=view&id=', d.id) as url,
                d.statut
            FROM dossiers d
            INNER JOIN clients c ON c.id = d.client_id
            WHERE d.numero_dossier LIKE ? 
               OR d.type_dossier LIKE ?
               OR c.nom LIKE ?
               OR c.prenom LIKE ?
            ORDER BY d.date_modification DESC
            LIMIT 5
        ");
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        $dossiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($dossiers as $dossier) {
            $results[] = [
                'type' => 'dossier',
                'id' => $dossier['id'],
                'titre' => $dossier['titre'],
                'subtitle' => $dossier['subtitle'],
                'url' => url($dossier['url']),
                'icon' => 'bi-folder',
                'statut' => $dossier['statut']
            ];
        }
        
        // Recherche dans les rendez-vous
        $stmt = $db->prepare("
            SELECT 
                'rendez-vous' as type,
                r.id,
                CONCAT('RDV - ', c.nom, ' ', c.prenom) as titre,
                CONCAT('Le ', DATE_FORMAT(r.date_heure, '%d/%m/%Y à %H:%i'), ' - ', r.type_rendez_vous) as subtitle,
                CONCAT('rendez-vous.php?action=view&id=', r.id) as url,
                r.date_heure
            FROM rendez_vous r
            INNER JOIN clients c ON c.id = r.client_id
            WHERE c.nom LIKE ? 
               OR c.prenom LIKE ?
               OR r.type_rendez_vous LIKE ?
            ORDER BY r.date_heure DESC
            LIMIT 5
        ");
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
        $rdvs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($rdvs as $rdv) {
            $results[] = [
                'type' => 'rendez-vous',
                'id' => $rdv['id'],
                'titre' => $rdv['titre'],
                'subtitle' => $rdv['subtitle'],
                'url' => url($rdv['url']),
                'icon' => 'bi-calendar-event'
            ];
        }
    } else {
        // Pour les clients non-admin, recherche uniquement dans leurs propres dossiers et rendez-vous
        $clientId = $auth->getClientId();
        
        if ($clientId) {
            // Dossiers du client
            $stmt = $db->prepare("
                SELECT 
                    'dossier' as type,
                    d.id,
                    d.numero_dossier as titre,
                    CONCAT('Type: ', d.type_dossier) as subtitle,
                    CONCAT('mon-dossier.php?id=', d.id) as url,
                    d.statut
                FROM dossiers d
                WHERE d.client_id = ? 
                  AND (d.numero_dossier LIKE ? OR d.type_dossier LIKE ?)
                ORDER BY d.date_modification DESC
                LIMIT 5
            ");
            $stmt->execute([$clientId, $searchTerm, $searchTerm]);
            $dossiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($dossiers as $dossier) {
                $results[] = [
                    'type' => 'dossier',
                    'id' => $dossier['id'],
                    'titre' => $dossier['titre'],
                    'subtitle' => $dossier['subtitle'],
                    'url' => url($dossier['url']),
                    'icon' => 'bi-folder',
                    'statut' => $dossier['statut']
                ];
            }
            
            // Rendez-vous du client
            $stmt = $db->prepare("
                SELECT 
                    'rendez-vous' as type,
                    r.id,
                    CONCAT('RDV - ', DATE_FORMAT(r.date_heure, '%d/%m/%Y')) as titre,
                    CONCAT('Le ', DATE_FORMAT(r.date_heure, '%d/%m/%Y à %H:%i'), ' - ', r.type_rendez_vous) as subtitle,
                    CONCAT('mes-rendez-vous.php?id=', r.id) as url
                FROM rendez_vous r
                WHERE r.client_id = ? 
                  AND (r.type_rendez_vous LIKE ?)
                ORDER BY r.date_heure DESC
                LIMIT 5
            ");
            $stmt->execute([$clientId, $searchTerm]);
            $rdvs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($rdvs as $rdv) {
                $results[] = [
                    'type' => 'rendez-vous',
                    'id' => $rdv['id'],
                    'titre' => $rdv['titre'],
                    'subtitle' => $rdv['subtitle'],
                    'url' => url($rdv['url']),
                    'icon' => 'bi-calendar-event'
                ];
            }
        }
    }
    
    echo json_encode(['results' => $results]);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Erreur lors de la recherche: ' . $e->getMessage()]);
}









