-- Index auf password_reset_expires fuer schnellen Cleanup
-- Cronjob UPDATE ... WHERE password_reset_expires < NOW() wird dadurch performant
CREATE INDEX IF NOT EXISTS idx_users_password_reset_expires
    ON users (password_reset_expires);
