-- Stundenplan-Eintraege (Lehrer-Klasse-Zuordnung pro Zeitslot)
CREATE TABLE IF NOT EXISTS timetable_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    timetable_setting_id INT NOT NULL,
    class_id INT NOT NULL,
    teacher_id INT NOT NULL,
    day_of_week TINYINT NOT NULL COMMENT '1=Montag, 2=Dienstag, ..., 6=Samstag',
    slot_number INT NOT NULL COMMENT 'Einheitsnummer (1, 2, 3, ...)',
    subject VARCHAR(100) NULL COMMENT 'Fach',
    room VARCHAR(50) NULL COMMENT 'Raum',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_slot_teacher (timetable_setting_id, class_id, teacher_id, day_of_week, slot_number),
    INDEX idx_teacher_conflict (timetable_setting_id, teacher_id, day_of_week, slot_number),
    INDEX idx_class_schedule (timetable_setting_id, class_id, day_of_week, slot_number),
    CONSTRAINT fk_timetable_slots_setting FOREIGN KEY (timetable_setting_id) REFERENCES timetable_settings(id) ON DELETE CASCADE,
    CONSTRAINT fk_timetable_slots_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    CONSTRAINT fk_timetable_slots_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
