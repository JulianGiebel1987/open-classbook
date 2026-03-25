-- =====================================================================
-- Migration 016: Datenschutz-Retention-Policies (DSGVO Art. 5 Abs. 1 lit. e)
-- =====================================================================
-- Automatische Loeschroutinen fuer personenbezogene Protokolldaten.
-- Voraussetzung: MariaDB Event-Scheduler aktiviert (event_scheduler=ON).
-- =====================================================================

-- Event-Scheduler aktivieren (Hinweis fuer Serveradministrator):
-- SET GLOBAL event_scheduler = ON;

-- Login-Versuche nach 30 Tagen loeschen
-- (IP-Adressen sind pseudonymisiert, trotzdem kurzfristig loeschen)
CREATE EVENT IF NOT EXISTS evt_purge_login_attempts
    ON SCHEDULE EVERY 1 DAY
    STARTS CURRENT_TIMESTAMP
    DO
        DELETE FROM login_attempts
        WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 30 DAY);

-- Audit-Log nach 90 Tagen loeschen
-- (Rechenschaftspflicht Art. 5 Abs. 2 DSGVO - 90 Tage genuegen fuer Schulbetrieb)
CREATE EVENT IF NOT EXISTS evt_purge_audit_log
    ON SCHEDULE EVERY 1 DAY
    STARTS CURRENT_TIMESTAMP
    DO
        DELETE FROM audit_log
        WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- Rate-Limit-Eintraege nach 15 Minuten loeschen
-- (Kuerzere Retention, da nur fuer DDoS-Schutz benoetigt)
CREATE EVENT IF NOT EXISTS evt_purge_rate_limits
    ON SCHEDULE EVERY 15 MINUTE
    STARTS CURRENT_TIMESTAMP
    DO
        DELETE FROM rate_limits
        WHERE requested_at < DATE_SUB(NOW(), INTERVAL 15 MINUTE);

-- Abgelaufene Passwort-Reset-Tokens sofort loeschen
CREATE EVENT IF NOT EXISTS evt_purge_reset_tokens
    ON SCHEDULE EVERY 1 HOUR
    STARTS CURRENT_TIMESTAMP
    DO
        UPDATE users
        SET reset_token = NULL, reset_token_expires = NULL
        WHERE reset_token_expires IS NOT NULL
          AND reset_token_expires < NOW();

-- =====================================================================
-- Hinweis Loeschkonzept (Schulrecht / DSGVO):
-- - Klassenbucheintraege:        2 Jahre nach Schuljahresende (manuell)
-- - Schueler-Fehlzeiten:         3 Jahre nach Schuljahresende (manuell)
-- - Nachrichten:                 2 Jahre (manuell oder per Event)
-- - Login-Attempts:              30 Tage (automatisch, s.o.)
-- - Audit-Log:                   90 Tage (automatisch, s.o.)
-- - Rate-Limits:                 15 Minuten (automatisch, s.o.)
-- - Password-Reset-Tokens:       bei Ablauf (automatisch, s.o.)
-- =====================================================================
