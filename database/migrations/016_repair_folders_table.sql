-- Reparatur: Falls 014_create_files.sql nur teilweise ausgefuehrt wurde
-- (Multi-Statement-Problem in PDO::exec mit ATTR_EMULATE_PREPARES=false),
-- werden die fehlenden Tabellen hier nachtraeglich erstellt.

CREATE TABLE IF NOT EXISTS folders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    parent_id INT DEFAULT NULL,
    owner_id INT DEFAULT NULL,
    is_shared TINYINT(1) NOT NULL DEFAULT 0,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_folders_parent (parent_id),
    INDEX idx_folders_owner (owner_id),
    INDEX idx_folders_shared (is_shared),
    UNIQUE KEY uk_folder_name (name, parent_id, owner_id, is_shared),
    FOREIGN KEY (parent_id) REFERENCES folders(id) ON DELETE CASCADE,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
