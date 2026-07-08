-- n:m-Zuweisung Schulbegleiter:innen <-> Schueler:innen (analog class_teacher)
-- Eine Begleitung kann mehrere Kinder begleiten; ein Kind kann mehrere Begleitungen haben.
CREATE TABLE IF NOT EXISTS aide_student (
    aide_id INT NOT NULL,
    student_id INT NOT NULL,
    PRIMARY KEY (aide_id, student_id),
    FOREIGN KEY (aide_id) REFERENCES school_aides(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    INDEX idx_aide_student_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
