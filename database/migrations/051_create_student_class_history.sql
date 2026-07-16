CREATE TABLE IF NOT EXISTS student_class_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    from_class_id INT DEFAULT NULL,
    to_class_id INT NOT NULL,
    changed_by INT DEFAULT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (from_class_id) REFERENCES classes(id) ON DELETE SET NULL,
    FOREIGN KEY (to_class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_sch_student (student_id),
    INDEX idx_sch_to_class (to_class_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
