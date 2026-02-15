<?php
/**
 * CONFIGURATION GLOBALE – BH CONNECT
 * 🔒 VERSION SÉCURISÉE
 * Charge les variables depuis .env
 */

// Charger le système de logging des erreurs en premier
require_once __DIR__ . '/ErrorLogger.php';

// Charger les variables d'environnement
require_once __DIR__ . '/EnvLoader.php';

// Charger les classes de sécurité
require_once __DIR__ . '/CSRFToken.php';
require_once __DIR__ . '/FileValidator.php';
require_once __DIR__ . '/RateLimiter.php';
require_once __DIR__ . '/../includes/Constants.php';

/* =====================================================
   GESTION DES ERREURS (SÉCURISÉE)
===================================================== */
$isProduction = EnvLoader::get('ENVIRONMENT', 'production') === 'production';
$appDebug = EnvLoader::get('APP_DEBUG', 'false') === 'true';

if ($isProduction && !$appDebug) {
    // En production: ne pas afficher les erreurs
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/error.log');
} else {
    // En développement: afficher les erreurs
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

/* =====================================================
   BASE URL & PATH
===================================================== */
// Détection automatique du chemin de base (pour supporter les sous-dossiers)
// Normaliser les chemins (Windows vs Unix)
$projectDir = str_replace('\\', '/', dirname(__DIR__));
$docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);

// Si le projet est à la racine, BASE_PATH = /
if (strtolower($projectDir) === strtolower($docRoot)) {
    define('BASE_PATH', '/');
} else {
    // Sinon, on calcule le chemin relatif depuis le Document Root
    $basePath = str_replace(strtolower($docRoot), '', strtolower($projectDir));
    // Récupérer le vrai chemin avec sa casse
    if ($basePath !== '') {
        $basePath = substr($projectDir, strlen($docRoot));
    }
    $basePath = '/' . trim($basePath, '/') . '/';
    define('BASE_PATH', $basePath);
}

/* =====================================================
   APPLICATION
===================================================== */
define('APP_NAME', EnvLoader::get('APP_NAME', 'BH CONNECT'));
define('APP_DEBUG', $appDebug);
define('APP_TIMEZONE', EnvLoader::get('APP_TIMEZONE', 'Europe/Paris'));

/* =====================================================
   LOGO
===================================================== */
define('SHOW_LOGO', true);
define('LOGO_PATH', BASE_PATH . 'images/logo.png');
define('LOGO_ALT', 'BH CONNECT');

/* =====================================================
   BASE DE DONNÉES (depuis .env)
===================================================== */
define('DB_HOST', EnvLoader::get('DB_HOST', 'localhost'));
define('DB_PORT', EnvLoader::get('DB_PORT', '3306'));
define('DB_NAME', EnvLoader::get('DB_NAME', 'cabinet_immigration'));
define('DB_USER', EnvLoader::get('DB_USER', 'root'));
define('DB_PASS', EnvLoader::get('DB_PASS', ''));

/* =====================================================
   URL HELPER
===================================================== */
if (!function_exists('url')) {
    function url(string $path = ''): string {
        return BASE_PATH . ltrim($path, '/');
    }
}

/* =====================================================
   UPLOADS (SÉCURISÉS)
===================================================== */
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', (int)EnvLoader::get('MAX_FILE_SIZE', 5242880)); // 5MB par défaut

// Extensions autorisées pour l'upload de documents
define('ALLOWED_EXTENSIONS', ['pdf', 'jpg', 'jpeg', 'png']);

/* =====================================================
   SESSIONS (SÉCURISÉES)
===================================================== */
// Configuration du chemin de session pour InfinityFree
// Sur InfinityFree, laisser les sessions par défaut si possible
$sessionPath = __DIR__ . '/../sessions';

if ($isProduction) {
    // Vérifier si on peut créer/utiliser le dossier sessions
    if (@is_writable(dirname($sessionPath))) {
        if (!file_exists($sessionPath)) {
            @mkdir($sessionPath, 0755, true);
        }
        if (is_dir($sessionPath) && is_writable($sessionPath)) {
            ini_set('session.save_path', $sessionPath);
        }
        // Sinon, laisser la config par défaut d'InfinityFree
    }
}

// Démarrer la session avec les flags de sécurité
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    $sessionTimeout = (int)EnvLoader::get('SESSION_TIMEOUT', 3600);
    
    // Configuration des sessions - compatible InfinityFree
    $sessionConfig = [
        'cookie_lifetime' => $sessionTimeout,           // Timeout de session
        'cookie_httponly' => true,                      // JavaScript ne peut pas accéder
        'name' => EnvLoader::get('SESSION_NAME', 'bh_connect_session')
    ];
    
    // Seulement en HTTPS vrai (production sécurisée)
    if ($isProduction && isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        $sessionConfig['cookie_secure'] = true;        // HTTPS seulement
        $sessionConfig['cookie_samesite'] = 'Lax';     // Plus permissif que Strict
    }
    
    session_start($sessionConfig);
    
    // Régénérer l'ID de session après connexion (prévention fixation)
    if (!isset($_SESSION['session_regenerated'])) {
        session_regenerate_id(true);
        $_SESSION['session_regenerated'] = true;
    }
}



/* =====================================================
   SECURITY
===================================================== */
define('CSRF_TOKEN_LENGTH', (int)EnvLoader::get('CSRF_TOKEN_LENGTH', 32));
define('RATE_LIMIT_ATTEMPTS', (int)EnvLoader::get('RATE_LIMIT_ATTEMPTS', 5));
define('RATE_LIMIT_WINDOW', (int)EnvLoader::get('RATE_LIMIT_WINDOW', 300)); // 5 minutes

/* =====================================================
   TIMEZONE & ENCODING
===================================================== */
date_default_timezone_set(APP_TIMEZONE);
mb_internal_encoding('UTF-8');
