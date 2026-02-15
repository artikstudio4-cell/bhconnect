<?php
/**
 * Rate Limiting - Prévention des attaques par force brute
 */

class RateLimiter {
    private $db;
    private $attempts = 5;
    private $window = 300; // 5 minutes en secondes

    public function __construct($db = null) {
        if ($db) {
            $this->db = $db;
        } else {
            $this->db = Database::getInstance()->getConnection();
        }
        
        // Charger depuis .env
        require_once __DIR__ . '/EnvLoader.php';
        $this->attempts = (int)EnvLoader::get('RATE_LIMIT_ATTEMPTS', 5);
        $this->window = (int)EnvLoader::get('RATE_LIMIT_WINDOW', 300);
    }

    /**
     * Vérifier si une adresse IP/utilisateur est bloquée
     */
    public function isBlocked($identifier) {
        $key = $this->getKey($identifier);
        
        // Essayer avec la base de données
        try {
            $stmt = $this->db->prepare("
                SELECT attempt_count, last_attempt 
                FROM rate_limits 
                WHERE identifier = ? AND last_attempt > DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            $stmt->execute([$key, $this->window]);
            $record = $stmt->fetch();

            if ($record && $record['attempt_count'] >= $this->attempts) {
                return true;
            }
        } catch (Exception $e) {
            // Si la table n'existe pas, ignorer
        }

        return false;
    }

    /**
     * Enregistrer une tentative
     */
    public function recordAttempt($identifier) {
        $key = $this->getKey($identifier);

        try {
            // Vérifier si une tentative existe dans la fenêtre
            $stmt = $this->db->prepare("
                SELECT id, attempt_count 
                FROM rate_limits 
                WHERE identifier = ? AND last_attempt > DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            $stmt->execute([$key, $this->window]);
            $record = $stmt->fetch();

            if ($record) {
                // Incrémenter
                $stmt = $this->db->prepare("
                    UPDATE rate_limits 
                    SET attempt_count = attempt_count + 1, last_attempt = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$record['id']]);
            } else {
                // Nouvelle entrée
                $stmt = $this->db->prepare("
                    INSERT INTO rate_limits (identifier, attempt_count, last_attempt)
                    VALUES (?, 1, NOW())
                    ON DUPLICATE KEY UPDATE attempt_count = 1, last_attempt = NOW()
                ");
                $stmt->execute([$key]);
            }
        } catch (Exception $e) {
            // Si la table n'existe pas, ignorer
        }
    }

    /**
     * Réinitialiser les tentatives
     */
    public function reset($identifier) {
        $key = $this->getKey($identifier);

        try {
            $stmt = $this->db->prepare("DELETE FROM rate_limits WHERE identifier = ?");
            $stmt->execute([$key]);
        } catch (Exception $e) {
            // Si la table n'existe pas, ignorer
        }
    }

    /**
     * Générer une clé unique
     */
    private function getKey($identifier) {
        return ($identifier ?: $_SERVER['REMOTE_ADDR'] ?? 'unknown');
    }

    /**
     * Nettoyer les anciennes entrées (à exécuter régulièrement)
     */
    public function cleanup() {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM rate_limits 
                WHERE last_attempt < DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            $stmt->execute([$this->window * 2]);
        } catch (Exception $e) {
            // Si la table n'existe pas, ignorer
        }
    }
}
