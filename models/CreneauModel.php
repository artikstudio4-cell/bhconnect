    
<?php
/**
 * Modèle de gestion des créneaux disponibles
 */
require_once __DIR__ . '/../config/database.php';

class CreneauModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Ajouter un créneau manuellement
     */
    public function addCreneau($planning_id, $agent_id, $date_creneau, $heure_debut, $heure_fin, $duree, $note, $disponible) {
        // On ignore planning_id, on ne l'utilise plus
        $stmt = $this->db->prepare("INSERT INTO creneaux_disponibles (agent_id, date_creneau, heure_debut, heure_fin, duree, note, disponible) VALUES (?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$agent_id, $date_creneau, $heure_debut, $heure_fin, $duree, $note, $disponible]);
    }

    /**
     * Obtenir les créneaux disponibles pour une période
     */
    public function getCreneauxDisponibles($dateDebut, $dateFin, $agentId = null) {
        if ($agentId) {
            $stmt = $this->db->prepare("
                SELECT c.*, a.nom as agent_nom, a.prenom as agent_prenom
                FROM creneaux_disponibles c
                LEFT JOIN agents a ON a.id = c.agent_id
                WHERE c.date_creneau BETWEEN ? AND ?
                AND c.disponible = 1
                AND (c.agent_id = ? OR c.agent_id IS NULL)
                ORDER BY c.date_creneau, c.heure_debut
            ");
            $stmt->execute([$dateDebut, $dateFin, $agentId]);
        } else {
            $stmt = $this->db->prepare("
                SELECT c.*, a.nom as agent_nom, a.prenom as agent_prenom
                FROM creneaux_disponibles c
                LEFT JOIN agents a ON a.id = c.agent_id
                WHERE c.date_creneau BETWEEN ? AND ?
                AND c.disponible = 1
                ORDER BY c.date_creneau, c.heure_debut
            ");
            $stmt->execute([$dateDebut, $dateFin]);
        }
        return $stmt->fetchAll();
    }

    /**
     * Réserver un créneau (créer un RDV)
     */
    public function reserverCreneau($creneauId, $clientId, $typeRdv, $createdBy, $dossierId = null, $notes = null) {
        $this->db->beginTransaction();
        
        try {
            // Récupérer le créneau
            $stmt = $this->db->prepare("SELECT * FROM creneaux_disponibles WHERE id = ? AND disponible = 1");
            $stmt->execute([$creneauId]);
            $creneau = $stmt->fetch();
            
            if (!$creneau) {
                throw new Exception("Créneau non disponible");
            }
            
            // Récupérer l'agent si le créneau est assigné à un agent
            $agentId = $creneau['agent_id'];
            
            // Créer le RDV
            $dateHeure = $creneau['date_creneau'] . ' ' . $creneau['heure_debut'];
            $stmt = $this->db->prepare("
                INSERT INTO rendez_vous 
                (client_id, agent_id, dossier_id, date_heure, type_rendez_vous, notes, statut, created_by)
                VALUES (?, ?, ?, ?, ?, ?, 'planifie', ?)
            ");
            $stmt->execute([
                $clientId,
                $agentId,
                $dossierId,
                $dateHeure,
                $typeRdv,
                $notes,
                $createdBy
            ]);
            $rdvId = $this->db->lastInsertId();
            
            // Marquer le créneau comme non disponible
            $stmt = $this->db->prepare("
                UPDATE creneaux_disponibles 
                SET disponible = 0 
                WHERE id = ?
            ");
            $stmt->execute([$creneauId]);
            
            $this->db->commit();
            return $rdvId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Libérer un créneau (si RDV annulé)
     */
    public function libererCreneau($rdvId) {
        // Récupérer le RDV
        $stmt = $this->db->prepare("SELECT * FROM rendez_vous WHERE id = ?");
        $stmt->execute([$rdvId]);
        $rdv = $stmt->fetch();
        
        if (!$rdv) {
            return false;
        }
        
        // Trouver le créneau correspondant
        $dateRdv = date('Y-m-d', strtotime($rdv['date_heure']));
        $heureRdv = date('H:i:s', strtotime($rdv['date_heure']));
        
        $stmt = $this->db->prepare("
            UPDATE creneaux_disponibles 
            SET disponible = 1 
            WHERE date_creneau = ? 
            AND heure_debut = ?
            AND (agent_id <=> ?)
        ");
        return $stmt->execute([$dateRdv, $heureRdv, $rdv['agent_id']]);
    }
    /**
     * Récupérer tous les créneaux (pour l'admin)
     */
    public function getAllCreneaux() {
        $stmt = $this->db->query("
            SELECT c.*, a.nom as agent_nom, a.prenom as agent_prenom
            FROM creneaux_disponibles c
            LEFT JOIN agents a ON a.id = c.agent_id
            ORDER BY c.date_creneau, c.heure_debut
        ");
        return $stmt->fetchAll();
    }

    /**
     * Mettre à jour un créneau (note, durée, disponibilité)
     */
    public function updateCreneau($id, $note, $duree, $disponible) {
        $stmt = $this->db->prepare("UPDATE creneaux_disponibles SET note = ?, duree = ?, disponible = ? WHERE id = ?");
        return $stmt->execute([$note, $duree, $disponible, $id]);
    }

    /**
     * Supprimer un créneau
     */
    public function deleteCreneau($id) {
        $stmt = $this->db->prepare("DELETE FROM creneaux_disponibles WHERE id = ?");
        return $stmt->execute([$id]);
    }

    //supprimer tous les créneaux
     public function deleteAllCreneaux() {
        $stmt = $this->db->prepare("DELETE FROM creneaux_disponibles");
        return $stmt->execute();
    }
}

