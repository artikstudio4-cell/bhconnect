<?php
/**
 * Gestion des tokens CSRF (Cross-Site Request Forgery)
 * Version améliorée pour InfinityFree
 */

class CSRFToken {
    const SESSION_KEY = 'csrf_token';
    const TOKEN_LENGTH = 32;
    
    /**
     * Générer un nouveau token CSRF
     */
    public static function generate() {
        // Vérifier que la session est available
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        
        if (!isset($_SESSION[self::SESSION_KEY]) || empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(self::TOKEN_LENGTH));
        }
        return $_SESSION[self::SESSION_KEY];
    }

    /**
     * Vérifier que le token CSRF est valide
     */
    public static function verify($token = null) {
        // Vérifier que la session est available
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        
        if ($token === null) {
            $token = $_POST['csrf_token'] ?? $_REQUEST['csrf_token'] ?? '';
        }

        if (empty($token)) {
            return false;
        }
        
        if (empty($_SESSION[self::SESSION_KEY])) {
            return false;
        }

        // Comparer les tokens de manière sécurisée
        $isValid = hash_equals($_SESSION[self::SESSION_KEY], $token);
        
        // Régénérer le token après vérification (optionnel mais recommandé)
        if ($isValid) {
            self::regenerate();
        }
        
        return $isValid;
    }

    /**
     * Obtenir le token actuel
     */
    public static function token() {
        return self::generate();
    }

    /**
     * Afficher un champ caché pour formulaires HTML
     */
    public static function field($name = 'csrf_token') {
        $token = self::token();
        return '<input type="hidden" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($token) . '">';
    }

    /**
     * Régénérer le token après utilisation
     */
    public static function regenerate() {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        unset($_SESSION[self::SESSION_KEY]);
        return self::generate();
    }
    
    /**
     * Obtenir le token pour utilisation en AJAX
     */
    public static function getForAjax() {
        return [
            'name' => 'csrf_token',
            'value' => self::token()
        ];
    }
}

?>
