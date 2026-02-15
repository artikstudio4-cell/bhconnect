<?php
/**
 * Modèle de gestion de la messagerie interne
 */
require_once __DIR__ . '/../config/database.php';

class MessageModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Envoyer un message
     */
    public function sendMessage($expediteurId, $destinataireId, $sujet, $contenu, $piecesJointes = []) {
        $this->db->beginTransaction();
        
        try {
            // Créer le message
            $stmt = $this->db->prepare("
                INSERT INTO messages (expediteur_id, destinataire_id, sujet, contenu)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$expediteurId, $destinataireId, $sujet, $contenu]);
            $messageId = $this->db->lastInsertId();
            
            // Ajouter les pièces jointes
            foreach ($piecesJointes as $pj) {
                $stmt = $this->db->prepare("
                    INSERT INTO message_pieces_jointes (message_id, nom_fichier, chemin_fichier, taille_fichier)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $messageId,
                    $pj['nom_fichier'],
                    $pj['chemin_fichier'],
                    $pj['taille_fichier']
                ]);
            }
            
            // Créer une notification
            // Si le destinataire est NULL (Cabinet), on ne crée pas de notif individuelle ici (ou on pourrait notifier tous les agents)
            // Pour l'instant on garde la notif seulement si destinataire précis
            if ($destinataireId) {
                $this->createNotification($destinataireId, 'message', 'Nouveau message', "Vous avez reçu un nouveau message de " . $this->getUserEmail($expediteurId), url('messages.php?id=' . $messageId));
            }
            
            $this->db->commit();
            return $messageId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Obtenir les messages d'un utilisateur
     */
    public function getMessages($userId, $type = 'received', $role = 'client') {
        if ($type === 'received') {
            $sql = "
                SELECT m.*, 
                       u1.email as expediteur_email,
                       u1.role as expediteur_role,
                       CASE 
                           WHEN u1.role = 'client' THEN CONCAT(c1.nom, ' ', c1.prenom)
                           WHEN u1.role = 'agent' THEN CONCAT(a1.nom, ' ', a1.prenom)
                           ELSE 'Admin'
                       END as expediteur_nom
                FROM messages m
                INNER JOIN utilisateurs u1 ON u1.id = m.expediteur_id
                LEFT JOIN clients c1 ON c1.utilisateur_id = u1.id
                LEFT JOIN agents a1 ON a1.utilisateur_id = u1.id
                WHERE m.destinataire_id = ?
            ";

            // Si c'est un agent ou admin, ils voient aussi les messages adressés au "Cabinet" (NULL)
            if ($role === 'agent' || $role === 'admin') {
                $sql .= " OR m.destinataire_id IS NULL";
            }

            $sql .= " ORDER BY m.date_envoi DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
        } else {
            $stmt = $this->db->prepare("
                SELECT m.*, 
                       u2.email as destinataire_email,
                       u2.role as destinataire_role,
                       CASE 
                           WHEN u2.role = 'client' THEN CONCAT(c2.nom, ' ', c2.prenom)
                           WHEN u2.role = 'agent' THEN CONCAT(a2.nom, ' ', a2.prenom)
                           ELSE 'Admin'
                       END as destinataire_nom
                FROM messages m
                LEFT JOIN utilisateurs u2 ON u2.id = m.destinataire_id
                LEFT JOIN clients c2 ON c2.utilisateur_id = u2.id
                LEFT JOIN agents a2 ON a2.utilisateur_id = u2.id
                WHERE m.expediteur_id = ?
                ORDER BY m.date_envoi DESC
            ");
            $stmt->execute([$userId]);
        }
        return $stmt->fetchAll();
    }

    /**
     * Obtenir un message par ID
     */
    public function getMessageById($id, $userId) {
        $stmt = $this->db->prepare("
            SELECT m.*, 
                   u1.email as expediteur_email,
                   u2.email as destinataire_email
            FROM messages m
            INNER JOIN utilisateurs u1 ON u1.id = m.expediteur_id
            INNER JOIN utilisateurs u2 ON u2.id = m.destinataire_id
            WHERE m.id = ? AND (m.expediteur_id = ? OR m.destinataire_id = ?)
        ");
        $stmt->execute([$id, $userId, $userId]);
        return $stmt->fetch();
    }

    /**
     * Marquer un message comme lu
     */
    public function markAsRead($messageId, $userId) {
        $stmt = $this->db->prepare("
            UPDATE messages 
            SET lu = 1 
            WHERE id = ? AND destinataire_id = ?
        ");
        return $stmt->execute([$messageId, $userId]);
    }

    /**
     * Obtenir le nombre de messages non lus
     */
    public function getUnreadCount($userId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM messages 
            WHERE destinataire_id = ? AND lu = 0
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }

    /**
     * Obtenir les pièces jointes d'un message
     */
    public function getPiecesJointes($messageId) {
        $stmt = $this->db->prepare("
            SELECT * FROM message_pieces_jointes 
            WHERE message_id = ?
        ");
        $stmt->execute([$messageId]);
        return $stmt->fetchAll();
    }

    /**
     * Créer une notification (helper)
     */
    private function createNotification($userId, $type, $titre, $message, $lien = null) {
        $stmt = $this->db->prepare("
            INSERT INTO notifications (utilisateur_id, type, titre, message, lien)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $type, $titre, $message, $lien]);
    }

    /**
     * Obtenir l'email d'un utilisateur (helper)
     */
    private function getUserEmail($userId) {
        $stmt = $this->db->prepare("SELECT email FROM utilisateurs WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        return $user ? $user['email'] : '';
    }
}


