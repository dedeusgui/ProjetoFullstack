-- Catálogo de conquistas alinhado ao banco (tabela achievements)
-- nome do arquivo conforme solicitado: achivements.sql

INSERT INTO `achievements` (`id`, `slug`, `name`, `description`, `icon`, `badge_color`, `criteria_type`, `criteria_value`, `points`, `rarity`, `is_active`, `created_at`)
VALUES
(1, 'first-step', 'Primeiro Passo', 'Complete seu primeiro hábito', 'flag', '#59d186', 'total_completions', 1, 10, 'common', 1, '2026-02-10 17:49:42'),
(2, 'daily-rhythm-3', 'Ritmo Inicial', 'Mantenha um streak de 3 dias', 'fire', '#ff9500', 'streak', 3, 20, 'common', 1, '2026-02-10 17:49:42'),
(3, 'week-warrior', 'Guerreiro Semanal', 'Mantenha um streak de 7 dias', 'fire', '#ff9500', 'streak', 7, 50, 'rare', 1, '2026-02-10 17:49:42'),
(4, 'daily-rhythm-14', 'Foco de 2 Semanas', 'Mantenha um streak de 14 dias', 'trophy', '#FFD700', 'streak', 14, 90, 'rare', 1, '2026-02-10 17:49:42'),
(5, 'month-master', 'Mestre do Mês', 'Mantenha um streak de 30 dias', 'trophy', '#FFD700', 'streak', 30, 200, 'epic', 1, '2026-02-10 17:49:42'),
(6, 'daily-rhythm-60', 'Lenda da Rotina', 'Mantenha um streak de 60 dias', 'rocket', '#ff5757', 'streak', 60, 350, 'legendary', 1, '2026-02-10 17:49:42'),
(7, 'focus-10', 'Meta 10', 'Complete 10 hábitos', 'star', '#9b59b6', 'total_completions', 10, 30, 'common', 1, '2026-02-10 17:49:42'),
(8, 'century-club', 'Clube dos 100', 'Complete 100 hábitos', 'star', '#9b59b6', 'total_completions', 100, 150, 'rare', 1, '2026-02-10 17:49:42'),
(9, 'focus-250', 'Maratonista 250', 'Complete 250 hábitos', 'award', '#4a74ff', 'total_completions', 250, 280, 'epic', 1, '2026-02-10 17:49:42'),
(10, 'focus-500', 'Elite 500', 'Complete 500 hábitos', 'gem', '#3498db', 'total_completions', 500, 500, 'legendary', 1, '2026-02-10 17:49:42'),
(11, 'dedication', 'Dedicação Total', 'Complete 1000 hábitos', 'gem', '#3498db', 'total_completions', 1000, 1000, 'legendary', 1, '2026-02-10 17:49:42'),
(12, 'builder-3', 'Planejador', 'Crie 3 hábitos diferentes', 'collection', '#e67e22', 'habits_count', 3, 25, 'common', 1, '2026-02-10 17:49:42'),
(13, 'habit-collector', 'Colecionador de Hábitos', 'Crie 10 hábitos diferentes', 'collection', '#e67e22', 'habits_count', 10, 75, 'rare', 1, '2026-02-10 17:49:42'),
(14, 'builder-20', 'Arquiteto de Rotina', 'Crie 20 hábitos diferentes', 'collection', '#e67e22', 'habits_count', 20, 180, 'epic', 1, '2026-02-10 17:49:42'),
(15, 'perfect-week', 'Semana Perfeita', 'Complete todos os hábitos por 7 dias seguidos', 'award', '#4a74ff', 'perfect_week', 1, 100, 'epic', 1, '2026-02-10 17:49:42'),
(16, 'perfect-2-weeks', '14 Dias Sem Falhar', 'Complete todos os hábitos por 14 dias seguidos', 'award', '#4a74ff', 'perfect_week', 2, 230, 'epic', 1, '2026-02-10 17:49:42'),
(17, 'perfect-month', 'Mês Perfeito', 'Complete todos os hábitos por 30 dias seguidos', 'award', '#4a74ff', 'perfect_month', 1, 420, 'legendary', 1, '2026-02-10 17:49:42'),
(18, 'unstoppable', 'Imparável', 'Mantenha um streak de 100 dias', 'rocket', '#ff5757', 'streak', 100, 500, 'legendary', 1, '2026-02-10 17:49:42')
ON DUPLICATE KEY UPDATE
    `slug` = VALUES(`slug`),
    `name` = VALUES(`name`),
    `description` = VALUES(`description`),
    `icon` = VALUES(`icon`),
    `badge_color` = VALUES(`badge_color`),
    `criteria_type` = VALUES(`criteria_type`),
    `criteria_value` = VALUES(`criteria_value`),
    `points` = VALUES(`points`),
    `rarity` = VALUES(`rarity`),
    `is_active` = VALUES(`is_active`),
    `created_at` = VALUES(`created_at`);
