-- Reparatur: Falls 015_create_lists.sql nur teilweise ausgefuehrt wurde
CREATE TABLE IF NOT EXISTS list_rows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    list_id INT NOT NULL,
    label VARCHAR(255) DEFAULT NULL,
    position INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_list_rows_list_pos (list_id, position),
    FOREIGN KEY (list_id) REFERENCES lists(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
