<?php
/**
 * ErrorLogger - Centralise la gestion des erreurs
 * Utile pour InfinityFree où les messages d'erreur ne s'affichent pas toujours
 */

class ErrorLogger {
    private static $logFile = null;
    
    public static function init() {
        self::$logFile = __DIR__ . '/../logs/php_errors.log';
        
        // Créer le dossier logs s'il n'existe pas
        $logDir = dirname(self::$logFile);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        
        // Configuration du logging des erreurs PHP
        ini_set('error_log', self::$logFile);
        
        // Enregistrer les erreurs avec une fonction custom
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }
    
    public static function handleError($errno, $errstr, $errfile, $errline) {
        $errorType = match($errno) {
            E_ERROR => 'ERROR',
            E_WARNING => 'WARNING',
            E_PARSE => 'PARSE ERROR',
            E_NOTICE => 'NOTICE',
            E_CORE_ERROR => 'CORE ERROR',
            E_CORE_WARNING => 'CORE WARNING',
            E_COMPILE_ERROR => 'COMPILE ERROR',
            E_COMPILE_WARNING => 'COMPILE WARNING',
            E_USER_ERROR => 'USER ERROR',
            E_USER_WARNING => 'USER WARNING',
            E_USER_NOTICE => 'USER NOTICE',
            E_STRICT => 'STRICT',
            E_RECOVERABLE_ERROR => 'RECOVERABLE ERROR',
            E_DEPRECATED => 'DEPRECATED',
            E_USER_DEPRECATED => 'USER DEPRECATED',
            default => 'UNKNOWN'
        };
        
        $message = sprintf(
            "[%s] [%s] %s in %s:%d\n",
            date('Y-m-d H:i:s'),
            $errorType,
            $errstr,
            $errfile,
            $errline
        );
        
        error_log($message);
        
        // Ne pas continuer l'exécution pour les erreurs fatales
        if ($errno === E_ERROR || $errno === E_CORE_ERROR || $errno === E_COMPILE_ERROR) {
            return false;
        }
        
        return true;
    }
    
    public static function handleException(Throwable $e) {
        $message = sprintf(
            "[%s] [EXCEPTION] %s in %s:%d\nStacktrace:\n%s\n",
            date('Y-m-d H:i:s'),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );
        
        error_log($message);
        
        // Afficher un message générique en production
        http_response_code(500);
        echo "Une erreur est survenue. Veuillez réessayer plus tard.";
        exit;
    }
    
    public static function handleShutdown() {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            self::handleError($error['type'], $error['message'], $error['file'], $error['line']);
        }
    }
    
    public static function log($message, $level = 'INFO') {
        $msg = sprintf(
            "[%s] [%s] %s\n",
            date('Y-m-d H:i:s'),
            $level,
            $message
        );
        error_log($msg);
    }
}

// Initialiser automatiquement
ErrorLogger::init();
?>
