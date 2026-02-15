-- ============================================
-- TABLES POUR LA SÉCURITÉ
-- À exécuter sur la base de données
-- ============================================

-- Table pour le Rate Limiting
CREATE TABLE IF NOT EXISTS rate_limits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    identifier VARCHAR(255) UNIQUE NOT NULL,
    attempt_count INT DEFAULT 1,
    last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_identifier (identifier),
    INDEX idx_last_attempt (last_attempt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optionnel: Table pour les sessions (si vous voulez stocker les sessions en DB)
CREATE TABLE IF NOT EXISTS sessions (
    session_id VARCHAR(255) PRIMARY KEY,
    user_id INT,
    data LONGTEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    expires_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bonus: Index sur les tables existantes pour améliorer la performance
ALTER TABLE utilisateurs ADD INDEX IF NOT EXISTS idx_email (email);
ALTER TABLE utilisateurs ADD INDEX IF NOT EXISTS idx_role (role);
ALTER TABLE utilisateurs ADD INDEX IF NOT EXISTS idx_actif (actif);

ALTER TABLE clients ADD INDEX IF NOT EXISTS idx_agent_id (agent_id);
ALTER TABLE clients ADD INDEX IF NOT EXISTS idx_date_creation (date_creation);

ALTER TABLE dossiers ADD INDEX IF NOT EXISTS idx_client_id (client_id);
ALTER TABLE dossiers ADD INDEX IF NOT EXISTS idx_statut (statut);
ALTER TABLE dossiers ADD INDEX IF NOT EXISTS idx_date_modification (date_modification);

ALTER TABLE documents ADD INDEX IF NOT EXISTS idx_dossier_id (dossier_id);
ALTER TABLE documents ADD INDEX IF NOT EXISTS idx_statut (statut);
ALTER TABLE documents ADD INDEX IF NOT EXISTS idx_date_upload (date_upload);
