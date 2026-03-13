CREATE TABLE IF NOT EXISTS absences_students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    date_from DATE NOT NULL,
    date_to DATE NOT NULL,
    excused ENUM('ja', 'nein', 'offen') NOT NULL DEFAULT 'offen',
    reason VARCHAR(500) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_abs_student (student_id),
    INDEX idx_abs_student_dates (date_from, date_to),
    INDEX idx_abs_student_excused (excused)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
