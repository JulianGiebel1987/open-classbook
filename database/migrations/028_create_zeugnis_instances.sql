-- Migration 028: Zeugnis-Instanzen
-- Individual certificate instances created from templates for specific students

CREATE TABLE IF NOT EXISTS zeugnis_instances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_id INT NOT NULL,
    student_id INT NOT NULL,
    created_by INT NOT NULL,
    title VARCHAR(255) DEFAULT NULL COMMENT 'Optional display title override',
    status ENUM('draft','final') NOT NULL DEFAULT 'draft',
    field_values JSON NOT NULL DEFAULT ('{}') COMMENT 'Map of element_id => filled value',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_zi_template (template_id),
    INDEX idx_zi_student (student_id),
    INDEX idx_zi_created_by (created_by),
    INDEX idx_zi_status (status),
    CONSTRAINT fk_zi_template FOREIGN KEY (template_id) REFERENCES zeugnis_templates(id) ON DELETE CASCADE,
    CONSTRAINT fk_zi_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    CONSTRAINT fk_zi_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
