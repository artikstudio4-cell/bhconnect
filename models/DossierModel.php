<?php
/**
 * Modèle de gestion des dossiers
 */

require_once __DIR__ . '/../config/database.php';

class DossierModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Obtenir tous les dossiers (admin) ou dossiers du client/agent
     */
    public function getDossiers($clientId = null, $agentId = null) {
        $sql = "
            SELECT d.*, c.nom, c.prenom, c.telephone,
                   COUNT(doc.id) as nb_documents,
                   SUM(CASE WHEN doc.statut = 'valide' THEN 1 ELSE 0 END) as nb_documents_valides
            FROM dossiers d
            INNER JOIN clients c ON c.id = d.client_id
            LEFT JOIN documents doc ON doc.dossier_id = d.id
            WHERE 1=1
        ";
        
        $params = [];
        
        if ($clientId) {
            $sql .= " AND d.client_id = ?";
            $params[] = $clientId;
        }
        
        if ($agentId) {
            $sql .= " AND d.agent_id = ?";
            $params[] = $agentId;
        }
        
        $sql .= " GROUP BY d.id ORDER BY d.date_modification DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtenir un dossier par ID
     */
    public function getDossierById($id) {
        $stmt = $this->db->prepare("
            SELECT d.id, d.client_id, d.numero_dossier, d.type_dossier, d.type_dossier as type_procedure, d.statut, 
                   d.date_creation, d.date_modification, d.destination_id, d.progression,
                   c.nom as client_nom, c.prenom as client_prenom, c.telephone as client_telephone, u.email as client_email,
                   dest.pays as destination_pays
            FROM dossiers d
            INNER JOIN clients c ON c.id = d.client_id
            INNER JOIN utilisateurs u ON u.id = c.utilisateur_id
            LEFT JOIN destinations dest ON dest.id = d.destination_id
            WHERE d.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Créer un nouveau dossier
     */
    public function createDossier($data) {
        // Générer un numéro de dossier unique
        $numeroDossier = $this->generateNumeroDossier();
        
        $stmt = $this->db->prepare("
            INSERT INTO dossiers (client_id, numero_dossier, type_dossier, statut)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['client_id'],
            $numeroDossier,
            $data['type_dossier'],
            Constants::DOSSIER_NOUVEAU
        ]);
        
        $dossierId = $this->db->lastInsertId();
        
        // Créer l'entrée initiale dans l'historique
        $this->addHistorique($dossierId, null, Constants::DOSSIER_NOUVEAU, 'Dossier créé', $data['created_by']);
        
        return $dossierId;
    }

    /**
     * Mettre à jour le statut d'un dossier avec historique
     */
    public function updateStatut($dossierId, $nouveauStatut, $commentaire, $userId, $progression = null) {
        $this->db->beginTransaction();
        
        try {
            // Récupérer l'ancien statut
            $stmt = $this->db->prepare("SELECT statut FROM dossiers WHERE id = ?");
            $stmt->execute([$dossierId]);
            $ancienStatut = $stmt->fetchColumn();
            
            // Mettre à jour le statut et la progression
            $stmt = $this->db->prepare("
                UPDATE dossiers 
                SET statut = ?, progression = COALESCE(?, progression), date_modification = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$nouveauStatut, $progression, $dossierId]);
            
            // Ajouter à l'historique
            $this->addHistorique($dossierId, $ancienStatut, $nouveauStatut, $commentaire, $userId);
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Ajouter une entrée dans l'historique
     */
    public function addHistorique($dossierId, $statutAncien, $statutNouveau, $commentaire, $userId) {
        $stmt = $this->db->prepare("
            INSERT INTO historique_dossier (dossier_id, statut_ancien, statut_nouveau, commentaire, modifie_par)
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $dossierId,
            $statutAncien,
            $statutNouveau,
            $commentaire,
            $userId
        ]);
    }

    /**
     * Obtenir l'historique d'un dossier
     */
    public function getHistorique($dossierId) {
        try {
            $stmt = $this->db->prepare("
                SELECT h.*, u.email as modifie_par_email
                FROM historique_dossier h
                LEFT JOIN utilisateurs u ON u.id = h.modifie_par
                WHERE h.dossier_id = ?
                ORDER BY h.date_modification DESC
            ");
            $stmt->execute([$dossierId]);
            $result = $stmt->fetchAll();
            return is_array($result) ? $result : [];
        } catch (Exception $e) {
            error_log("Erreur getHistorique: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Générer un numéro de dossier unique
     */
    private function generateNumeroDossier() {
        $prefix = 'DOS-' . date('Y');
        $stmt = $this->db->query("SELECT COUNT(*) FROM dossiers WHERE numero_dossier LIKE '$prefix%'");
        $count = $stmt->fetchColumn();
        return $prefix . '-' . str_pad($count + 1, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Obtenir les statistiques des dossiers
     */
    public function getStatistiques() {
        $stats = [];
        
        // Nombre de dossiers par statut
        $stmt = $this->db->query("
            SELECT statut, COUNT(*) as nombre
            FROM dossiers
            GROUP BY statut
        ");
        $stats['par_statut'] = $stmt->fetchAll();
        
        // Dossiers incomplets (en attente de documents ou analyse)
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as nombre
            FROM dossiers
            WHERE statut IN (?, ?, ?)
        ");
        $stmt->execute([
            Constants::DOSSIER_NOUVEAU,
            Constants::DOSSIER_ANALYSE_PRELIMINAIRE,
            Constants::DOSSIER_CONSTITUTION
        ]);
        $stats['incomplets'] = $stmt->fetchColumn();
        
        // Total de dossiers
        $stmt = $this->db->query("SELECT COUNT(*) FROM dossiers");
        $stats['total'] = $stmt->fetchColumn();
        
        return $stats;
    }
}


