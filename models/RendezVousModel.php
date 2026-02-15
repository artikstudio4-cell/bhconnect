<?php
/**
 * Modèle de gestion des rendez-vous
 */

require_once __DIR__ . '/../config/database.php';

class RendezVousModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Obtenir tous les rendez-vous (admin) ou d'un client
     */
    public function getRendezVous($clientId = null) {
        if ($clientId) {
            $stmt = $this->db->prepare("
                SELECT r.*, c.nom, c.prenom, d.numero_dossier
                FROM rendez_vous r
                INNER JOIN clients c ON c.id = r.client_id
                LEFT JOIN dossiers d ON d.id = r.dossier_id
                WHERE r.client_id = ?
                ORDER BY r.date_heure DESC
            ");
            $stmt->execute([$clientId]);
        } else {
            $stmt = $this->db->query("
                SELECT r.*, c.nom, c.prenom, d.numero_dossier
                FROM rendez_vous r
                INNER JOIN clients c ON c.id = r.client_id
                LEFT JOIN dossiers d ON d.id = r.dossier_id
                ORDER BY r.date_heure DESC
            ");
        }
        return $stmt->fetchAll();
    }

    /**
     * Obtenir les rendez-vous à venir
     */
    public function getRendezVousAVenir($clientId = null, $limit = 10, $agentId = null) {
        $sql = "
            SELECT r.*, c.nom, c.prenom, d.numero_dossier
            FROM rendez_vous r
            INNER JOIN clients c ON c.id = r.client_id
            LEFT JOIN dossiers d ON d.id = r.dossier_id
            WHERE r.date_heure >= NOW() AND r.statut NOT IN ('annule', 'refuse')
        ";
        
        $params = [];
        
        if ($clientId) {
            $sql .= " AND r.client_id = ?";
            $params[] = $clientId;
        }
        
        if ($agentId) {
            $sql .= " AND r.agent_id = ?";
            $params[] = $agentId;
        }
        
        $sql .= " ORDER BY r.date_heure ASC LIMIT ?";
        $params[] = $limit;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtenir un rendez-vous par ID
     */
    public function getRendezVousById($id) {
        $stmt = $this->db->prepare("
            SELECT r.*, c.nom, c.prenom, d.numero_dossier
            FROM rendez_vous r
            INNER JOIN clients c ON c.id = r.client_id
            LEFT JOIN dossiers d ON d.id = r.dossier_id
            WHERE r.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Créer un nouveau rendez-vous
     */
    public function createRendezVous($data) {
        $stmt = $this->db->prepare("
            INSERT INTO rendez_vous (client_id, dossier_id, date_heure, type_rendez_vous, notes, statut)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['client_id'],
            $data['dossier_id'] ?? null,
            $data['date_heure'],
            $data['type_rendez_vous'],
            $data['notes'] ?? null,
            Constants::RDV_PLANIFIE
        ]);
    }

    /**
     * Mettre à jour un rendez-vous
     */
    public function updateRendezVous($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE rendez_vous 
            SET date_heure = ?, type_rendez_vous = ?, notes = ?, statut = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['date_heure'],
            $data['type_rendez_vous'],
            $data['notes'] ?? null,
            $data['statut'],
            $id
        ]);
    }

    /**
     * Supprimer un rendez-vous
     */
    public function deleteRendezVous($id) {
        $stmt = $this->db->prepare("DELETE FROM rendez_vous WHERE id = ?");
        return $stmt->execute([$id]);
    }
}


