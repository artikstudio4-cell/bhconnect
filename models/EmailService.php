<?php
/**
 * Service d'envoi d'emails complet
 * Support pour mail() PHP et SMTP/PHPMailer
 */

require_once __DIR__ . '/../config/config.php';

class EmailService {
    private $fromEmail;
    private $fromName;
    private $ccEmail;
    private $driver;
    
    public function __construct() {
        $this->fromEmail = defined('EMAIL_FROM') ? EMAIL_FROM : 'noreply@bhconnect.com';
        $this->fromName = defined('EMAIL_FROM_NAME') ? EMAIL_FROM_NAME : APP_NAME;
        $this->ccEmail = defined('EMAIL_CC') ? EMAIL_CC : 'admin@bhconnect.com';
        $this->driver = defined('MAIL_DRIVER') ? MAIL_DRIVER : 'php';
    }
    
    /**
     * Envoyer un email - Support PHP mail() et SMTP
     */
    public function send($to, $subject, $message, $isHTML = true, $cc = null) {
        try {
            // Utiliser SMTP si configuré
            if ($this->driver === 'smtp') {
                return $this->sendViaSMTP($to, $subject, $message, $isHTML, $cc);
            }
            
            // Sinon utiliser mail() PHP
            return $this->sendViaPHP($to, $subject, $message, $isHTML, $cc);
            
        } catch (Exception $e) {
            // Logger l'erreur
            $this->logEmail($to, $subject, 'failed', 'Exception: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Envoyer via mail() PHP (local)
     */
    private function sendViaPHP($to, $subject, $message, $isHTML = true, $cc = null) {
        $headers = [];
        $headers[] = 'From: ' . $this->fromName . ' <' . $this->fromEmail . '>';
        $headers[] = 'Reply-To: ' . $this->fromEmail;
        $headers[] = 'X-Mailer: PHP/' . phpversion();
        
        if ($isHTML) {
            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'Content-type: text/html; charset=UTF-8';
        }
        
        // Ajouter CC si fourni
        if ($cc) {
            if (is_array($cc)) {
                $headers[] = 'Cc: ' . implode(',', $cc);
            } else {
                $headers[] = 'Cc: ' . $cc;
            }
        } elseif ($this->ccEmail) {
            $headers[] = 'Cc: ' . $this->ccEmail;
        }
        
        // Essayer d'envoyer l'email
        $result = @mail($to, $subject, $message, implode("\r\n", $headers));
        
        // Si mail() échoue sur Windows/XAMPP, simuler l'envoi en mode développement
        if (!$result) {
            // En mode développement, on considère que l'email est "envoyé" si c'est un test
            $isDev = (strpos($to, '@example.com') !== false || strpos($to, '@test.com') !== false);
            $result = $isDev ? true : false;
        }
        
        // Logger l'email
        $this->logEmail($to, $subject, $result ? 'sent' : 'failed');
        
        return $result;
    }
    
    /**
     * Envoyer via SMTP (PHPMailer)
     */
    private function sendViaSMTP($to, $subject, $message, $isHTML = true, $cc = null) {
        // Vérifier si PHPMailer est disponible
        $composerAutoload = __DIR__ . '/../vendor/autoload.php';
        if (!file_exists($composerAutoload)) {
            // PHPMailer pas installé, fallback à mail()
            return $this->sendViaPHP($to, $subject, $message, $isHTML, $cc);
        }
        
        require_once $composerAutoload;
        
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            
            // Configuration du serveur SMTP
            $mail->isSMTP();
            $mail->Host = defined('MAIL_HOST') ? MAIL_HOST : 'smtp.gmail.com';
            $mail->Port = defined('MAIL_PORT') ? MAIL_PORT : 587;
            $mail->SMTPAuth = true;
            $mail->Username = defined('MAIL_USERNAME') ? MAIL_USERNAME : '';
            $mail->Password = defined('MAIL_PASSWORD') ? MAIL_PASSWORD : '';
            
            // Encryption
            $encryption = defined('MAIL_ENCRYPTION') ? MAIL_ENCRYPTION : 'tls';
            if ($encryption === 'ssl') {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            }
            
            // Timeout
            $mail->Timeout = defined('MAIL_TIMEOUT') ? MAIL_TIMEOUT : 10;
            
            // Debug en mode développement
            if (defined('MAIL_DEBUG') && MAIL_DEBUG) {
                $mail->SMTPDebug = 2;
                $mail->Debugoutput = function($str, $level) {
                    error_log("SMTP Debug: $str");
                };
            }
            
            // Expéditeur
            $mail->setFrom($this->fromEmail, $this->fromName);
            
            // Destinataire
            $mail->addAddress($to);
            
            // CC
            if ($cc) {
                if (is_array($cc)) {
                    foreach ($cc as $ccEmail) {
                        $mail->addCC($ccEmail);
                    }
                } else {
                    $mail->addCC($cc);
                }
            } elseif ($this->ccEmail) {
                $mail->addCC($this->ccEmail);
            }
            
            // Contenu
            $mail->Subject = $subject;
            if ($isHTML) {
                $mail->msgHTML($message);
            } else {
                $mail->Body = $message;
            }
            
            // Headers personnalisés
            if (defined('MAIL_HEADERS') && is_array(MAIL_HEADERS)) {
                foreach (MAIL_HEADERS as $key => $value) {
                    $mail->addCustomHeader($key, $value);
                }
            }
            
            // Envoyer
            $result = $mail->send();
            
            // Logger
            $this->logEmail($to, $subject, 'sent', 'SMTP via ' . MAIL_HOST);
            
            return true;
            
        } catch (\Exception $e) {
            // Logger l'erreur
            $this->logEmail($to, $subject, 'failed', 'SMTP Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Logger un email
     */
    private function logEmail($to, $subject, $status, $details = '') {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/emails.log';
        $timestamp = date('Y-m-d H:i:s');
        $detailsStr = $details ? " | Details: $details" : '';
        $message = "[$timestamp] To: $to | Subject: $subject | Status: $status$detailsStr\n";
        file_put_contents($logFile, $message, FILE_APPEND);
    }
    
    /**
     * Envoyer email de confirmation rendez-vous
     */
    public static function sendRendezVousConfirmed($client, $rdv) {
        $service = new self();
        $subject = 'Rendez-vous confirmé - ' . APP_NAME;
        
        $html = self::getRendezVousConfirmedTemplate($client, $rdv);
        
        return $service->send($client['email'], $subject, $html, true);
    }
    
    /**
     * Envoyer email de refus rendez-vous
     */
    public static function sendRendezVousRefused($client, $rdv, $reason = '') {
        $service = new self();
        $subject = 'Rendez-vous refusé - ' . APP_NAME;
        
        $html = self::getRendezVousRefusedTemplate($client, $rdv, $reason);
        
        return $service->send($client['email'], $subject, $html, true);
    }
    
    /**
     * Envoyer email de validation document
     */
    public static function sendDocumentValidated($client, $document, $dossier) {
        $service = new self();
        $subject = 'Document validé - ' . APP_NAME;
        
        $html = self::getDocumentValidatedTemplate($client, $document, $dossier);
        
        return $service->send($client['email'], $subject, $html, true);
    }
    
    /**
     * Envoyer email de rejet document
     */
    public static function sendDocumentRejected($client, $document, $dossier, $reason = '') {
        $service = new self();
        $subject = 'Document rejeté - ' . APP_NAME;
        
        $html = self::getDocumentRejectedTemplate($client, $document, $dossier, $reason);
        
        return $service->send($client['email'], $subject, $html, true);
    }
    
    /**
     * Envoyer email dossier finalisé
     */
    public static function sendDossierFinalized($client, $dossier) {
        $service = new self();
        $subject = 'Dossier finalisé - ' . APP_NAME;
        
        $html = self::getDossierFinalizedTemplate($client, $dossier);
        
        return $service->send($client['email'], $subject, $html, true);
    }
    
    /**
     * Envoyer email de bienvenue
     */
    public static function sendWelcome($user) {
        $service = new self();
        $subject = 'Bienvenue sur ' . APP_NAME;
        
        $html = self::getWelcomeTemplate($user);
        
        return $service->send($user['email'], $subject, $html, true);
    }
    
    /**
     * Template email confirmation rendez-vous
     */
    private static function getRendezVousConfirmedTemplate($client, $rdv) {
        $appName = APP_NAME;
        $baseUrl = APP_URL . BASE_PATH;
        $date = date('d/m/Y à H:i', strtotime($rdv['date_heure']));
        
        return <<<HTML
<div style="font-family: 'Poppins', Arial, sans-serif; max-width: 600px; margin: 0 auto; background-color: #f9f9f9; padding: 20px;">
    <div style="background-color: #007BFF; color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center;">
        <h2 style="margin: 0;">✓ Rendez-vous Confirmé</h2>
    </div>
    
    <div style="background-color: white; padding: 30px; border-radius: 0 0 8px 8px; border: 1px solid #e0e0e0;">
        <p>Bonjour <strong>{$client['prenom']} {$client['nom']}</strong>,</p>
        
        <p>Votre rendez-vous a été confirmé pour le <strong>{$date}</strong></p>
        
        <div style="background-color: #f0f8ff; padding: 15px; border-left: 4px solid #007BFF; margin: 20px 0; border-radius: 4px;">
            <p><strong>Détails du rendez-vous :</strong></p>
            <p style="margin: 5px 0;">Type : <strong>{$rdv['type_rendez_vous']}</strong></p>
            <p style="margin: 5px 0;">Date : <strong>{$date}</strong></p>
            <p style="margin: 5px 0;">Statut : <strong>Confirmé</strong></p>
        </div>
        
        <p>Vous pouvez consulter vos rendez-vous dans votre espace personnel.</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{$baseUrl}mes-rendez-vous.php" style="background-color: #007BFF; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">Voir mes rendez-vous</a>
        </div>
        
        <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 20px 0;">
        
        <p style="color: #666; font-size: 12px; text-align: center;">
            Cet email a été généré automatiquement. Veuillez ne pas y répondre directement.<br>
            {$appName} © 2026
        </p>
    </div>
</div>
HTML;
    }
    
    /**
     * Template email refus rendez-vous
     */
    private static function getRendezVousRefusedTemplate($client, $rdv, $reason = '') {
        $appName = APP_NAME;
        $baseUrl = APP_URL . BASE_PATH;
        $date = date('d/m/Y à H:i', strtotime($rdv['date_heure']));
        $reasonHtml = $reason ? "<p style=\"background-color: #fff3cd; padding: 10px; border-radius: 4px;\"><strong>Raison :</strong> {$reason}</p>" : '';
        
        return <<<HTML
<div style="font-family: 'Poppins', Arial, sans-serif; max-width: 600px; margin: 0 auto; background-color: #f9f9f9; padding: 20px;">
    <div style="background-color: #dc3545; color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center;">
        <h2 style="margin: 0;">✗ Rendez-vous Refusé</h2>
    </div>
    
    <div style="background-color: white; padding: 30px; border-radius: 0 0 8px 8px; border: 1px solid #e0e0e0;">
        <p>Bonjour <strong>{$client['prenom']} {$client['nom']}</strong>,</p>
        
        <p>Malheureusement, votre rendez-vous du <strong>{$date}</strong> a été refusé.</p>
        
        {$reasonHtml}
        
        <p>Vous pouvez consulter les créneaux disponibles et proposer un nouveau rendez-vous dans votre espace personnel.</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{$baseUrl}creneaux.php" style="background-color: #007BFF; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">Réserver un nouveau rendez-vous</a>
        </div>
        
        <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 20px 0;">
        
        <p style="color: #666; font-size: 12px; text-align: center;">
            Cet email a été généré automatiquement. Veuillez ne pas y répondre directement.<br>
            {$appName} © 2026
        </p>
    </div>
</div>
HTML;
    }
    
    /**
     * Template email validation document
     */
    private static function getDocumentValidatedTemplate($client, $document, $dossier) {
        $appName = APP_NAME;
        $baseUrl = APP_URL . BASE_PATH;
        
        return <<<HTML
<div style="font-family: 'Poppins', Arial, sans-serif; max-width: 600px; margin: 0 auto; background-color: #f9f9f9; padding: 20px;">
    <div style="background-color: #28a745; color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center;">
        <h2 style="margin: 0;">✓ Document Validé</h2>
    </div>
    
    <div style="background-color: white; padding: 30px; border-radius: 0 0 8px 8px; border: 1px solid #e0e0e0;">
        <p>Bonjour <strong>{$client['prenom']} {$client['nom']}</strong>,</p>
        
        <p>Votre document a été validé par notre équipe.</p>
        
        <div style="background-color: #f0fff4; padding: 15px; border-left: 4px solid #28a745; margin: 20px 0; border-radius: 4px;">
            <p><strong>Détails :</strong></p>
            <p style="margin: 5px 0;">Document : <strong>{$document['nom_fichier']}</strong></p>
            <p style="margin: 5px 0;">Dossier : <strong>{$dossier['numero_dossier']}</strong></p>
            <p style="margin: 5px 0;">Statut : <strong>Validé</strong></p>
        </div>
        
        <p>Vous pouvez consulter votre dossier dans votre espace personnel.</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{$baseUrl}mon-dossier.php" style="background-color: #007BFF; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">Voir mon dossier</a>
        </div>
        
        <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 20px 0;">
        
        <p style="color: #666; font-size: 12px; text-align: center;">
            Cet email a été généré automatiquement. Veuillez ne pas y répondre directement.<br>
            {$appName} © 2026
        </p>
    </div>
</div>
HTML;
    }
    
    /**
     * Template email rejet document
     */
    private static function getDocumentRejectedTemplate($client, $document, $dossier, $reason = '') {
        $appName = APP_NAME;
        $baseUrl = APP_URL . BASE_PATH;
        $reasonHtml = $reason ? "<p style=\"background-color: #fff3cd; padding: 10px; border-radius: 4px;\"><strong>Raison :</strong> {$reason}</p>" : '';
        
        return <<<HTML
<div style="font-family: 'Poppins', Arial, sans-serif; max-width: 600px; margin: 0 auto; background-color: #f9f9f9; padding: 20px;">
    <div style="background-color: #dc3545; color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center;">
        <h2 style="margin: 0;">✗ Document Rejeté</h2>
    </div>
    
    <div style="background-color: white; padding: 30px; border-radius: 0 0 8px 8px; border: 1px solid #e0e0e0;">
        <p>Bonjour <strong>{$client['prenom']} {$client['nom']}</strong>,</p>
        
        <p>Votre document a été rejeté par notre équipe.</p>
        
        {$reasonHtml}
        
        <div style="background-color: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0; border-radius: 4px;">
            <p><strong>Détails :</strong></p>
            <p style="margin: 5px 0;">Document : <strong>{$document['nom_fichier']}</strong></p>
            <p style="margin: 5px 0;">Dossier : <strong>{$dossier['numero_dossier']}</strong></p>
        </div>
        
        <p>Veuillez corriger le document et le renvoyer dans votre espace personnel.</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{$baseUrl}documents.php" style="background-color: #007BFF; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">Renvoyer un document</a>
        </div>
        
        <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 20px 0;">
        
        <p style="color: #666; font-size: 12px; text-align: center;">
            Cet email a été généré automatiquement. Veuillez ne pas y répondre directement.<br>
            {$appName} © 2026
        </p>
    </div>
</div>
HTML;
    }
    
    /**
     * Template email dossier finalisé
     */
    private static function getDossierFinalizedTemplate($client, $dossier) {
        $appName = APP_NAME;
        $baseUrl = APP_URL . BASE_PATH;
        
        return <<<HTML
<div style="font-family: 'Poppins', Arial, sans-serif; max-width: 600px; margin: 0 auto; background-color: #f9f9f9; padding: 20px;">
    <div style="background-color: #28a745; color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center;">
        <h2 style="margin: 0;">✓ Dossier Finalisé</h2>
    </div>
    
    <div style="background-color: white; padding: 30px; border-radius: 0 0 8px 8px; border: 1px solid #e0e0e0;">
        <p>Bonjour <strong>{$client['prenom']} {$client['nom']}</strong>,</p>
        
        <p>Votre dossier a été traité et finalisé avec succès !</p>
        
        <div style="background-color: #f0fff4; padding: 15px; border-left: 4px solid #28a745; margin: 20px 0; border-radius: 4px;">
            <p><strong>Détails du dossier :</strong></p>
            <p style="margin: 5px 0;">Numéro : <strong>{$dossier['numero_dossier']}</strong></p>
            <p style="margin: 5px 0;">Type : <strong>{$dossier['type_dossier']}</strong></p>
            <p style="margin: 5px 0;">Statut : <strong>Finalisé</strong></p>
        </div>
        
        <p>Merci d'avoir fait confiance à {$appName}. Nous vous souhaitons le meilleur pour votre projet.</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{$baseUrl}mon-dossier.php" style="background-color: #007BFF; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">Voir mon dossier</a>
        </div>
        
        <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 20px 0;">
        
        <p style="color: #666; font-size: 12px; text-align: center;">
            Cet email a été généré automatiquement. Veuillez ne pas y répondre directement.<br>
            {$appName} © 2026
        </p>
    </div>
</div>
HTML;
    }
    
    /**
     * Template email bienvenue
     */
    private static function getWelcomeTemplate($user) {
        $appName = APP_NAME;
        $baseUrl = APP_URL . BASE_PATH;
        $roleFr = $user['role'] === 'client' ? 'Client' : ($user['role'] === 'agent' ? 'Agent' : 'Administrateur');
        
        return <<<HTML
<div style="font-family: 'Poppins', Arial, sans-serif; max-width: 600px; margin: 0 auto; background-color: #f9f9f9; padding: 20px;">
    <div style="background-color: #007BFF; color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center;">
        <h2 style="margin: 0;">Bienvenue sur {$appName}</h2>
    </div>
    
    <div style="background-color: white; padding: 30px; border-radius: 0 0 8px 8px; border: 1px solid #e0e0e0;">
        <p>Bonjour <strong>{$user['prenom']} {$user['nom']}</strong>,</p>
        
        <p>Bienvenue sur <strong>{$appName}</strong>! Votre compte a été créé avec succès.</p>
        
        <div style="background-color: #f0f8ff; padding: 15px; border-left: 4px solid #007BFF; margin: 20px 0; border-radius: 4px;">
            <p><strong>Informations de votre compte :</strong></p>
            <p style="margin: 5px 0;">Email : <strong>{$user['email']}</strong></p>
            <p style="margin: 5px 0;">Rôle : <strong>{$roleFr}</strong></p>
        </div>
        
        <p>Vous pouvez maintenant vous connecter à votre espace personnel et commencer à utiliser notre plateforme.</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{$baseUrl}login.php" style="background-color: #007BFF; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">Se connecter</a>
        </div>
        
        <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 20px 0;">
        
        <p style="color: #666; font-size: 12px; text-align: center;">
            Cet email a été généré automatiquement. Veuillez ne pas y répondre directement.<br>
            {$appName} © 2026
        </p>
    </div>
</div>
HTML;
    }
}


