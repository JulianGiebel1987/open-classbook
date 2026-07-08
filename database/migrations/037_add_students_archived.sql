-- Archivierung von Schueler:innen (Soft-Delete)
-- Statt Schueler:innen samt Historie (Fehlzeiten, Bemerkungen) hart zu loeschen,
-- werden sie in der Regel archiviert: archived_at wird gesetzt, der verknuepfte
-- Benutzer-Account wird deaktiviert. Archivierte Schueler:innen erscheinen nicht
-- mehr in den Klassenlisten, bleiben aber inklusive Historie erhalten und koennen
-- wiederhergestellt werden. Hartes Loeschen bleibt Admins vorbehalten.
ALTER TABLE students
    ADD COLUMN IF NOT EXISTS archived_at DATETIME DEFAULT NULL;

CREATE INDEX IF NOT EXISTS idx_students_archived
    ON students (archived_at);
