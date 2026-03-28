-- Vertretungsplaene (Veroeffentlichungsstatus pro Tag)
CREATE TABLE IF NOT EXISTS substitution_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    timetable_setting_id INT NOT NULL,
    date DATE NOT NULL COMMENT 'Datum des Vertretungsplans',
    is_published TINYINT(1) NOT NULL DEFAULT 0,
    published_at DATETIME NULL,
    published_by INT NULL,
    created_by INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_plan_date (timetable_setting_id, date),
    CONSTRAINT fk_subplan_setting FOREIGN KEY (timetable_setting_id) REFERENCES timetable_settings(id) ON DELETE CASCADE,
    CONSTRAINT fk_subplan_published_by FOREIGN KEY (published_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_subplan_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
