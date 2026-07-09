-- Aufsichtspunkte eines Pausenaufsichtsplans (die Zeilen, z.B. Tor, Sandkasten)
CREATE TABLE IF NOT EXISTS supervision_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plan_id INT NOT NULL,
    name VARCHAR(120) NOT NULL COMMENT 'Bezeichnung des Aufsichtspunkts',
    sort_order INT NOT NULL DEFAULT 0 COMMENT 'Reihenfolge der Aufsichtspunkte',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_supervision_locations_plan (plan_id, sort_order),
    CONSTRAINT fk_supervision_locations_plan FOREIGN KEY (plan_id) REFERENCES supervision_plans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
