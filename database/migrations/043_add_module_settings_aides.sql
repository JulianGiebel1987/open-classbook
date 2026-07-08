-- Modul-Schalter fuer das Schulbegleiter:innen-Modul (analog 033)
-- Global aktiviert und fuer Schulleitung/Sekretariat zugaenglich per Default.
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
  ('module_school_aides',              '1'),
  ('module_school_aides_schulleitung', '1'),
  ('module_school_aides_sekretariat',  '1');
