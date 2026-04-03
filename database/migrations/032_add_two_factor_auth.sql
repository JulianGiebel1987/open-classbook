-- Migration: Zwei-Faktor-Authentifizierung
ALTER TABLE users
    ADD COLUMN two_factor_method ENUM('none', 'email', 'totp') NOT NULL DEFAULT 'none' AFTER must_change_password,
    ADD COLUMN two_factor_secret VARCHAR(500) DEFAULT NULL AFTER two_factor_method,
    ADD COLUMN two_factor_confirmed_at DATETIME DEFAULT NULL AFTER two_factor_secret,
    ADD COLUMN two_factor_recovery_codes TEXT DEFAULT NULL AFTER two_factor_confirmed_at;

-- Tabelle fuer 2FA-Verifizierungscodes (E-Mail-Codes)
CREATE TABLE IF NOT EXISTS two_factor_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    code VARCHAR(255) NOT NULL,
    type ENUM('email', 'recovery') NOT NULL DEFAULT 'email',
    expires_at DATETIME NOT NULL,
    used_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_2fa_user (user_id),
    INDEX idx_2fa_expires (expires_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
