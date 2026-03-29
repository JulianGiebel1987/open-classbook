-- Migration 027: Zeugnisvorlagen
-- Stores certificate template definitions with JSON canvas data

CREATE TABLE IF NOT EXISTS zeugnis_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    school_year VARCHAR(9) DEFAULT NULL COMMENT 'e.g. 2025/2026',
    grade_levels VARCHAR(255) DEFAULT NULL COMMENT 'Comma-separated grade levels, e.g. "5,6,7"',
    page_orientation ENUM('P','L') NOT NULL DEFAULT 'P' COMMENT 'P=Portrait/Hochformat, L=Landscape/Querformat',
    page_format ENUM('A4','A3') NOT NULL DEFAULT 'A4',
    template_canvas JSON NOT NULL COMMENT 'Array of page objects, each with array of element descriptors',
    status ENUM('draft','published') NOT NULL DEFAULT 'draft',
    created_by INT NOT NULL,
    updated_by INT DEFAULT NULL,
    published_at DATETIME DEFAULT NULL,
    published_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_zt_status (status),
    INDEX idx_zt_school_year (school_year),
    INDEX idx_zt_created_by (created_by),
    CONSTRAINT fk_zt_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_zt_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_zt_published_by FOREIGN KEY (published_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
