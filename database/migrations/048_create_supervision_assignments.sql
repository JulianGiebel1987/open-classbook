-- Lehrkraft-Zuweisungen je Aufsichtspunkt x Wochentag x Pause
CREATE TABLE IF NOT EXISTS supervision_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plan_id INT NOT NULL,
    break_id INT NOT NULL,
    location_id INT NOT NULL,
    day_of_week TINYINT NOT NULL COMMENT '1=Montag, 2=Dienstag, ..., 6=Samstag',
    teacher_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_supervision_assignment (break_id, location_id, day_of_week, teacher_id),
    INDEX idx_supervision_conflict (plan_id, teacher_id, day_of_week, break_id),
    CONSTRAINT fk_supervision_assignments_plan FOREIGN KEY (plan_id) REFERENCES supervision_plans(id) ON DELETE CASCADE,
    CONSTRAINT fk_supervision_assignments_break FOREIGN KEY (break_id) REFERENCES supervision_breaks(id) ON DELETE CASCADE,
    CONSTRAINT fk_supervision_assignments_location FOREIGN KEY (location_id) REFERENCES supervision_locations(id) ON DELETE CASCADE,
    CONSTRAINT fk_supervision_assignments_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
