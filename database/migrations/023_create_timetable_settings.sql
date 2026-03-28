-- Stundenplan-Konfiguration (Raster-Einstellungen pro Schuljahr)
CREATE TABLE IF NOT EXISTS timetable_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_year VARCHAR(9) NOT NULL,
    unit_duration INT NOT NULL DEFAULT 45 COMMENT 'Einheitsdauer in Minuten (30, 45, 60)',
    units_per_day INT NOT NULL DEFAULT 8 COMMENT 'Anzahl Einheiten pro Tag',
    day_start_time TIME NOT NULL DEFAULT '08:00:00' COMMENT 'Unterrichtsbeginn',
    days_of_week JSON NOT NULL COMMENT 'Aktive Wochentage [1=Mo..6=Sa]',
    is_published TINYINT(1) NOT NULL DEFAULT 0,
    published_at DATETIME NULL,
    published_by INT NULL,
    created_by INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_school_year (school_year),
    CONSTRAINT fk_timetable_settings_published_by FOREIGN KEY (published_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_timetable_settings_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
