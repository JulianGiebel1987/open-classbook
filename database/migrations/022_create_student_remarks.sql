CREATE TABLE IF NOT EXISTS student_remarks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    class_id INT NOT NULL,
    teacher_id INT NOT NULL,
    remark TEXT NOT NULL,
    remark_date DATE NOT NULL COMMENT 'Datum der Bemerkung (kann von created_at abweichen)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id)   REFERENCES classes(id)  ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    INDEX idx_remarks_student (student_id),
    INDEX idx_remarks_class   (class_id),
    INDEX idx_remarks_teacher (teacher_id),
    INDEX idx_remarks_date    (remark_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
