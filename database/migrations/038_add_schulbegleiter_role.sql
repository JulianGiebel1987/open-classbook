-- Neuer Account-Typ: Schulbegleiter:innen (Integrationshilfen)
-- Ergaenzt das role-ENUM der users-Tabelle um 'schulbegleiter'.
ALTER TABLE users
    MODIFY COLUMN role ENUM('admin', 'schulleitung', 'sekretariat', 'lehrer', 'schueler', 'schulbegleiter') NOT NULL;
