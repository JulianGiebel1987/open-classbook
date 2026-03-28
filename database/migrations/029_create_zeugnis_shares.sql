-- Migration 029: Zeugnis-Freigaben
-- Sharing of certificate instances between teachers

CREATE TABLE IF NOT EXISTS zeugnis_shares (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instance_id INT NOT NULL,
    user_id INT NOT NULL,
    can_edit TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=edit access, 0=read-only',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_zs_instance_user (instance_id, user_id),
    INDEX idx_zs_user (user_id),
    CONSTRAINT fk_zs_instance FOREIGN KEY (instance_id) REFERENCES zeugnis_instances(id) ON DELETE CASCADE,
    CONSTRAINT fk_zs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
