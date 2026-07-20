-- Migration 053: E-Mail als zentraler Identifikator
--
-- Ergaenzt Spalten fuer die E-Mail-Verifizierung (Double-Opt-in) und die
-- Self-Service-E-Mail-Aenderung. Es wird BEWUSST keine UNIQUE-Constraint auf
-- users.email gesetzt: Schueler:innen speichern die (nicht eindeutige)
-- Erziehungsberechtigten-E-Mail in users.email; Eindeutigkeit fuer die
-- E-Mail-Login-Rollen wird auf Anwendungsebene erzwungen (User::emailExists()).

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS email_verified_at DATETIME DEFAULT NULL AFTER email;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS email_verification_token VARCHAR(255) DEFAULT NULL AFTER email_verified_at;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS email_verification_expires DATETIME DEFAULT NULL AFTER email_verification_token;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS pending_email VARCHAR(255) DEFAULT NULL AFTER email_verification_expires;

CREATE INDEX IF NOT EXISTS idx_users_email_verify ON users (email_verification_token);
