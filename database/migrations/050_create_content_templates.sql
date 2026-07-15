-- Migration 050: Unterrichtsinhalt-Vorlagen (wiederverwendbare Klassenbuch-Inhalte)
-- Speichert vorgefertigte Themen/Notizen, die per Klick in einen Klassenbucheintrag uebernommen werden.
-- owner_user_id: NULL = schulweit geteilt, sonst persoenliche Vorlage der jeweiligen Nutzer:in.

CREATE TABLE IF NOT EXISTS content_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_user_id INT DEFAULT NULL COMMENT 'NULL = schulweit geteilt, sonst persoenlich',
    category VARCHAR(100) DEFAULT NULL COMMENT 'Freitext-Oberkategorie, z. B. Mathematik',
    topic VARCHAR(500) NOT NULL COMMENT 'Thema, das ins Klassenbuch uebernommen wird',
    notes TEXT DEFAULT NULL COMMENT 'Optionale Notizen',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ct_owner (owner_user_id),
    INDEX idx_ct_category (category),
    CONSTRAINT fk_ct_owner FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
