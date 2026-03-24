-- Reparatur: Falls 015_create_lists.sql nur teilweise ausgefuehrt wurde
-- (Multi-Statement-Problem in PDO::exec), wird list_columns nachtraeglich erstellt.
CREATE TABLE IF NOT EXISTS list_columns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    list_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    type ENUM('text', 'checkbox', 'number', 'date', 'select', 'rating') NOT NULL DEFAULT 'text',
    options JSON DEFAULT NULL,
    position INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_list_columns_list_pos (list_id, position),
    FOREIGN KEY (list_id) REFERENCES lists(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
