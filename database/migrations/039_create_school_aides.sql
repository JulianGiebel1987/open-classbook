-- Schulbegleiter:innen-Stammdaten (analog teachers), verknuepft mit users
CREATE TABLE IF NOT EXISTS school_aides (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    firstname VARCHAR(100) NOT NULL,
    lastname VARCHAR(100) NOT NULL,
    comment TEXT DEFAULT NULL COMMENT 'Freitext-Kommentar zur Schulbegleitung',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_aides_user (user_id),
    INDEX idx_aides_name (lastname, firstname)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
