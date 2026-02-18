-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 10/02/2026 às 20:06
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `doitly_db`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `achievements`
--

CREATE TABLE `achievements` (
  `id` int(10) UNSIGNED NOT NULL,
  `slug` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `badge_color` varchar(7) DEFAULT '#FFD700',
  `criteria_type` enum('streak','total_completions','habits_count','perfect_week','perfect_month') NOT NULL,
  `criteria_value` int(10) UNSIGNED NOT NULL COMMENT 'Valor necessário para desbloquear',
  `points` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Pontos ganhos ao desbloquear',
  `rarity` enum('common','rare','epic','legendary') NOT NULL DEFAULT 'common',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Conquistas disponíveis no sistema';

--
-- Despejando dados para a tabela `achievements`
--

INSERT INTO `achievements` (`id`, `slug`, `name`, `description`, `icon`, `badge_color`, `criteria_type`, `criteria_value`, `points`, `rarity`, `is_active`, `created_at`) VALUES
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
(18, 'unstoppable', 'Imparável', 'Mantenha um streak de 100 dias', 'rocket', '#ff5757', 'streak', 100, 500, 'legendary', 1, '2026-02-10 17:49:42');

-- --------------------------------------------------------

--
-- Estrutura para tabela `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `slug` varchar(50) NOT NULL COMMENT 'Identificador único da categoria',
  `icon` varchar(50) DEFAULT NULL COMMENT 'Nome do ícone (ex: heart, book, dumbbell)',
  `color` varchar(7) DEFAULT '#4a74ff' COMMENT 'Cor em hexadecimal',
  `description` text DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Categoria padrão do sistema',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Categorias de hábitos';

--
-- Despejando dados para a tabela `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `icon`, `color`, `description`, `is_system`, `created_at`) VALUES
(1, 'Saúde', 'saude', 'heart', '#ff5757', 'Hábitos relacionados à saúde física e mental', 1, '2026-02-10 17:49:42'),
(2, 'Exercícios', 'exercicios', 'dumbbell', '#59d186', 'Atividades físicas e esportivas', 1, '2026-02-10 17:49:42'),
(3, 'Estudos', 'estudos', 'book', '#4a74ff', 'Aprendizado e desenvolvimento pessoal', 1, '2026-02-10 17:49:42'),
(4, 'Trabalho', 'trabalho', 'briefcase', '#eed27a', 'Produtividade e carreira profissional', 1, '2026-02-10 17:49:42'),
(5, 'Mindfulness', 'mindfulness', 'brain', '#9b59b6', 'Meditação, yoga e bem-estar mental', 1, '2026-02-10 17:49:42'),
(6, 'Criatividade', 'criatividade', 'palette', '#e67e22', 'Arte, música e expressão criativa', 1, '2026-02-10 17:49:42'),
(7, 'Social', 'social', 'users', '#3498db', 'Relacionamentos e vida social', 1, '2026-02-10 17:49:42'),
(8, 'Finanças', 'financas', 'dollar-sign', '#27ae60', 'Gestão financeira e economia', 1, '2026-02-10 17:49:42'),
(9, 'Leitura', 'leitura', 'book-open', '#8e44ad', 'Hábitos de leitura', 1, '2026-02-10 17:49:42'),
(10, 'Outros', 'outros', 'star', '#95a5a6', 'Outros hábitos personalizados', 1, '2026-02-10 17:49:42');

-- --------------------------------------------------------

--
-- Estrutura para tabela `habits`
--

