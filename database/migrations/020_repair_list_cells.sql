-- Reparatur: Falls 015_create_lists.sql nur teilweise ausgefuehrt wurde
CREATE TABLE IF NOT EXISTS list_cells (
    id INT AUTO_INCREMENT PRIMARY KEY,
    list_id INT NOT NULL,
    row_id INT NOT NULL,
    column_id INT NOT NULL,
    value TEXT DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_cell (row_id, column_id),
    INDEX idx_list_cells_list (list_id),
    FOREIGN KEY (list_id) REFERENCES lists(id) ON DELETE CASCADE,
    FOREIGN KEY (row_id) REFERENCES list_rows(id) ON DELETE CASCADE,
    FOREIGN KEY (column_id) REFERENCES list_columns(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
