-- Veroeffentlichungsstatus fuer Schulbegleiter:innen-Vertretungen.
-- Erst nach dem Veroeffentlichen sehen die eingeteilten Ersatz-Begleitungen
-- ihre Vertretungen in "Meine Vertretungen".
ALTER TABLE aide_substitutions
    ADD COLUMN published_at DATETIME NULL DEFAULT NULL COMMENT 'Zeitpunkt der Veroeffentlichung (NULL = Entwurf)' AFTER notes,
    ADD COLUMN published_by INT NULL DEFAULT NULL COMMENT 'Veroeffentlicht von (User)' AFTER published_at,
    ADD INDEX idx_aide_sub_published (published_at),
    ADD CONSTRAINT fk_aide_sub_published_by FOREIGN KEY (published_by) REFERENCES users(id) ON DELETE SET NULL;