CREATE TABLE `habits` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED DEFAULT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL COMMENT 'Emoji ou nome do ícone',
  `color` varchar(7) DEFAULT '#4a74ff' COMMENT 'Cor personalizada em hexadecimal',
  `frequency` enum('daily','weekly','custom') NOT NULL DEFAULT 'daily' COMMENT 'Frequência do hábito',
  `target_days` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Dias da semana para hábitos semanais [0-6, 0=domingo]' CHECK (json_valid(`target_days`)),
  `time_of_day` enum('morning','afternoon','evening','anytime') DEFAULT 'anytime' COMMENT 'Período do dia',
  `reminder_time` time DEFAULT NULL COMMENT 'Horário de lembrete',
  `goal_type` enum('completion','quantity','duration') NOT NULL DEFAULT 'completion' COMMENT 'Tipo de meta',
  `goal_value` int(10) UNSIGNED DEFAULT 1 COMMENT 'Valor da meta (ex: 30 minutos, 5 repetições)',
  `goal_unit` varchar(20) DEFAULT NULL COMMENT 'Unidade da meta (minutos, vezes, páginas)',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `start_date` date NOT NULL DEFAULT curdate(),
  `end_date` date DEFAULT NULL COMMENT 'Data de término (opcional)',
  `archived_at` timestamp NULL DEFAULT NULL,
  `current_streak` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Sequência atual de dias',
  `longest_streak` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Maior sequência alcançada',
  `total_completions` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Total de conclusões',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Hábitos dos usuários';

-- --------------------------------------------------------

--
-- Estrutura para tabela `habit_completions`
--

