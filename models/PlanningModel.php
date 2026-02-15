<?php
/**
 * Modèle de gestion des plannings RDV
 */
require_once __DIR__ . '/../config/database.php';

class PlanningModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Obtenir tous les plannings
     */
    public function getAllPlannings($agentId = null) {
        if ($agentId) {
            $stmt = $this->db->prepare("
                SELECT p.*, a.nom as agent_nom, a.prenom as agent_prenom,
                       u.email as created_by_email
                FROM plannings p
                LEFT JOIN agents a ON a.id = p.agent_id
                LEFT JOIN utilisateurs u ON u.id = p.created_by
                WHERE p.agent_id = ? OR p.agent_id IS NULL
                ORDER BY p.jour_semaine, p.heure_debut
            ");
            $stmt->execute([$agentId]);
        } else {
            $stmt = $this->db->query("
                SELECT p.*, a.nom as agent_nom, a.prenom as agent_prenom,
                       u.email as created_by_email
                FROM plannings p
                LEFT JOIN agents a ON a.id = p.agent_id
                LEFT JOIN utilisateurs u ON u.id = p.created_by
                ORDER BY p.jour_semaine, p.heure_debut
            ");
        }
        return $stmt->fetchAll();
    }

    /**
     * Obtenir un planning par ID
     */
    public function getPlanningById($id) {
        $stmt = $this->db->prepare("
            SELECT p.*, a.nom as agent_nom, a.prenom as agent_prenom
            FROM plannings p
            LEFT JOIN agents a ON a.id = p.agent_id
            WHERE p.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Créer un planning
     */
    public function createPlanning($data, $createdBy) {
        $stmt = $this->db->prepare("
            INSERT INTO plannings (agent_id, jour_semaine, heure_debut, heure_fin, duree_creneau, actif, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['agent_id'] ?? null,
            $data['jour_semaine'],
            $data['heure_debut'],
            $data['heure_fin'],
            $data['duree_creneau'] ?? 30,
            $data['actif'] ?? true,
            $createdBy
        ]);
    }

    /**
     * Mettre à jour un planning
     */
    public function updatePlanning($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE plannings 
            SET agent_id = ?, jour_semaine = ?, heure_debut = ?, heure_fin = ?, 
                duree_creneau = ?, actif = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['agent_id'] ?? null,
            $data['jour_semaine'],
            $data['heure_debut'],
            $data['heure_fin'],
            $data['duree_creneau'] ?? 30,
            $data['actif'] ?? true,
            $id
        ]);
    }

    /**
     * Supprimer un planning
     */
    public function deletePlanning($id) {
        $stmt = $this->db->prepare("DELETE FROM plannings WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Générer les créneaux disponibles pour une période
     */
    public function generateCreneaux($dateDebut, $dateFin) {
        // Récupérer tous les plannings actifs
        $plannings = $this->getAllPlannings();
        
        $creneauxGeneres = 0;
        
        foreach ($plannings as $planning) {
            $date = new DateTime($dateDebut);
            $dateFinObj = new DateTime($dateFin);
            
            while ($date <= $dateFinObj) {
                $jourSemaine = (int)$date->format('N'); // 1=Lundi, 7=Dimanche
                
                if ($planning['jour_semaine'] == $jourSemaine && $planning['actif']) {
                    // Générer les créneaux pour ce jour
                    $heureDebut = new DateTime($date->format('Y-m-d') . ' ' . $planning['heure_debut']);
                    $heureFin = new DateTime($date->format('Y-m-d') . ' ' . $planning['heure_fin']);
                    $dureeCreneau = $planning['duree_creneau'];
                    
                    $creneauActuel = clone $heureDebut;
                    
                    while ($creneauActuel < $heureFin) {
                        $creneauFin = clone $creneauActuel;
                        $creneauFin->modify("+{$dureeCreneau} minutes");
                        
                        // Vérifier si le créneau existe déjà
                        $stmt = $this->db->prepare("
                            SELECT id FROM creneaux_disponibles
                            WHERE planning_id = ? 
                            AND agent_id <=> ?
                            AND date_creneau = ?
                            AND heure_debut = ?
                        ");
                        $stmt->execute([
                            $planning['id'],
                            $planning['agent_id'],
                            $date->format('Y-m-d'),
                            $creneauActuel->format('H:i:s')
                        ]);
                        
                        if (!$stmt->fetch()) {
                            // Créer le créneau
                            $stmt = $this->db->prepare("
                                INSERT INTO creneaux_disponibles 
                                (planning_id, agent_id, date_creneau, heure_debut, heure_fin, disponible)
                                VALUES (?, ?, ?, ?, ?, 1)
                            ");
                            $stmt->execute([
                                $planning['id'],
                                $planning['agent_id'],
                                $date->format('Y-m-d'),
                                $creneauActuel->format('H:i:s'),
                                $creneauFin->format('H:i:s')
                            ]);
                            $creneauxGeneres++;
                        }
                        
                        $creneauActuel->modify("+{$dureeCreneau} minutes");
                    }
                }
                
                $date->modify('+1 day');
            }
        }
        
        return $creneauxGeneres;
    }
}


