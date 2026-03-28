-- Migration 030: Zeugnis-Bilder
-- Tracks uploaded images (logos etc.) for certificate templates

CREATE TABLE IF NOT EXISTS zeugnis_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_id INT NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL UNIQUE,
    mime_type VARCHAR(100) NOT NULL,
    file_size INT UNSIGNED NOT NULL COMMENT 'File size in bytes',
    uploaded_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_zimg_template (template_id),
    CONSTRAINT fk_zimg_template FOREIGN KEY (template_id) REFERENCES zeugnis_templates(id) ON DELETE CASCADE,
    CONSTRAINT fk_zimg_uploader FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
