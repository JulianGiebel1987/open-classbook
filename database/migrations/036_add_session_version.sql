-- Session-Version fuer Invalidierung aktiver Sessions (z.B. nach Passwort-Reset)
-- Jede Session speichert die session_version zum Zeitpunkt des Logins.
-- AuthMiddleware vergleicht mit dem DB-Wert; bei Mismatch wird die Session zerstoert.
ALTER TABLE users
    ADD COLUMN session_version INT NOT NULL DEFAULT 0;
