ALTER TABLE users
    ADD COLUMN IF NOT EXISTS level INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Nível atual do usuário' AFTER timezone,
    ADD COLUMN IF NOT EXISTS experience_points INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'XP acumulado via conquistas' AFTER level;
