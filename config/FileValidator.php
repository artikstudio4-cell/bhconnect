<?php
/**
 * Validation et sécurité des uploads de fichiers
 */

class FileValidator {
    
    // MIME types autorisés
    private static $allowedMimes = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    /**
     * Valider un fichier uploadé
     */
    public static function validate($file, $maxSize = null) {
        $errors = [];

        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Erreur lors de l\'upload: ' . self::getUploadError($file['error'] ?? -1);
            return ['valid' => false, 'errors' => $errors];
        }

        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $errors[] = 'Le fichier n\'est pas un upload valide';
            return ['valid' => false, 'errors' => $errors];
        }

        // Vérifier la taille
        if ($maxSize && $file['size'] > $maxSize) {
            $errors[] = 'Le fichier est trop volumineux (max ' . self::formatBytes($maxSize) . ')';
        }

        // Vérifier l'extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = array_values(self::$allowedMimes);
        
        if (!in_array($extension, $allowed)) {
            $errors[] = 'Extension non autorisée. Acceptés: ' . implode(', ', $allowed);
        }

        // Vérifier le MIME type réel
        $mimeType = self::getMimeType($file['tmp_name']);
        if (!isset(self::$allowedMimes[$mimeType])) {
            $errors[] = 'Type de fichier non autorisé (MIME: ' . htmlspecialchars($mimeType) . ')';
        }

        // Vérifier cohérence extension/MIME
        $expectedExtension = self::$allowedMimes[$mimeType] ?? null;
        if ($expectedExtension && $extension !== $expectedExtension) {
            $errors[] = 'L\'extension ne correspond pas au type de fichier réel';
        }

        // Vérifier qu'on ne peut pas exécuter le fichier
        if (self::isExecutable($extension)) {
            $errors[] = 'Les fichiers exécutables ne sont pas autorisés';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'extension' => $extension,
            'mimeType' => $mimeType,
            'size' => $file['size'] ?? 0
        ];
    }

    /**
     * Obtenir le vrai MIME type du fichier
     */
    private static function getMimeType($filePath) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $filePath);
        finfo_close($finfo);
        return $mime;
    }

    /**
     * Vérifier que le fichier n'est pas exécutable
     */
    private static function isExecutable($extension) {
        $executable = ['php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar', 'sh', 'bat', 'exe', 'com', 'scr', 'exe'];
        return in_array($extension, $executable);
    }

    /**
     * Obtenir le message d'erreur upload
     */
    private static function getUploadError($code) {
        $errors = [
            UPLOAD_ERR_OK => 'Pas d\'erreur',
            UPLOAD_ERR_INI_SIZE => 'Fichier trop volumineux (dépassement php.ini)',
            UPLOAD_ERR_FORM_SIZE => 'Fichier trop volumineux (dépassement formulaire)',
            UPLOAD_ERR_PARTIAL => 'Fichier partiellement uploadé',
            UPLOAD_ERR_NO_FILE => 'Aucun fichier uploadé',
            UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant',
            UPLOAD_ERR_CANT_WRITE => 'Impossible d\'écrire le fichier',
            UPLOAD_ERR_EXTENSION => 'Upload arrêté par une extension PHP',
        ];
        return $errors[$code] ?? 'Erreur inconnue';
    }

    /**
     * Formater les bytes en taille lisible
     */
    private static function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Générer un nom de fichier sécurisé
     */
    public static function sanitizeFileName($originalName) {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        
        // Nettoyer le nom de fichier
        $baseName = preg_replace('/[^a-zA-Z0-9._-]/', '', $baseName);
        $baseName = trim($baseName, '.');
        
        if (empty($baseName)) {
            $baseName = 'file';
        }
        
        // Générer un nom unique
        $uniqueName = md5(uniqid()) . '_' . substr($baseName, 0, 20) . '.' . $extension;
        
        return $uniqueName;
    }
}
