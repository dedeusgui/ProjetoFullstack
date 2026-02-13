-- Migração: preferências visuais personalizáveis por usuário
-- Execute no banco doitly_db

ALTER TABLE user_settings
  ADD COLUMN primary_color VARCHAR(7) NOT NULL DEFAULT '#4A74FF' AFTER theme,
  ADD COLUMN accent_color VARCHAR(7) NOT NULL DEFAULT '#59D186' AFTER primary_color,
  ADD COLUMN text_scale DECIMAL(3,2) NOT NULL DEFAULT 1.00 AFTER accent_color;

-- Ajuste opcional para padronizar tema atual em usuários já existentes
UPDATE user_settings
SET
  primary_color = COALESCE(NULLIF(primary_color, ''), '#4A74FF'),
  accent_color = COALESCE(NULLIF(accent_color, ''), '#59D186'),
  text_scale = CASE
    WHEN text_scale < 0.90 THEN 0.90
    WHEN text_scale > 1.20 THEN 1.20
    ELSE text_scale
  END;
