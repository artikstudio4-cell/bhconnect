<?php
/**
 * Modèle de gestion des documents
 */

require_once __DIR__ . '/../config/database.php';

class DocumentModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Obtenir tous les documents d'un dossier
     */
    public function getDocumentsByDossier($dossierId) {
        try {
            $stmt = $this->db->prepare("
                SELECT d.*, u.email as valide_par_email
                FROM documents d
                LEFT JOIN utilisateurs u ON u.id = d.valide_par
                WHERE d.dossier_id = ?
                ORDER BY d.date_upload DESC
            ");
            $stmt->execute([$dossierId]);
            $result = $stmt->fetchAll();
            return is_array($result) ? $result : [];
        } catch (Exception $e) {
            error_log("Erreur getDocumentsByDossier: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtenir un document par ID
     */
    public function getDocumentById($id) {
        $stmt = $this->db->prepare("
            SELECT d.*, dos.client_id
            FROM documents d
            INNER JOIN dossiers dos ON dos.id = d.dossier_id
            WHERE d.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Ajouter un document
     */
    public function addDocument($dossierId, $nomFichier, $cheminFichier, $typeDocument, $tailleFichier) {
        $stmt = $this->db->prepare("
            INSERT INTO documents (dossier_id, nom_fichier, chemin_fichier, type_document, taille_fichier, statut)
            VALUES (?, ?, ?, ?, ?, 'en_attente')
        ");
        return $stmt->execute([
            $dossierId,
            $nomFichier,
            $cheminFichier,
            $typeDocument,
            $tailleFichier
        ]);
    }

    /**
     * Valider ou rejeter un document
     */
    public function updateStatutDocument($id, $statut, $userId) {
        $stmt = $this->db->prepare("
            UPDATE documents 
            SET statut = ?, valide_par = ?, date_validation = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([
            $statut,
            $userId,
            $id
        ]);
    }

    /**
     * Supprimer un document
     */
    public function deleteDocument($id) {
        // Récupérer le chemin du fichier
        $stmt = $this->db->prepare("SELECT chemin_fichier FROM documents WHERE id = ?");
        $stmt->execute([$id]);
        $document = $stmt->fetch();
        
        if ($document && file_exists($document['chemin_fichier'])) {
            unlink($document['chemin_fichier']);
        }
        
        $stmt = $this->db->prepare("DELETE FROM documents WHERE id = ?");
        return $stmt->execute([$id]);
    }
}


