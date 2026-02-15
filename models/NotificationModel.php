<?php
/**
 * Modèle de gestion des notifications
 */
require_once __DIR__ . '/../config/database.php';

class NotificationModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Créer une notification
     * @param int $userId ID de l'utilisateur
     * @param string $type Type de notification
     * @param string $titre Titre de la notification
     * @param string $message Message de la notification
     * @param string|null $lien Lien vers la ressource
     * @param bool $sendEmail Si true, envoie également un email
     * @return int|false ID de la notification créée ou false en cas d'erreur
     */
    public function create($userId, $type, $titre, $message, $lien = null, $sendEmail = false) {
        $this->db->beginTransaction();
        
        try {
            // Créer la notification
            $stmt = $this->db->prepare("
                INSERT INTO notifications (utilisateur_id, type, titre, message, lien, email_envoye)
                VALUES (?, ?, ?, ?, ?, 0)
            ");
            $stmt->execute([$userId, $type, $titre, $message, $lien]);
            $notificationId = $this->db->lastInsertId();
            
            // Envoyer l'email si demandé
            if ($sendEmail) {
                $this->sendEmailNotification($userId, $titre, $message, $lien, $notificationId);
            }
            
            $this->db->commit();
            return $notificationId;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Erreur création notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Envoyer une notification par email
     */
    private function sendEmailNotification($userId, $titre, $message, $lien, $notificationId) {
        // Vérifier si l'envoi d'emails est activé
        if (!defined('EMAIL_ENABLED') || !EMAIL_ENABLED) {
            return false;
        }
        
        try {
            // Récupérer l'email de l'utilisateur
            $stmt = $this->db->prepare("SELECT email FROM utilisateurs WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user || empty($user['email'])) {
                return false;
            }
            
            // Vérifier les préférences de l'utilisateur (si table preferences existe)
            // Pour l'instant, on envoie toujours
            
            require_once __DIR__ . '/EmailService.php';
            $emailService = new EmailService();
            $sent = $emailService->sendNotification($user['email'], $titre, $message, $lien);
            
            // Marquer l'email comme envoyé
            if ($sent) {
                $stmt = $this->db->prepare("
                    UPDATE notifications 
                    SET email_envoye = 1, date_email_envoye = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$notificationId]);
            }
            
            return $sent;
        } catch (Exception $e) {
            error_log("Erreur envoi email notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtenir les notifications d'un utilisateur
     */
    public function getNotifications($userId, $unreadOnly = false, $limit = 50) {
        $sql = "
            SELECT * FROM notifications
            WHERE utilisateur_id = ?
        ";
        
        if ($unreadOnly) {
            $sql .= " AND lu = 0";
        }
        
        $sql .= " ORDER BY date_creation DESC LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Obtenir le nombre de notifications non lues
     */
    public function getUnreadCount($userId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM notifications 
            WHERE utilisateur_id = ? AND lu = 0
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }

    /**
     * Marquer une notification comme lue
     */
    public function markAsRead($notificationId, $userId) {
        $stmt = $this->db->prepare("
            UPDATE notifications 
            SET lu = 1 
            WHERE id = ? AND utilisateur_id = ?
        ");
        return $stmt->execute([$notificationId, $userId]);
    }

    /**
     * Marquer toutes les notifications comme lues
     */
    public function markAllAsRead($userId) {
        $stmt = $this->db->prepare("
            UPDATE notifications 
            SET lu = 1 
            WHERE utilisateur_id = ? AND lu = 0
        ");
        return $stmt->execute([$userId]);
    }

    /**
     * Supprimer une notification
     */
    public function delete($notificationId, $userId) {
        $stmt = $this->db->prepare("
            DELETE FROM notifications 
            WHERE id = ? AND utilisateur_id = ?
        ");
        return $stmt->execute([$notificationId, $userId]);
    }

    /**
     * Notifier un RDV confirmé
     */
    public function notifyRdvConfirme($rdvId, $clientId, $agentId = null, $sendEmail = true) {
        // Notification au client
        $this->create(
            $clientId,
            'rdv_confirme',
            'Rendez-vous confirmé',
            'Votre rendez-vous a été confirmé',
            url('mes-rendez-vous.php?id=' . $rdvId),
            $sendEmail
        );
        
        // Notification à l'agent si assigné
        if ($agentId) {
            $agent = $this->getAgentUserId($agentId);
            if ($agent) {
                $this->create(
                    $agent,
                    'rdv_confirme',
                    'Rendez-vous confirmé',
                    'Un rendez-vous a été confirmé',
                    url('rendez-vous.php?id=' . $rdvId),
                    false // Pas d'email pour l'agent
                );
            }
        }
    }

    /**
     * Notifier un RDV refusé
     */
    public function notifyRdvRefuse($rdvId, $clientId, $sendEmail = true) {
        $this->create(
            $clientId,
            'rdv_refuse',
            'Rendez-vous refusé',
            'Votre demande de rendez-vous a été refusée',
            url('mes-rendez-vous.php'),
            $sendEmail
        );
    }

    /**
     * Notifier une validation de document
     */
    public function notifyDocumentValide($documentId, $dossierId, $clientId, $sendEmail = true) {
        $this->create(
            $clientId,
            'document_valide',
            'Document validé',
            'Un de vos documents a été validé',
            url('mon-dossier.php?dossier_id=' . $dossierId),
            $sendEmail
        );
    }
    
    /**
     * Notifier un changement de statut de dossier
     */
    public function notifyDossierUpdate($dossierId, $clientId, $nouveauStatut, $sendEmail = true) {
        $titre = 'Mise à jour du dossier';
        $message = 'Le statut de votre dossier a été mis à jour : ' . Constants::getDossierStatusLabel($nouveauStatut);
        
        $this->create(
            $clientId,
            'dossier_update',
            $titre,
            $message,
            url('mon-dossier.php?id=' . $dossierId),
            $sendEmail
        );
    }
    
    /**
     * Notifier la finalisation d'un dossier
     */
    public function notifyDossierFinalise($dossierId, $clientId, $sendEmail = true) {
        $this->create(
            $clientId,
            'dossier_finalise',
            'Dossier finalisé',
            'Votre dossier a été finalisé avec succès',
            url('mon-dossier.php?id=' . $dossierId),
            $sendEmail
        );
    }

    /**
     * Helper pour obtenir l'ID utilisateur d'un agent
     */
    private function getAgentUserId($agentId) {
        $stmt = $this->db->prepare("SELECT utilisateur_id FROM agents WHERE id = ?");
        $stmt->execute([$agentId]);
        $agent = $stmt->fetch();
        return $agent ? $agent['utilisateur_id'] : null;
    }
}

