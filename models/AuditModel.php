<?php
/**
 * Modèle de gestion des logs d'audit (admin uniquement)
 */
require_once __DIR__ . '/../config/database.php';

class AuditModel {
    private $db;
    private $tableExists = null;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        // Vérifier si la table audit_logs existe
        $this->checkTableExists();
    }
    
    /**
     * Vérifier si la table audit_logs existe
     */
    private function checkTableExists() {
        try {
            $stmt = $this->db->query("SHOW TABLES LIKE 'audit_logs'");
            $this->tableExists = $stmt->rowCount() > 0;
        } catch (Exception $e) {
            $this->tableExists = false;
        }
    }
    
    /**
     * Vérifier si on peut utiliser les fonctionnalités d'audit
     */
    private function canUseAudit() {
        return $this->tableExists === true;
    }

    /**
     * Obtenir les logs d'audit
     */
    public function getLogs($filters = [], $limit = 100, $offset = 0) {
        if (!$this->canUseAudit()) {
            return [];
        }
        
        try {
            $sql = "
                SELECT al.*, 
                       u.email as utilisateur_email,
                       u.role as utilisateur_role
                FROM audit_logs al
                INNER JOIN utilisateurs u ON u.id = al.utilisateur_id
                WHERE 1=1
            ";
            
            $params = [];
            
            if (!empty($filters['utilisateur_id'])) {
                $sql .= " AND al.utilisateur_id = ?";
                $params[] = $filters['utilisateur_id'];
            }
            
            if (!empty($filters['action'])) {
                $sql .= " AND al.action = ?";
                $params[] = $filters['action'];
            }
            
            if (!empty($filters['table_affectee'])) {
                $sql .= " AND al.table_affectee = ?";
                $params[] = $filters['table_affectee'];
            }
            
            if (!empty($filters['date_debut'])) {
                $sql .= " AND al.date_action >= ?";
                $params[] = $filters['date_debut'];
            }
            
            if (!empty($filters['date_fin'])) {
                $sql .= " AND al.date_action <= ?";
                $params[] = $filters['date_fin'];
            }
            
            $sql .= " ORDER BY al.date_action DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Erreur AuditModel::getLogs: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtenir les statistiques d'audit
     */
    public function getStats($dateDebut = null, $dateFin = null) {
        if (!$this->canUseAudit()) {
            return [
                'total_actions' => 0,
                'nb_utilisateurs' => 0,
                'nb_actions_differentes' => 0,
                'nb_tables_affectees' => 0
            ];
        }
        
        try {
            $sql = "
                SELECT 
                    COUNT(*) as total_actions,
                    COUNT(DISTINCT utilisateur_id) as nb_utilisateurs,
                    COUNT(DISTINCT action) as nb_actions_differentes,
                    COUNT(DISTINCT table_affectee) as nb_tables_affectees
                FROM audit_logs
                WHERE 1=1
            ";
            
            $params = [];
            
            if ($dateDebut) {
                $sql .= " AND date_action >= ?";
                $params[] = $dateDebut;
            }
            
            if ($dateFin) {
                $sql .= " AND date_action <= ?";
                $params[] = $dateFin;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            
            // S'assurer que tous les champs sont présents
            return [
                'total_actions' => $result['total_actions'] ?? 0,
                'nb_utilisateurs' => $result['nb_utilisateurs'] ?? 0,
                'nb_actions_differentes' => $result['nb_actions_differentes'] ?? 0,
                'nb_tables_affectees' => $result['nb_tables_affectees'] ?? 0
            ];
        } catch (Exception $e) {
            error_log("Erreur AuditModel::getStats: " . $e->getMessage());
            return [
                'total_actions' => 0,
                'nb_utilisateurs' => 0,
                'nb_actions_differentes' => 0,
                'nb_tables_affectees' => 0
            ];
        }
    }

    /**
     * Obtenir les actions les plus fréquentes
     */
    public function getTopActions($limit = 10) {
        if (!$this->canUseAudit()) {
            return [];
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT action, COUNT(*) as nombre
                FROM audit_logs
                GROUP BY action
                ORDER BY nombre DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Erreur AuditModel::getTopActions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtenir les utilisateurs les plus actifs
     */
    public function getTopUsers($limit = 10) {
        if (!$this->canUseAudit()) {
            return [];
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT u.email, u.role, COUNT(*) as nombre_actions
                FROM audit_logs al
                INNER JOIN utilisateurs u ON u.id = al.utilisateur_id
                GROUP BY al.utilisateur_id
                ORDER BY nombre_actions DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Erreur AuditModel::getTopUsers: " . $e->getMessage());
            return [];
        }
    }
}


