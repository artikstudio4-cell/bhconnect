<?php
/**
 * Connexion à la base de données avec reconnection automatique
 * Optimisé pour InfinityFree
 */

require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $connection;
    private $retryCount = 0;
    private $maxRetries = 3;

    private function __construct() {
        $this->connect();
    }

    /**
     * Établir la connexion à la base de données
     */
    private function connect() {
        try {
            $this->connection = new PDO(
                "mysql:host=" . DB_HOST . ";port=3306;dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => 5  // Timeout 5 secondes
                ]
            );
            
            // Test rapide de la connexion
            $this->connection->query("SELECT 1");
            $this->retryCount = 0;
            
        } catch (PDOException $e) {
            // Log l'erreur
            $errorLog = __DIR__ . '/../logs/database_error.log';
            $errorMsg = "[" . date('Y-m-d H:i:s') . "] Erreur BD: " . $e->getMessage() . "\n";
            error_log($errorMsg, 3, $errorLog);
            
            // Tenter une reconnexion
            if ($this->retryCount < $this->maxRetries) {
                $this->retryCount++;
                sleep(1); // Attendre 1 seconde avant de réessayer
                $this->connect();
            } else {
                // Affichage d'une page d'erreur gracieuse
                $this->showErrorPage($e);
            }
        }
    }

    /**
     * Vérifier que la connexion est active
     */
    private function isConnected() {
        if ($this->connection === null) {
            return false;
        }
        
        try {
            $this->connection->query("SELECT 1");
            return true;
        } catch (PDOException $e) {
            error_log("[" . date('Y-m-d H:i:s') . "] Connexion perdue: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtenir l'instance de la base de données (singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        // Vérifier que la connexion est toujours active
        if (!self::$instance->isConnected()) {
            self::$instance->connect();
        }
        
        return self::$instance;
    }

    /**
     * Obtenir la connexion PDO
     */
    public function getConnection() {
        return $this->connection;
    }

    /**
     * Afficher une page d'erreur gracieuse
     */
    private function showErrorPage($exception) {
        http_response_code(503);
        
        // En production, afficher une page simple
        if (!defined('APP_DEBUG') || !APP_DEBUG) {
            echo "<!DOCTYPE html>";
            echo "<html lang='fr'>";
            echo "<head>";
            echo "<meta charset='UTF-8'>";
            echo "<meta name='viewport' content='width=device-width, initial-scale=1'>";
            echo "<title>Service Indisponible</title>";
            echo "<style>";
            echo "body { font-family: Arial, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; background: #f0f0f0; }";
            echo ".container { background: white; padding: 40px; border-radius: 8px; text-align: center; max-width: 500px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }";
            echo "h1 { color: #d32f2f; margin: 0 0 10px 0; }";
            echo "p { color: #666; margin: 10px 0; line-height: 1.6; }";
            echo ".retry { margin-top: 20px; }";
            echo "a { color: #1976d2; text-decoration: none; }";
            echo "a:hover { text-decoration: underline; }";
            echo "</style>";
            echo "</head>";
            echo "<body>";
            echo "<div class='container'>";
            echo "<h1>⚠️ Service Indisponible</h1>";
            echo "<p>La base de données est temporairement inaccessible.</p>";
            echo "<p>Veuillez <span class='retry'><a href='javascript:location.reload()'>réessayer dans quelques secondes</a></span></p>";
            echo "<p style='font-size: 12px; color: #999; margin-top: 30px;'>";
            echo "Si le problème persiste, contactez le support.";
            echo "</p>";
            echo "</div>";
            echo "</body>";
            echo "</html>";
        } else {
            // En debug, afficher le message d'erreur réel
            die("Erreur de connexion à la base de données : " . $exception->getMessage());
        }
        
        exit;
    }

    // Empêcher le clonage
    private function __clone() {}

    // Empêcher la désérialisation
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

?>
