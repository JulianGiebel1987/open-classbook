-- Vertretungsbedarfe fuer Schulbegleiter:innen (schueler-/abwesenheitsbasiert)
-- Faellt eine Begleitung aus, entsteht pro begleitetem Kind ein Vertretungsbedarf
-- mit Prioritaet (1 = sehr hoch ... 4 = niedrig) und optionaler Ersatz-Begleitung.
CREATE TABLE IF NOT EXISTS aide_substitutions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date_from DATE NOT NULL,
    date_to DATE NOT NULL,
    absent_aide_id INT NOT NULL COMMENT 'Abwesende Schulbegleitung',
    student_id INT NOT NULL COMMENT 'Zu begleitendes Kind',
    substitute_aide_id INT NULL COMMENT 'Ersatz-Begleitung (NULL = offen/unbesetzt)',
    absence_aide_id INT NULL COMMENT 'Verknuepfung zur Krankmeldung',
    priority TINYINT NOT NULL DEFAULT 3 COMMENT '1=sehr hoch, 2=hoch, 3=mittel, 4=niedrig',
    status ENUM('offen', 'geplant', 'erledigt') NOT NULL DEFAULT 'offen',
    notes TEXT DEFAULT NULL,
    created_by INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_aide_sub (absent_aide_id, student_id, date_from),
    INDEX idx_aide_sub_dates (date_from, date_to),
    INDEX idx_aide_sub_substitute (substitute_aide_id),
    INDEX idx_aide_sub_priority (priority),
    CONSTRAINT fk_aide_sub_absent FOREIGN KEY (absent_aide_id) REFERENCES school_aides(id) ON DELETE CASCADE,
    CONSTRAINT fk_aide_sub_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    CONSTRAINT fk_aide_sub_substitute FOREIGN KEY (substitute_aide_id) REFERENCES school_aides(id) ON DELETE SET NULL,
    CONSTRAINT fk_aide_sub_absence FOREIGN KEY (absence_aide_id) REFERENCES absences_school_aides(id) ON DELETE SET NULL,
    CONSTRAINT fk_aide_sub_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
