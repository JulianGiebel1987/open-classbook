CREATE TABLE IF NOT EXISTS absences_teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    date_from DATE NOT NULL,
    date_to DATE NOT NULL,
    type ENUM('krank', 'fortbildung', 'sonstiges') NOT NULL DEFAULT 'krank',
    reason VARCHAR(500) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_abs_teacher (teacher_id),
    INDEX idx_abs_teacher_dates (date_from, date_to),
    INDEX idx_abs_teacher_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
