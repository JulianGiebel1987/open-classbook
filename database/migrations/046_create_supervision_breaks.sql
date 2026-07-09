-- Pausenspalten eines Pausenaufsichtsplans (fuer alle Wochentage gleich)
CREATE TABLE IF NOT EXISTS supervision_breaks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plan_id INT NOT NULL,
    label VARCHAR(80) NOT NULL COMMENT 'Bezeichnung der Pause (z.B. 1. Pause)',
    start_time TIME NULL COMMENT 'Beginn der Pause',
    end_time TIME NULL COMMENT 'Ende der Pause',
    sort_order INT NOT NULL DEFAULT 0 COMMENT 'Reihenfolge der Pausenspalten',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_supervision_breaks_plan (plan_id, sort_order),
    CONSTRAINT fk_supervision_breaks_plan FOREIGN KEY (plan_id) REFERENCES supervision_plans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
