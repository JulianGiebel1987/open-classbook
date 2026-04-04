-- Module settings: global enable/disable and role-specific access
-- All modules are enabled and accessible by default (value '1').

INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
  ('module_timetable',   '1'),
  ('module_substitution','1'),
  ('module_messages',    '1'),
  ('module_lists',       '1'),
  ('module_files',       '1'),
  ('module_templates',   '1'),
  ('module_teacher_absences_schulleitung', '1'),
  ('module_teacher_absences_sekretariat',  '1'),
  ('module_timetable_schulleitung',        '1'),
  ('module_timetable_sekretariat',         '1'),
  ('module_substitution_schulleitung',     '1'),
  ('module_substitution_sekretariat',      '1');
