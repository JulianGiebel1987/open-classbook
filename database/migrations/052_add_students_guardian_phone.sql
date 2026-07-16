-- Telefonkontakt der Erziehungsberechtigten
-- Ergaenzt die students-Tabelle um eine optionale Telefonnummer. Sie wird in der
-- Klassenbuch-Uebersicht als klickbarer tel:-Link angezeigt und ergaenzt die bereits
-- vorhandene guardian_email als zweiten Kontaktweg.
ALTER TABLE students
    ADD COLUMN IF NOT EXISTS guardian_phone VARCHAR(30) DEFAULT NULL AFTER guardian_email;
