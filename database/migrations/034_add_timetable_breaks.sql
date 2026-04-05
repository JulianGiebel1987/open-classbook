-- Pausen-Konfiguration fuer Stundenplaene
-- Format: JSON-Array [{after_slot, duration, label}, ...]
ALTER TABLE timetable_settings
ADD COLUMN breaks JSON NULL COMMENT 'Pausen-Konfiguration [{after_slot, duration, label}]'
AFTER days_of_week;
