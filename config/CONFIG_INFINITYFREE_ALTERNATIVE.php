<?php
/**
 * CONFIGURATION ALTERNATIVE POUR INFINITYFREE
 * Si vous avez encore des problèmes avec les sessions et CSRF,
 * vous pouvez utiliser cette config plus simple
 * 
 * Instructions:
 * 1. Sauvegardez votre config/config.php actuel
 * 2. Remplacez le contenu de la section SESSIONS by the code below
 * 3. Testez login.php
 */

// =====================================================
// ALTERNATIVE 1: SESSIONS ULTRA-SIMPLE (RECOMMANDÉ)
// =====================================================
// Utiliser JUSTE session_start() avec config minimale
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    @session_start([
        'name' => 'bh_connect_session'
    ]);
}


// =====================================================
// ALTERNATIVE 2: SESSIONS AVEC PATH (Si Alternative 1 ne marche pas)
// =====================================================
/*
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    @mkdir(__DIR__ . '/../sessions', 0777, true);
    @ini_set('session.save_path', __DIR__ . '/../sessions');
    @session_start([
        'name' => 'bh_connect_session'
    ]);
}
*/


// =====================================================
// ALTERNATIVE 3: SESSIONS AVEC DEBUG
// =====================================================
/*
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    // Log what we're doing
    error_log("Starting session... Session status: " . session_status());
    
    @session_start([
        'name' => 'bh_connect_session',
        'cookie_lifetime' => 3600
    ]);
    
    error_log("Session started. ID: " . session_id());
    error_log("Session data: " . json_encode($_SESSION));
}
*/


// =====================================================
// ENSUITE: CSRF AMÉLIORÉ
// =====================================================

/**
 * Tokens CSRF en session + fichier (backup si session échoue)
 */
class CSRFTokenInfinity {
    private static $tokenFile = null;
    private static $tokenKey = 'csrf_token';
    
    public static function init() {
        self::$tokenFile = __DIR__ . '/../uploads/.csrf_tokens.json';
    }
    
    public static function generate() {
        // Essayer d'abord avec SESSION
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION[self::$tokenKey])) {
            return $_SESSION[self::$tokenKey];
        }
        
        // Générer un nouveau token
        $token = bin2hex(random_bytes(32));
        
        // Sauvegarder dans SESSION
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION[self::$tokenKey] = $token;
        }
        
        // Aussi sauvegarder dans un fichier (backup)
        // self::saveTokenToFile($token);
        
        return $token;
    }
    
    public static function verify($token = null) {
        if ($token === null) {
            $token = $_POST['csrf_token'] ?? '';
        }
        
        if (empty($token)) {
            return false;
        }
        
        // Vérifier en SESSION d'abord
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION[self::$tokenKey])) {
            if (hash_equals($_SESSION[self::$tokenKey], $token)) {
                return true;
            }
        }
        
        // Sinon vérifier dans fichier
        // if (self::verifyTokenFile($token)) {
        //     return true;
        // }
        
        return false;
    }
    
    public static function field($name = 'csrf_token') {
        $token = self::generate();
        return '<input type="hidden" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($token) . '">';
    }
    
    public static function token() {
        return self::generate();
    }
}

// Initialiser
CSRFTokenInfinity::init();

?>
