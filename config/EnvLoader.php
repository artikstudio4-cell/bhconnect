<?php
/**
 * Charge les variables d'environnement depuis le fichier .env
 * Compatible avec InfinityFree et localhost
 */

class EnvLoader {
    private static $loaded = false;
    private static $vars = [];

    /**
     * Charger les variables d'environnement
     */
    public static function load($filePath = null) {
        if (self::$loaded) {
            return;
        }

        if ($filePath === null) {
            $filePath = dirname(__DIR__) . '/.env';
        }

        if (!file_exists($filePath)) {
            // .env n'existe pas, utiliser les defaults
            self::setDefaults();
            self::$loaded = true;
            return;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Ignorer les commentaires
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Parser la ligne (KEY=VALUE)
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Retirer les guillemets si présents
                if ((strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) ||
                    (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1)) {
                    $value = substr($value, 1, -1);
                }

                self::$vars[$key] = $value;
                $_ENV[$key] = $value;
            }
        }

        self::$loaded = true;
    }

    /**
     * Obtenir une variable
     */
    public static function get($key, $default = null) {
        if (!self::$loaded) {
            self::load();
        }
        return self::$vars[$key] ?? getenv($key) ?: $default;
    }

    /**
     * Définir les defaults si .env n'existe pas
     */
    private static function setDefaults() {
        $defaults = [
            'ENVIRONMENT' => (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] !== 'localhost') ? 'production' : 'development',
            'DB_HOST' => (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] !== 'localhost') ? 'sql309.infinityfree.com' : 'localhost',
            'DB_PORT' => '3306',
            'DB_NAME' => 'cabinet_immigration',
            'DB_USER' => (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] !== 'localhost') ? 'if0_40862714' : 'root',
            'DB_PASS' => '',
            'APP_NAME' => 'BH CONNECT',
            'APP_DEBUG' => 'false',
            'APP_LOG_LEVEL' => 'error',
            'SESSION_TIMEOUT' => '3600',
            'SESSION_NAME' => 'bh_connect_session',
            'MAX_FILE_SIZE' => '5242880',
            'UPLOAD_DIR' => 'uploads',
            'MAIL_DRIVER' => 'php',
            'MAIL_FROM' => 'noreply@bhconnect.com',
            'MAIL_FROM_NAME' => 'BH CONNECT',
            'APP_TIMEZONE' => 'Europe/Paris',
            'CSRF_TOKEN_LENGTH' => '32',
            'RATE_LIMIT_ATTEMPTS' => '5',
            'RATE_LIMIT_WINDOW' => '300',
        ];

        foreach ($defaults as $key => $value) {
            self::$vars[$key] = $value;
        }
    }
}

// Charger automatiquement
EnvLoader::load();
