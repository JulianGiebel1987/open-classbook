CREATE TABLE IF NOT EXISTS classbook_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    teacher_id INT NOT NULL,
    entry_date DATE NOT NULL,
    lesson TINYINT NOT NULL COMMENT 'Unterrichtsstunde 1-10',
    topic VARCHAR(500) NOT NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    INDEX idx_classbook_class_date (class_id, entry_date),
    INDEX idx_classbook_teacher (teacher_id),
    INDEX idx_classbook_date (entry_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
