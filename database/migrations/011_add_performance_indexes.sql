-- Zusaetzliche Indizes fuer Performance-Optimierung

-- Password-Reset-Token schneller finden
CREATE INDEX IF NOT EXISTS idx_users_reset_token ON users (password_reset_token);

-- Schueler nach User-ID schneller finden
CREATE INDEX IF NOT EXISTS idx_students_user ON students (user_id);

-- Klassenbucheintraege: zusammengesetzter Index fuer Klasse + Lehrer
CREATE INDEX IF NOT EXISTS idx_classbook_class_teacher ON classbook_entries (class_id, teacher_id);

-- Fehlzeiten: zusammengesetzter Index fuer Student + Status
CREATE INDEX IF NOT EXISTS idx_abs_student_excused_combined ON absences_students (student_id, excused);

-- Fehlzeiten: zusammengesetzter Index fuer Lehrer + Datumsbereich
CREATE INDEX IF NOT EXISTS idx_abs_teacher_dates_combined ON absences_teachers (teacher_id, date_from, date_to);

-- Audit-Log: zusammengesetzter Index fuer User + Zeitstempel
CREATE INDEX IF NOT EXISTS idx_audit_user_time ON audit_log (user_id, created_at);
