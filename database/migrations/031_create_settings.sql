-- Migration: Settings-Tabelle fuer systemweite Einstellungen
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_settings_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Standard-2FA-Einstellungen
INSERT INTO settings (setting_key, setting_value) VALUES
    ('two_factor_enabled', '0'),
    ('two_factor_enforce_roles', '[]'),
    ('two_factor_code_lifetime', '600'),
    ('two_factor_max_attempts', '5');
