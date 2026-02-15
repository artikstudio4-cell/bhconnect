<?php
/**
 * Modèle de gestion des clients
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/DossierModel.php';

class ClientModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Obtenir tous les clients (admin seulement)
     */
    public function getAllClients() {
        // Synchronisation automatique : trouver les utilisateurs 'client' qui n'ont pas de profil 'clients'
        $stmt = $this->db->query("
            SELECT u.id 
            FROM utilisateurs u 
            LEFT JOIN clients c ON c.utilisateur_id = u.id 
            WHERE u.role = 'client' AND c.id IS NULL
        ");
        $missing = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($missing as $userId) {
            $this->createClientProfileIfMissing($userId);
        }

        // Récupérer la liste complète
        $stmt = $this->db->query("
            SELECT c.*, u.email, COUNT(d.id) as nb_dossiers
            FROM clients c
            INNER JOIN utilisateurs u ON u.id = c.utilisateur_id
            LEFT JOIN dossiers d ON d.client_id = c.id
            GROUP BY c.id
            ORDER BY c.date_creation DESC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Obtenir un client par ID
     */
    public function getClientById($id) {
        $stmt = $this->db->prepare("
            SELECT c.*, u.email
            FROM clients c
            INNER JOIN utilisateurs u ON u.id = c.utilisateur_id
            WHERE c.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Obtenir un client par ID utilisateur
     * @param int $userId ID de l'utilisateur
     * @param bool $autoCreate Si true, crée automatiquement le profil client s'il n'existe pas
     * @return array|false Données du client ou false
     */
    public function getClientByUserId($userId, $autoCreate = false) {
        $stmt = $this->db->prepare("
            SELECT c.*, u.email
            FROM clients c
            INNER JOIN utilisateurs u ON u.id = c.utilisateur_id
            WHERE c.utilisateur_id = ?
        ");
        $stmt->execute([$userId]);
        $client = $stmt->fetch();
        
        // Si le client n'existe pas et que autoCreate est activé, créer le profil
        if (!$client && $autoCreate) {
            $client = $this->createClientProfileIfMissing($userId);
        }
        
        return $client;
    }
    
    /**
     * Créer automatiquement un profil client si l'utilisateur existe mais n'a pas de profil
     * @param int $userId ID de l'utilisateur
     * @return array|false Données du client créé ou false en cas d'erreur
     */
    public function createClientProfileIfMissing($userId) {
        // Vérifier que l'utilisateur existe et est un client
        $stmt = $this->db->prepare("
            SELECT id, email, role 
            FROM utilisateurs 
            WHERE id = ? AND role = 'client'
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return false;
        }
        
        // Vérifier si le profil client existe déjà (double vérification)
        $stmt = $this->db->prepare("SELECT * FROM clients WHERE utilisateur_id = ?");
        $stmt->execute([$userId]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Le profil existe déjà, le retourner
            return $existing;
        }
        
        // Extraire un nom et prénom basique depuis l'email
        $emailParts = explode('@', $user['email']);
        $nameParts = explode('.', $emailParts[0]);
        $prenom = !empty($nameParts[0]) ? ucfirst($nameParts[0]) : 'Client';
        $nom = !empty($nameParts[1]) ? ucfirst($nameParts[1]) : 'Inconnu';
        
        // Créer le profil client minimal
        try {
            $stmt = $this->db->prepare("
                INSERT INTO clients (utilisateur_id, nom, prenom, agent_id)
                VALUES (?, ?, ?, NULL)
            ");
            $stmt->execute([$userId, $nom, $prenom]);
            $clientId = $this->db->lastInsertId();
            
            // Auto-création du dossier "Initial"
            $dossierModel = new DossierModel();
            $dossierModel->createDossier([
                'client_id' => $clientId,
                'type_dossier' => 'Autre', // Type par défaut
                'created_by' => $userId,   // Créé par le système/utilisateur lui-même (via sync)
                'agent_id' => null
            ]);
            
            // Récupérer le client créé
            return $this->getClientById($clientId);
        } catch (Exception $e) {
            error_log("Erreur création profil client automatique: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Créer un nouveau client
     */
    public function createClient($data) {
        $this->db->beginTransaction();
        
        try {
            // Créer d'abord l'utilisateur
            $stmt = $this->db->prepare("
                INSERT INTO utilisateurs (email, mot_de_passe, role, created_by) 
                VALUES (?, ?, 'client', ?)
            ");
            $createdBy = $data['created_by'] ?? null;
            $stmt->execute([
                $data['email'],
                password_hash($data['mot_de_passe'], PASSWORD_DEFAULT),
                $createdBy
            ]);
            $userId = $this->db->lastInsertId();

            // Créer le client
            $stmt = $this->db->prepare("
                INSERT INTO clients (utilisateur_id, agent_id, nom, prenom, telephone, adresse, date_naissance, nationalite)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $userId,
                $data['agent_id'] ?? null,
                $data['nom'],
                $data['prenom'],
                $data['telephone'] ?? null,
                $data['adresse'] ?? null,
                $data['date_naissance'] ?? null,
                $data['nationalite'] ?? null
            ]);

            $clientId = $this->db->lastInsertId();

            // Auto-création du dossier pour le nouveau client
            try {
                $dossierModel = new DossierModel();
                $dossierModel->createDossier([
                    'client_id' => $clientId,
                    'type_dossier' => 'Autre', // Type par défaut pour l'inscription
                    'created_by' => $userId,   // Utiliser le nouvel utilisateur créé
                    'agent_id' => $data['agent_id'] ?? null
                ]);
            } catch (Exception $e) {
                // Log l'erreur du dossier mais continue (le client est créé)
                error_log("Erreur création dossier auto pour client {$clientId}: " . $e->getMessage());
                // Optionnel: relancer l'exception pour rollback complet
                throw $e;
            }

            $this->db->commit();
            return $clientId;
        } catch (Exception $e) {
            $this->db->rollBack();
            // Re-lancer pour que le caller gère
            throw $e;
        }
    }

    /**
     * Mettre à jour un client
     */
    public function updateClient($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE clients 
            SET nom = ?, prenom = ?, telephone = ?, adresse = ?, date_naissance = ?, nationalite = ?, agent_id = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['nom'],
            $data['prenom'],
            $data['telephone'] ?? null,
            $data['adresse'] ?? null,
            $data['date_naissance'] ?? null,
            $data['nationalite'] ?? null,
            $data['agent_id'] ?? null,
            $id
        ]);
    }
    
    /**
     * Obtenir les clients d'un agent
     */
    public function getClientsByAgent($agentId) {
        $stmt = $this->db->prepare("
            SELECT c.*, u.email
            FROM clients c
            INNER JOIN utilisateurs u ON u.id = c.utilisateur_id
            WHERE c.agent_id = ?
            ORDER BY c.nom, c.prenom
        ");
        $stmt->execute([$agentId]);
        return $stmt->fetchAll();
    }
}