CREATE TABLE `habit_completions` (
  `id` int(10) UNSIGNED NOT NULL,
  `habit_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `completion_date` date NOT NULL,
  `completed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `value_achieved` decimal(10,2) DEFAULT NULL COMMENT 'Valor alcançado (para metas quantitativas)',
  `notes` text DEFAULT NULL COMMENT 'Notas opcionais do usuário',
  `mood` enum('great','good','okay','bad') DEFAULT NULL COMMENT 'Como se sentiu ao completar',
  `completed_on_time` tinyint(1) DEFAULT 1 COMMENT 'Completou no horário planejado',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de conclusões de hábitos';

-- --------------------------------------------------------

--
-- Estrutura para tabela `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(128) NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `payload` text NOT NULL,
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sessões ativas dos usuários';

-- --------------------------------------------------------

--
-- Estrutura para tabela `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL COMMENT 'Hash bcrypt da senha',
  `avatar_url` varchar(255) DEFAULT NULL COMMENT 'URL do avatar do usuário',
  `timezone` varchar(50) DEFAULT 'America/Sao_Paulo' COMMENT 'Fuso horário do usuário',
  `level` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Nível atual do usuário',
  `experience_points` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'XP acumulado via conquistas',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Usuário ativo no sistema',
  `email_verified` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Email verificado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabela de usuários do sistema';

--
-- Acionadores `users`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `user_achievements`
--

CREATE TABLE `user_achievements` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `achievement_id` int(10) UNSIGNED NOT NULL,
  `unlocked_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `progress` int(10) UNSIGNED DEFAULT 0 COMMENT 'Progresso atual para conquistas em andamento'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Conquistas desbloqueadas pelos usuários';

-- --------------------------------------------------------

--
-- Estrutura para tabela `user_settings`
--

CREATE TABLE `user_settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `theme` enum('light','dark','auto') NOT NULL DEFAULT 'light',
  `primary_color` varchar(7) NOT NULL DEFAULT '#4A74FF',
  `accent_color` varchar(7) NOT NULL DEFAULT '#59D186',
  `text_scale` decimal(3,2) NOT NULL DEFAULT 1.00,
  `language` varchar(5) NOT NULL DEFAULT 'pt-BR',
  `first_day_of_week` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '0=Domingo, 1=Segunda',
  `notifications_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `email_notifications` tinyint(1) NOT NULL DEFAULT 0,
  `reminder_notifications` tinyint(1) NOT NULL DEFAULT 1,
  `daily_summary_time` time DEFAULT '20:00:00' COMMENT 'Horário do resumo diário',
  `profile_public` tinyint(1) NOT NULL DEFAULT 0,
  `show_stats_public` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Configurações dos usuários';

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_completions_detail`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_completions_detail` (
`id` int(10) unsigned
,`habit_id` int(10) unsigned
,`habit_title` varchar(150)
,`user_id` int(10) unsigned
,`user_name` varchar(100)
,`completion_date` date
,`completed_at` timestamp
,`value_achieved` decimal(10,2)
,`notes` text
,`mood` enum('great','good','okay','bad')
,`completed_on_time` tinyint(1)
,`category_name` varchar(50)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_habits_full`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_habits_full` (
`id` int(10) unsigned
,`user_id` int(10) unsigned
,`user_name` varchar(100)
,`title` varchar(150)
,`description` text
,`icon` varchar(50)
,`color` varchar(7)
,`category_name` varchar(50)
,`category_slug` varchar(50)
,`category_color` varchar(7)
,`frequency` enum('daily','weekly','custom')
,`time_of_day` enum('morning','afternoon','evening','anytime')
,`goal_type` enum('completion','quantity','duration')
,`goal_value` int(10) unsigned
,`goal_unit` varchar(20)
,`current_streak` int(10) unsigned
,`longest_streak` int(10) unsigned
,`total_completions` int(10) unsigned
,`is_active` tinyint(1)
,`start_date` date
,`created_at` timestamp
,`updated_at` timestamp
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_user_stats`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_user_stats` (
`user_id` int(10) unsigned
,`name` varchar(100)
,`email` varchar(150)
,`total_habits` bigint(21)
,`active_habits` bigint(21)
,`total_completions` decimal(32,0)
,`best_streak` decimal(10,0)
,`achievements_unlocked` bigint(21)
,`member_since` timestamp
);

-- --------------------------------------------------------

--
-- Estrutura para view `v_completions_detail`
--
DROP TABLE IF EXISTS `v_completions_detail`;


-- --------------------------------------------------------

--
-- Estrutura para view `v_habits_full`
--
DROP TABLE IF EXISTS `v_habits_full`;


-- --------------------------------------------------------

--
-- Estrutura para view `v_user_stats`
--
DROP TABLE IF EXISTS `v_user_stats`;


--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `achievements`
--
ALTER TABLE `achievements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_slug` (`slug`),
  ADD KEY `idx_criteria_type` (`criteria_type`);

--
-- Índices de tabela `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_slug` (`slug`),
  ADD KEY `idx_is_system` (`is_system`);

--
-- Índices de tabela `habits`
--
ALTER TABLE `habits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_category_id` (`category_id`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_time_of_day` (`time_of_day`),
  ADD KEY `idx_start_date` (`start_date`),
  ADD KEY `idx_user_active` (`user_id`,`is_active`),
  ADD KEY `idx_habits_user_category_active` (`user_id`,`category_id`,`is_active`);

--
-- Índices de tabela `habit_completions`
--
ALTER TABLE `habit_completions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_habit_date` (`habit_id`,`completion_date`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_completion_date` (`completion_date`),
  ADD KEY `idx_habit_date` (`habit_id`,`completion_date`),
  ADD KEY `idx_user_date` (`user_id`,`completion_date`),
  ADD KEY `idx_completions_user_date_range` (`user_id`,`completion_date`,`habit_id`);

--
-- Índices de tabela `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_last_activity` (`last_activity`);

--
-- Índices de tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Índices de tabela `user_achievements`
--
ALTER TABLE `user_achievements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_achievement` (`user_id`,`achievement_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_achievement_id` (`achievement_id`),
  ADD KEY `idx_unlocked_at` (`unlocked_at`);

--
-- Índices de tabela `user_settings`
--
ALTER TABLE `user_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_settings` (`user_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `achievements`
--
ALTER TABLE `achievements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `habits`
--
ALTER TABLE `habits`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `habit_completions`
--
ALTER TABLE `habit_completions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `user_achievements`
--
ALTER TABLE `user_achievements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `user_settings`
--
ALTER TABLE `user_settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `habits`
--
ALTER TABLE `habits`
  ADD CONSTRAINT `fk_habits_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_habits_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `habit_completions`
--
ALTER TABLE `habit_completions`
  ADD CONSTRAINT `fk_completions_habit` FOREIGN KEY (`habit_id`) REFERENCES `habits` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_completions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `fk_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `user_achievements`
--
ALTER TABLE `user_achievements`
  ADD CONSTRAINT `fk_user_achievements_achievement` FOREIGN KEY (`achievement_id`) REFERENCES `achievements` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user_achievements_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `user_settings`
--
ALTER TABLE `user_settings`
  ADD CONSTRAINT `fk_settings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
