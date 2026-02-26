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

CREATE DATABASE IF NOT EXISTS `doitly` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `doitly`;

--
-- Banco de dados: `doitly`
--

DELIMITER $$
--
-- Procedimentos
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_complete_habit` (IN `p_habit_id` INT UNSIGNED, IN `p_user_id` INT UNSIGNED, IN `p_completion_date` DATE, IN `p_value_achieved` DECIMAL(10,2), IN `p_notes` TEXT, IN `p_mood` VARCHAR(10))   BEGIN
    DECLARE v_yesterday DATE;
    DECLARE v_yesterday_completed BOOLEAN;
    DECLARE v_current_streak INT;
    
    -- Inicia transação
    START TRANSACTION;
    
    -- Insere ou atualiza a conclusão
    INSERT INTO habit_completions (
        habit_id, 
        user_id, 
        completion_date, 
        value_achieved, 
        notes, 
        mood
    ) VALUES (
        p_habit_id, 
        p_user_id, 
        p_completion_date, 
        p_value_achieved, 
        p_notes, 
        p_mood
    )
    ON DUPLICATE KEY UPDATE
        value_achieved = p_value_achieved,
        notes = p_notes,
        mood = p_mood,
        completed_at = CURRENT_TIMESTAMP;
    
    -- Calcula streak
    SET v_yesterday = DATE_SUB(p_completion_date, INTERVAL 1 DAY);
    
    SELECT COUNT(*) > 0 INTO v_yesterday_completed
    FROM habit_completions
    WHERE habit_id = p_habit_id 
        AND completion_date = v_yesterday;
    
    -- Atualiza streak
    IF v_yesterday_completed THEN
        -- Continua o streak
        UPDATE habits 
        SET current_streak = current_streak + 1,
            total_completions = total_completions + 1,
            longest_streak = GREATEST(longest_streak, current_streak + 1)
        WHERE id = p_habit_id;
    ELSE
        -- Reinicia o streak
        UPDATE habits 
        SET current_streak = 1,
            total_completions = total_completions + 1,
            longest_streak = GREATEST(longest_streak, 1)
        WHERE id = p_habit_id;
    END IF;
    
    COMMIT;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_get_dashboard_stats` (IN `p_user_id` INT UNSIGNED, IN `p_date` DATE)   BEGIN
    -- Estatísticas do dia
    SELECT 
        COUNT(DISTINCT h.id) AS total_habits_today,
        COUNT(DISTINCT hc.habit_id) AS completed_today,
        ROUND(
            (COUNT(DISTINCT hc.habit_id) * 100.0) / NULLIF(COUNT(DISTINCT h.id), 0), 
            2
        ) AS completion_rate_today,
        COALESCE(MAX(h.current_streak), 0) AS best_current_streak,
        COALESCE(SUM(h.total_completions), 0) AS total_all_time_completions
    FROM habits h
    LEFT JOIN habit_completions hc 
        ON h.id = hc.habit_id 
        AND hc.completion_date = p_date
    WHERE h.user_id = p_user_id 
        AND h.is_active = TRUE;
    
    -- Hábitos por categoria
    SELECT 
        c.name AS category_name,
        c.color AS category_color,
        COUNT(h.id) AS habit_count
    FROM habits h
    LEFT JOIN categories c ON h.category_id = c.id
    WHERE h.user_id = p_user_id 
        AND h.is_active = TRUE
    GROUP BY c.id, c.name, c.color
    ORDER BY habit_count DESC;
    
    -- Progresso dos últimos 7 dias
    SELECT 
        d.date AS completion_date,
        COUNT(DISTINCT hc.habit_id) AS habits_completed,
        COUNT(DISTINCT h.id) AS total_habits
    FROM (
        SELECT DATE_SUB(p_date, INTERVAL n DAY) AS date
        FROM (
            SELECT 0 AS n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 
            UNION SELECT 4 UNION SELECT 5 UNION SELECT 6
        ) numbers
    ) d
    LEFT JOIN habits h 
        ON h.user_id = p_user_id 
        AND h.is_active = TRUE
        AND h.start_date <= d.date
    LEFT JOIN habit_completions hc 
        ON h.id = hc.habit_id 
        AND hc.completion_date = d.date
    GROUP BY d.date
    ORDER BY d.date ASC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_uncomplete_habit` (IN `p_habit_id` INT UNSIGNED, IN `p_completion_date` DATE)   BEGIN
    START TRANSACTION;
    
    -- Remove a conclusão
    DELETE FROM habit_completions
    WHERE habit_id = p_habit_id 
        AND completion_date = p_completion_date;
    
    -- Recalcula o streak (simplificado - pode ser otimizado)
    UPDATE habits
    SET current_streak = (
        SELECT COUNT(DISTINCT completion_date)
        FROM habit_completions
        WHERE habit_id = p_habit_id
            AND completion_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ),
    total_completions = GREATEST(0, total_completions - 1)
    WHERE id = p_habit_id;
    
    COMMIT;
END$$

DELIMITER ;

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
  `frequency` enum('daily','weekly') NOT NULL DEFAULT 'daily' COMMENT 'Frequência do hábito',
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
DELIMITER $$
CREATE TRIGGER `tr_user_after_insert` AFTER INSERT ON `users` FOR EACH ROW BEGIN
    INSERT INTO user_settings (user_id)
    VALUES (NEW.id);
END
$$
DELIMITER ;

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
,`frequency` enum('daily','weekly')
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

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_completions_detail`  AS SELECT `hc`.`id` AS `id`, `hc`.`habit_id` AS `habit_id`, `h`.`title` AS `habit_title`, `hc`.`user_id` AS `user_id`, `u`.`name` AS `user_name`, `hc`.`completion_date` AS `completion_date`, `hc`.`completed_at` AS `completed_at`, `hc`.`value_achieved` AS `value_achieved`, `hc`.`notes` AS `notes`, `hc`.`mood` AS `mood`, `hc`.`completed_on_time` AS `completed_on_time`, `c`.`name` AS `category_name` FROM (((`habit_completions` `hc` join `habits` `h` on(`hc`.`habit_id` = `h`.`id`)) join `users` `u` on(`hc`.`user_id` = `u`.`id`)) left join `categories` `c` on(`h`.`category_id` = `c`.`id`)) ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_habits_full`
--
DROP TABLE IF EXISTS `v_habits_full`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_habits_full`  AS SELECT `h`.`id` AS `id`, `h`.`user_id` AS `user_id`, `u`.`name` AS `user_name`, `h`.`title` AS `title`, `h`.`description` AS `description`, `h`.`icon` AS `icon`, `h`.`color` AS `color`, `c`.`name` AS `category_name`, `c`.`slug` AS `category_slug`, `c`.`color` AS `category_color`, `h`.`frequency` AS `frequency`, `h`.`time_of_day` AS `time_of_day`, `h`.`goal_type` AS `goal_type`, `h`.`goal_value` AS `goal_value`, `h`.`goal_unit` AS `goal_unit`, `h`.`current_streak` AS `current_streak`, `h`.`longest_streak` AS `longest_streak`, `h`.`total_completions` AS `total_completions`, `h`.`is_active` AS `is_active`, `h`.`start_date` AS `start_date`, `h`.`created_at` AS `created_at`, `h`.`updated_at` AS `updated_at` FROM ((`habits` `h` join `users` `u` on(`h`.`user_id` = `u`.`id`)) left join `categories` `c` on(`h`.`category_id` = `c`.`id`)) ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_user_stats`
--
DROP TABLE IF EXISTS `v_user_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_user_stats`  AS SELECT `u`.`id` AS `user_id`, `u`.`name` AS `name`, `u`.`email` AS `email`, count(distinct `h`.`id`) AS `total_habits`, count(distinct case when `h`.`is_active` = 1 then `h`.`id` end) AS `active_habits`, coalesce(sum(`h`.`total_completions`),0) AS `total_completions`, coalesce(max(`h`.`longest_streak`),0) AS `best_streak`, count(distinct `ua`.`achievement_id`) AS `achievements_unlocked`, `u`.`created_at` AS `member_since` FROM ((`users` `u` left join `habits` `h` on(`u`.`id` = `h`.`user_id`)) left join `user_achievements` `ua` on(`u`.`id` = `ua`.`user_id`)) WHERE `u`.`is_active` = 1 GROUP BY `u`.`id`, `u`.`name`, `u`.`email`, `u`.`created_at` ;

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

-- --------------------------------------------------------
-- Extensão: recomendações adaptativas por usuário
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `user_recommendations` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `score` smallint NOT NULL,
  `trend` enum('positive','neutral','negative') NOT NULL DEFAULT 'neutral',
  `risk_level` enum('high_performer','stable','attention','at_risk') NOT NULL DEFAULT 'stable',
  `recommendation_text` text NOT NULL,
  `recommendation_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`recommendation_payload`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_recommendations_user_created` (`user_id`, `created_at`),
  CONSTRAINT `fk_user_recommendations_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Extensao: motor de conquistas v2 + progressao/recompensas
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `achievement_definitions` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` varchar(80) NOT NULL,
  `name` varchar(120) NOT NULL,
  `description` text NOT NULL,
  `icon` varchar(60) DEFAULT NULL,
  `badge_color` varchar(7) DEFAULT '#4a74ff',
  `rarity` enum('common','rare','epic','legendary') NOT NULL DEFAULT 'common',
  `points` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `rule_key` varchar(50) NOT NULL,
  `rule_config_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`rule_config_json`)),
  `sort_order` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `version` smallint UNSIGNED NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_achievement_definitions_slug` (`slug`),
  KEY `idx_achievement_definitions_rule_key` (`rule_key`),
  KEY `idx_achievement_definitions_active_sort` (`is_active`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_achievement_unlocks` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `achievement_definition_id` int(10) UNSIGNED NOT NULL,
  `unlocked_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `awarded_points` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `rule_version` smallint UNSIGNED NOT NULL DEFAULT 1,
  `source` varchar(50) NOT NULL DEFAULT 'live_sync',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_achievement_unlocks_user_achievement` (`user_id`,`achievement_definition_id`),
  KEY `idx_user_achievement_unlocks_user` (`user_id`,`unlocked_at`),
  KEY `idx_user_achievement_unlocks_achievement` (`achievement_definition_id`),
  CONSTRAINT `fk_user_achievement_unlocks_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_user_achievement_unlocks_definition` FOREIGN KEY (`achievement_definition_id`) REFERENCES `achievement_definitions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_achievement_events` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `achievement_definition_id` int(10) UNSIGNED NOT NULL,
  `event_type` enum('unlocked') NOT NULL DEFAULT 'unlocked',
  `event_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `payload_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload_json`)),
  `source` varchar(50) NOT NULL DEFAULT 'live_sync',
  PRIMARY KEY (`id`),
  KEY `idx_user_achievement_events_user_event` (`user_id`,`event_at`),
  KEY `idx_user_achievement_events_achievement` (`achievement_definition_id`,`event_type`),
  CONSTRAINT `fk_user_achievement_events_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_user_achievement_events_definition` FOREIGN KEY (`achievement_definition_id`) REFERENCES `achievement_definitions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `progression_levels` (
  `level` int(10) UNSIGNED NOT NULL,
  `xp_required_total` int(10) UNSIGNED NOT NULL,
  `title` varchar(80) NOT NULL,
  `badge_color` varchar(7) DEFAULT '#4a74ff',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`level`),
  UNIQUE KEY `uq_progression_levels_xp_required_total` (`xp_required_total`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `reward_definitions` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` varchar(80) NOT NULL,
  `reward_type` enum('profile_badge') NOT NULL DEFAULT 'profile_badge',
  `name` varchar(120) NOT NULL,
  `description` text NOT NULL,
  `icon` varchar(60) DEFAULT NULL,
  `visual_config_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`visual_config_json`)),
  `unlock_source_type` enum('level_milestone') NOT NULL DEFAULT 'level_milestone',
  `unlock_source_config_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`unlock_source_config_json`)),
  `sort_order` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_reward_definitions_slug` (`slug`),
  KEY `idx_reward_definitions_active_sort` (`is_active`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_reward_unlocks` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `reward_definition_id` int(10) UNSIGNED NOT NULL,
  `unlocked_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `source` varchar(50) NOT NULL DEFAULT 'progress_sync',
  `source_ref` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_reward_unlocks_user_reward` (`user_id`,`reward_definition_id`),
  KEY `idx_user_reward_unlocks_user` (`user_id`,`unlocked_at`),
  KEY `idx_user_reward_unlocks_reward` (`reward_definition_id`),
  CONSTRAINT `fk_user_reward_unlocks_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_user_reward_unlocks_definition` FOREIGN KEY (`reward_definition_id`) REFERENCES `reward_definitions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_reward_events` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `reward_definition_id` int(10) UNSIGNED NOT NULL,
  `event_type` enum('unlocked') NOT NULL DEFAULT 'unlocked',
  `event_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `payload_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload_json`)),
  `source` varchar(50) NOT NULL DEFAULT 'progress_sync',
  PRIMARY KEY (`id`),
  KEY `idx_user_reward_events_user_event` (`user_id`,`event_at`),
  KEY `idx_user_reward_events_reward` (`reward_definition_id`,`event_type`),
  CONSTRAINT `fk_user_reward_events_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_user_reward_events_definition` FOREIGN KEY (`reward_definition_id`) REFERENCES `reward_definitions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `achievement_definitions` (`id`,`slug`,`name`,`description`,`icon`,`badge_color`,`rarity`,`points`,`rule_key`,`rule_config_json`,`sort_order`,`version`,`is_active`)
VALUES
  (1,'first-step','Primeiro Passo','Complete seu primeiro hábito','flag','#59d186','common',10,'total_completions','{\"threshold\":1}',10,1,1),
  (2,'daily-rhythm-3','Ritmo Inicial','Mantenha um streak de 3 dias','fire','#ff9500','common',20,'streak_days','{\"threshold\":3}',20,1,1),
  (3,'week-warrior','Guerreiro Semanal','Mantenha um streak de 7 dias','fire','#ff9500','rare',50,'streak_days','{\"threshold\":7}',30,1,1),
  (4,'daily-rhythm-14','Foco de 2 Semanas','Mantenha um streak de 14 dias','trophy','#FFD700','rare',90,'streak_days','{\"threshold\":14}',40,1,1),
  (5,'month-master','Mestre do Mês','Mantenha um streak de 30 dias','trophy','#FFD700','epic',200,'streak_days','{\"threshold\":30}',50,1,1),
  (6,'daily-rhythm-60','Lenda da Rotina','Mantenha um streak de 60 dias','rocket','#ff5757','legendary',350,'streak_days','{\"threshold\":60}',60,1,1),
  (7,'focus-10','Meta 10','Complete 10 hábitos','star','#9b59b6','common',30,'total_completions','{\"threshold\":10}',70,1,1),
  (8,'century-club','Clube dos 100','Complete 100 hábitos','star','#9b59b6','rare',150,'total_completions','{\"threshold\":100}',80,1,1),
  (9,'focus-250','Maratonista 250','Complete 250 hábitos','award','#4a74ff','epic',280,'total_completions','{\"threshold\":250}',90,1,1),
  (10,'focus-500','Elite 500','Complete 500 hábitos','gem','#3498db','legendary',500,'total_completions','{\"threshold\":500}',100,1,1),
  (11,'dedication','Dedicação Total','Complete 1000 hábitos','gem','#3498db','legendary',1000,'total_completions','{\"threshold\":1000}',110,1,1),
  (12,'builder-3','Planejador','Crie 3 hábitos diferentes','collection','#e67e22','common',25,'habits_created','{\"threshold\":3}',120,1,1),
  (13,'habit-collector','Colecionador de Hábitos','Crie 10 hábitos diferentes','collection','#e67e22','rare',75,'habits_created','{\"threshold\":10}',130,1,1),
  (14,'builder-20','Arquiteto de Rotina','Crie 20 hábitos diferentes','collection','#e67e22','epic',180,'habits_created','{\"threshold\":20}',140,1,1),
  (15,'perfect-week','Semana Perfeita','Complete todos os hábitos agendados por 7 dias seguidos','award','#4a74ff','epic',100,'perfect_days_streak','{\"threshold\":7}',150,1,1),
  (16,'perfect-2-weeks','14 Dias Sem Falhar','Complete todos os hábitos agendados por 14 dias seguidos','award','#4a74ff','epic',230,'perfect_days_streak','{\"threshold\":14}',160,1,1),
  (17,'perfect-month','Mês Perfeito','Complete todos os hábitos agendados por 30 dias seguidos','award','#4a74ff','legendary',420,'perfect_days_streak','{\"threshold\":30}',170,1,1),
  (18,'unstoppable','Imparável','Mantenha um streak de 100 dias','rocket','#ff5757','legendary',500,'streak_days','{\"threshold\":100}',180,1,1),
  (19,'active-days-7','Presença Semanal','Complete hábitos em 7 dias diferentes','calendar','#59d186','common',30,'active_days','{\"threshold\":7}',190,1,1),
  (20,'active-days-30','Presença Mensal','Complete hábitos em 30 dias diferentes','calendar','#4a74ff','rare',120,'active_days','{\"threshold\":30}',200,1,1),
  (21,'active-days-100','Presença Centenária','Complete hábitos em 100 dias diferentes','calendar','#FFD700','epic',260,'active_days','{\"threshold\":100}',210,1,1),
  (22,'category-focus-25','Especialista de Categoria','Complete 25 hábitos em uma mesma categoria','patch-check','#9b59b6','rare',90,'max_category_completions','{\"threshold\":25}',220,1,1),
  (23,'category-focus-100','Mestre de Categoria','Complete 100 hábitos em uma mesma categoria','patch-check','#6c5ce7','epic',220,'max_category_completions','{\"threshold\":100}',230,1,1),
  (24,'weekday-explorer','Semana Completa','Complete hábitos em todos os dias da semana pelo menos uma vez','collection','#00b894','rare',100,'weekday_coverage','{\"threshold\":7}',240,1,1),
  (25,'time-master-3','Rotina Completa','Complete hábitos em manhã, tarde e noite','clock','#0984e3','rare',90,'time_of_day_variety','{\"threshold\":3}',250,1,1),
  (26,'comeback-kid','Volta por Cima','Retorne e complete um hábito após uma pausa de 3+ dias','rocket','#fdcb6e','epic',140,'comeback_count','{\"threshold\":1,\"min_gap_days\":3}',260,1,1)
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `description` = VALUES(`description`),
  `icon` = VALUES(`icon`),
  `badge_color` = VALUES(`badge_color`),
  `rarity` = VALUES(`rarity`),
  `points` = VALUES(`points`),
  `rule_key` = VALUES(`rule_key`),
  `rule_config_json` = VALUES(`rule_config_json`),
  `sort_order` = VALUES(`sort_order`),
  `version` = VALUES(`version`),
  `is_active` = VALUES(`is_active`);

INSERT INTO `progression_levels` (`level`,`xp_required_total`,`title`,`badge_color`,`is_active`)
VALUES
  (1,0,'Iniciante','#95a5a6',1),
  (2,100,'Iniciante II','#95a5a6',1),
  (3,200,'Aprendiz I','#74b9ff',1),
  (4,300,'Aprendiz II','#74b9ff',1),
  (5,400,'Aprendiz III','#74b9ff',1),
  (6,500,'Constante I','#55efc4',1),
  (7,600,'Constante II','#55efc4',1),
  (8,700,'Constante III','#55efc4',1),
  (9,800,'Constante IV','#55efc4',1),
  (10,900,'Constante V','#55efc4',1),
  (11,1050,'Focado I','#0984e3',1),
  (12,1200,'Focado II','#0984e3',1),
  (13,1350,'Focado III','#0984e3',1),
  (14,1500,'Focado IV','#0984e3',1),
  (15,1650,'Focado V','#0984e3',1),
  (16,1800,'Disciplina I','#6c5ce7',1),
  (17,1950,'Disciplina II','#6c5ce7',1),
  (18,2100,'Disciplina III','#6c5ce7',1),
  (19,2250,'Disciplina IV','#6c5ce7',1),
  (20,2400,'Disciplina V','#6c5ce7',1),
  (21,2600,'Mestre I','#fdcb6e',1),
  (22,2800,'Mestre II','#fdcb6e',1),
  (23,3000,'Mestre III','#fdcb6e',1),
  (24,3200,'Mestre IV','#fdcb6e',1),
  (25,3400,'Mestre V','#fdcb6e',1),
  (26,3600,'Elite I','#e17055',1),
  (27,3800,'Elite II','#e17055',1),
  (28,4000,'Elite III','#e17055',1),
  (29,4200,'Elite IV','#e17055',1),
  (30,4400,'Elite V','#e17055',1),
  (31,4600,'Veterano I','#d63031',1),
  (32,4800,'Veterano II','#d63031',1),
  (33,5000,'Veterano III','#d63031',1),
  (34,5200,'Veterano IV','#d63031',1),
  (35,5400,'Veterano V','#d63031',1),
  (36,5600,'Lendário I','#fd79a8',1),
  (37,5850,'Lendário II','#fd79a8',1),
  (38,6100,'Lendário III','#fd79a8',1),
  (39,6350,'Lendário IV','#fd79a8',1),
  (40,6600,'Lendário V','#fd79a8',1),
  (41,6850,'Mítico I','#a29bfe',1),
  (42,7100,'Mítico II','#a29bfe',1),
  (43,7350,'Mítico III','#a29bfe',1),
  (44,7600,'Mítico IV','#a29bfe',1),
  (45,7850,'Mítico V','#a29bfe',1),
  (46,8100,'Ascendente I','#00cec9',1),
  (47,8350,'Ascendente II','#00cec9',1),
  (48,8600,'Ascendente III','#00cec9',1),
  (49,8850,'Ascendente IV','#00cec9',1),
  (50,9100,'Ascendente V','#00cec9',1)
ON DUPLICATE KEY UPDATE
  `xp_required_total` = VALUES(`xp_required_total`),
  `title` = VALUES(`title`),
  `badge_color` = VALUES(`badge_color`),
  `is_active` = VALUES(`is_active`);

INSERT INTO `reward_definitions` (`id`,`slug`,`reward_type`,`name`,`description`,`icon`,`visual_config_json`,`unlock_source_type`,`unlock_source_config_json`,`sort_order`,`is_active`)
VALUES
  (1,'level-badge-2','profile_badge','Faixa Nível 2','Badge de evolução ao alcançar o nível 2','star','{\"color\":\"#95a5a6\",\"label\":\"N2\"}','level_milestone','{\"level\":2}',10,1),
  (2,'level-badge-5','profile_badge','Faixa Nível 5','Badge de evolução ao alcançar o nível 5','patch-check','{\"color\":\"#74b9ff\",\"label\":\"N5\"}','level_milestone','{\"level\":5}',20,1),
  (3,'level-badge-10','profile_badge','Faixa Nível 10','Badge de evolução ao alcançar o nível 10','award','{\"color\":\"#55efc4\",\"label\":\"N10\"}','level_milestone','{\"level\":10}',30,1),
  (4,'level-badge-15','profile_badge','Faixa Nível 15','Badge de evolução ao alcançar o nível 15','trophy','{\"color\":\"#0984e3\",\"label\":\"N15\"}','level_milestone','{\"level\":15}',40,1),
  (5,'level-badge-20','profile_badge','Faixa Nível 20','Badge de evolução ao alcançar o nível 20','trophy','{\"color\":\"#6c5ce7\",\"label\":\"N20\"}','level_milestone','{\"level\":20}',50,1),
  (6,'level-badge-30','profile_badge','Faixa Nível 30','Badge de evolução ao alcançar o nível 30','gem','{\"color\":\"#fdcb6e\",\"label\":\"N30\"}','level_milestone','{\"level\":30}',60,1),
  (7,'level-badge-50','profile_badge','Faixa Nível 50','Badge de evolução ao alcançar o nível 50','gem','{\"color\":\"#00cec9\",\"label\":\"N50\"}','level_milestone','{\"level\":50}',70,1)
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `description` = VALUES(`description`),
  `icon` = VALUES(`icon`),
  `visual_config_json` = VALUES(`visual_config_json`),
  `unlock_source_type` = VALUES(`unlock_source_type`),
  `unlock_source_config_json` = VALUES(`unlock_source_config_json`),
  `sort_order` = VALUES(`sort_order`),
  `is_active` = VALUES(`is_active`);
