<?php
/**
 * Modèle de gestion des agents
 */
require_once __DIR__ . '/../config/database.php';

class AgentModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Obtenir tous les agents
     */
    public function getAllAgents() {
        $stmt = $this->db->query("
            SELECT a.*, u.email, u.actif, u.date_creation, u.derniere_connexion
            FROM agents a
            INNER JOIN utilisateurs u ON u.id = a.utilisateur_id
            ORDER BY a.nom, a.prenom
        ");
        return $stmt->fetchAll();
    }

    /**
     * Obtenir un agent par ID
     */
    public function getAgentById($id) {
        $stmt = $this->db->prepare("
            SELECT a.*, u.email, u.actif, u.date_creation, u.derniere_connexion
            FROM agents a
            INNER JOIN utilisateurs u ON u.id = a.utilisateur_id
            WHERE a.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Obtenir un agent par ID utilisateur
     */
    public function getAgentByUserId($userId) {
        $stmt = $this->db->prepare("
            SELECT a.*, u.email, u.actif
            FROM agents a
            INNER JOIN utilisateurs u ON u.id = a.utilisateur_id
            WHERE a.utilisateur_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    /**
     * Créer un nouvel agent (par admin)
     */
    public function createAgent($data, $createdBy) {
        $this->db->beginTransaction();
        
        try {
            // Créer l'utilisateur
            $stmt = $this->db->prepare("
                INSERT INTO utilisateurs (email, mot_de_passe, role, created_by)
                VALUES (?, ?, 'agent', ?)
            ");
            $hashedPassword = password_hash($data['mot_de_passe'], PASSWORD_DEFAULT);
            $stmt->execute([
                $data['email'],
                $hashedPassword,
                $createdBy
            ]);
            $userId = $this->db->lastInsertId();

            // Créer l'agent
            $stmt = $this->db->prepare("
                INSERT INTO agents (utilisateur_id, nom, prenom, telephone, specialite)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $userId,
                $data['nom'],
                $data['prenom'],
                $data['telephone'] ?? null,
                $data['specialite'] ?? null
            ]);
            $agentId = $this->db->lastInsertId();

            $this->db->commit();
            return $agentId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Mettre à jour un agent
     */
    public function updateAgent($id, $data) {
        $this->db->beginTransaction();
        
        try {
            // Mettre à jour l'agent
            $stmt = $this->db->prepare("
                UPDATE agents 
                SET nom = ?, prenom = ?, telephone = ?, specialite = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $data['nom'],
                $data['prenom'],
                $data['telephone'] ?? null,
                $data['specialite'] ?? null,
                $id
            ]);

            // Mettre à jour l'email si fourni
            if (isset($data['email'])) {
                $agent = $this->getAgentById($id);
                $stmt = $this->db->prepare("
                    UPDATE utilisateurs 
                    SET email = ?
                    WHERE id = ?
                ");
                $stmt->execute([$data['email'], $agent['utilisateur_id']]);
            }

            // Mettre à jour le mot de passe si fourni
            if (!empty($data['mot_de_passe'])) {
                $agent = $this->getAgentById($id);
                $hashedPassword = password_hash($data['mot_de_passe'], PASSWORD_DEFAULT);
                $stmt = $this->db->prepare("
                    UPDATE utilisateurs 
                    SET mot_de_passe = ?
                    WHERE id = ?
                ");
                $stmt->execute([$hashedPassword, $agent['utilisateur_id']]);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Supprimer un agent (désactiver l'utilisateur)
     */
    public function deleteAgent($id) {
        $agent = $this->getAgentById($id);
        if (!$agent) {
            return false;
        }

        $stmt = $this->db->prepare("
            UPDATE utilisateurs 
            SET actif = 0 
            WHERE id = ?
        ");
        return $stmt->execute([$agent['utilisateur_id']]);
    }

    /**
     * Obtenir les statistiques d'un agent
     */
    public function getStatistiques($agentId) {
        $stats = [];

        // Nombre de clients
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM clients WHERE agent_id = ?");
        $stmt->execute([$agentId]);
        $stats['total_clients'] = $stmt->fetchColumn();

        // Nombre de dossiers
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM dossiers WHERE agent_id = ?");
        $stmt->execute([$agentId]);
        $stats['total_dossiers'] = $stmt->fetchColumn();

        // Dossiers en cours
        $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM dossiers 
            WHERE agent_id = ? 
            AND statut NOT IN (?, ?, ?)
        ");
        $stmt->execute([
            $agentId, 
            Constants::DOSSIER_VISA_ACCORDE, 
            Constants::DOSSIER_VISA_REFUSE, 
            Constants::DOSSIER_CLOTURE
        ]);
        $stats['dossiers_en_cours'] = $stmt->fetchColumn();

        // RDV à venir
        $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM rendez_vous 
            WHERE agent_id = ? 
            AND date_heure >= NOW() 
            AND statut IN (?, ?)
        ");
        $stmt->execute([
            $agentId,
            Constants::RDV_PLANIFIE,
            Constants::RDV_CONFIRME
        ]);
        $stats['rdv_a_venir'] = $stmt->fetchColumn();

        return $stats;
    }

    /**
     * Obtenir les clients d'un agent
     */
    public function getClientsByAgent($agentId) {
        $stmt = $this->db->prepare("
            SELECT c.*, u.email,
                   COUNT(DISTINCT d.id) as nb_dossiers,
                   COUNT(DISTINCT r.id) as nb_rdv
            FROM clients c
            INNER JOIN utilisateurs u ON u.id = c.utilisateur_id
            LEFT JOIN dossiers d ON d.client_id = c.id
            LEFT JOIN rendez_vous r ON r.client_id = c.id
            WHERE c.agent_id = ?
            GROUP BY c.id
            ORDER BY c.nom, c.prenom
        ");
        $stmt->execute([$agentId]);
        return $stmt->fetchAll();
    }
}


