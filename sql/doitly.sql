-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 11/12/2025 às 18:17
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
-- Banco de dados: `doitly`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL COMMENT 'Nome da categoria',
  `color` varchar(7) NOT NULL DEFAULT '#4a74ff' COMMENT 'Cor em hexadecimal',
  `icon` varchar(50) DEFAULT NULL COMMENT 'Classe do ícone Bootstrap Icons',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `categories`
--

INSERT INTO `categories` (`id`, `name`, `color`, `icon`, `created_at`) VALUES
(1, 'Saúde Física', '#59d186', 'bi-heart-pulse', '2025-12-10 19:20:00'),
(2, 'Saúde Mental', '#4a74ff', 'bi-brain', '2025-12-10 19:20:00'),
(3, 'Trabalho', '#ff5757', 'bi-briefcase', '2025-12-10 19:20:00'),
(4, 'Estudo', '#eed27a', 'bi-book', '2025-12-10 19:20:00'),
(5, 'Desenvolvimento Pessoal', '#a78bfa', 'bi-rocket-takeoff', '2025-12-10 19:20:00'),
(6, 'Finanças', '#34d399', 'bi-piggy-bank', '2025-12-10 19:20:00');

-- --------------------------------------------------------

--
-- Estrutura para tabela `category_streaks`
--

CREATE TABLE `category_streaks` (
  `id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL COMMENT 'Categoria relacionada',
  `streak_date` date NOT NULL COMMENT 'Data do registro',
  `all_habits_completed` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Todos os hábitos da categoria foram concluídos?',
  `streak_count` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Contagem do streak naquele dia',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `habits`
--

CREATE TABLE `habits` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL COMMENT 'Usuário proprietário do hábito',
  `category_id` int(10) UNSIGNED NOT NULL COMMENT 'Categoria do hábito',
  `title` varchar(150) NOT NULL COMMENT 'Nome do hábito',
  `description` text DEFAULT NULL COMMENT 'Descrição detalhada (opcional)',
  `frequency` enum('diario','semanal','mensal') NOT NULL DEFAULT 'diario' COMMENT 'Frequência do hábito',
  `time_of_day` enum('manha','tarde','noite','qualquer') NOT NULL DEFAULT 'qualquer' COMMENT 'Horário preferencial',
  `start_date` date NOT NULL COMMENT 'Data de início do hábito',
  `active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Hábito ativo ou arquivado',
  `streak` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Sequência atual de dias consecutivos',
  `best_streak` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Melhor sequência já alcançada',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `habits`
--

INSERT INTO `habits` (`id`, `user_id`, `category_id`, `title`, `description`, `frequency`, `time_of_day`, `start_date`, `active`, `streak`, `best_streak`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 'Meditar pela manhã', 'Praticar 10 minutos de meditação guiada logo após acordar para começar o dia com mais foco e tranquilidade.', 'diario', 'manha', '2025-01-01', 1, 12, 15, '2025-12-10 19:20:00', '2025-12-10 19:20:00'),
(2, 1, 1, 'Fazer exercícios', 'Treino de 30 minutos (corrida, academia ou yoga).', 'diario', 'manha', '2025-01-01', 1, 8, 12, '2025-12-10 19:20:00', '2025-12-10 19:20:00'),
(3, 1, 4, 'Estudar programação', 'Dedicar 30 minutos ao estudo de desenvolvimento web.', 'diario', 'noite', '2025-01-05', 1, 5, 5, '2025-12-10 19:20:00', '2025-12-10 19:20:00'),
(4, 1, 5, 'Ler 15 minutos', 'Leitura de livros de desenvolvimento pessoal ou ficção.', 'diario', 'qualquer', '2025-01-01', 1, 10, 14, '2025-12-10 19:20:00', '2025-12-10 19:20:00'),
(5, 1, 1, 'Beber 2 litros de água', 'Manter hidratação adequada ao longo do dia.', 'diario', 'qualquer', '2025-01-01', 1, 7, 9, '2025-12-10 19:20:00', '2025-12-10 19:20:00');

-- --------------------------------------------------------

--
-- Estrutura para tabela `habit_completions`
--

CREATE TABLE `habit_completions` (
  `id` int(10) UNSIGNED NOT NULL,
  `habit_id` int(10) UNSIGNED NOT NULL COMMENT 'Hábito relacionado',
  `completion_date` date NOT NULL COMMENT 'Data da conclusão',
  `completed_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Horário exato da conclusão',
  `note` varchar(255) DEFAULT NULL COMMENT 'Nota opcional do usuário'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Acionadores `habit_completions`
--
DELIMITER $$
CREATE TRIGGER `trg_recalculate_streak_after_delete` AFTER DELETE ON `habit_completions` FOR EACH ROW BEGIN
  DECLARE v_category_id INT UNSIGNED;
  DECLARE v_total_active_habits INT;
  DECLARE v_completed_habits INT;
  
  -- Buscar categoria
  SELECT category_id INTO v_category_id
  FROM habits
  WHERE id = OLD.habit_id;
  
  -- Contar hábitos ativos
  SELECT COUNT(*) INTO v_total_active_habits
  FROM habits
  WHERE category_id = v_category_id AND active = TRUE;
  
  -- Contar conclusões restantes do dia
  SELECT COUNT(DISTINCT h.id) INTO v_completed_habits
  FROM habits h
  INNER JOIN habit_completions hc ON hc.habit_id = h.id
  WHERE h.category_id = v_category_id
    AND h.active = TRUE
    AND hc.completion_date = OLD.completion_date;
  
  -- Atualizar registro de streak do dia
  IF v_completed_habits < v_total_active_habits THEN
    UPDATE category_streaks
    SET all_habits_completed = FALSE,
        streak_count = 0
    WHERE category_id = v_category_id
      AND streak_date = OLD.completion_date;
    
    -- Resetar current_streak da categoria
    UPDATE categories
    SET current_streak = 0
    WHERE id = v_category_id;
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_update_category_streak_after_completion` AFTER INSERT ON `habit_completions` FOR EACH ROW BEGIN
  DECLARE v_category_id INT UNSIGNED;
  DECLARE v_total_active_habits INT;
  DECLARE v_completed_habits INT;
  DECLARE v_all_completed BOOLEAN DEFAULT FALSE;
  DECLARE v_current_streak INT;
  DECLARE v_best_streak INT;
  
  -- Buscar categoria do hábito
  SELECT category_id INTO v_category_id
  FROM habits
  WHERE id = NEW.habit_id;
  
  -- Contar total de hábitos ativos da categoria
  SELECT COUNT(*) INTO v_total_active_habits
  FROM habits
  WHERE category_id = v_category_id
    AND active = TRUE;
  
  -- Contar hábitos concluídos hoje dessa categoria
  SELECT COUNT(DISTINCT h.id) INTO v_completed_habits
  FROM habits h
  INNER JOIN habit_completions hc ON hc.habit_id = h.id
  WHERE h.category_id = v_category_id
    AND h.active = TRUE
    AND hc.completion_date = NEW.completion_date;
  
  -- Verificar se todos foram concluídos
  IF v_completed_habits >= v_total_active_habits THEN
    SET v_all_completed = TRUE;
    
    -- Calcular novo streak
    SELECT COALESCE(MAX(streak_count), 0) + 1 INTO v_current_streak
    FROM category_streaks
    WHERE category_id = v_category_id
      AND streak_date = DATE_SUB(NEW.completion_date, INTERVAL 1 DAY)
      AND all_habits_completed = TRUE;
    
    -- Se não há registro do dia anterior completo, resetar streak
    IF v_current_streak = 1 THEN
      SET v_current_streak = 1;
    END IF;
    
  ELSE
    SET v_current_streak = 0;
  END IF;
  
  -- Inserir ou atualizar registro de streak do dia
  INSERT INTO category_streaks (category_id, streak_date, all_habits_completed, streak_count)
  VALUES (v_category_id, NEW.completion_date, v_all_completed, v_current_streak)
  ON DUPLICATE KEY UPDATE
    all_habits_completed = v_all_completed,
    streak_count = v_current_streak;
  
  -- Atualizar current_streak e best_streak na tabela categories
  IF v_all_completed THEN
    SELECT best_streak INTO v_best_streak
    FROM categories
    WHERE id = v_category_id;
    
    UPDATE categories
    SET 
      current_streak = v_current_streak,
      best_streak = GREATEST(v_best_streak, v_current_streak)
    WHERE id = v_category_id;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `habit_logs`
--

CREATE TABLE `habit_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `habit_id` int(10) UNSIGNED NOT NULL COMMENT 'Hábito relacionado',
  `log_date` date NOT NULL COMMENT 'Data do registro',
  `completed` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Se foi concluído ou não',
  `note` varchar(255) DEFAULT NULL COMMENT 'Nota opcional do usuário',
  `completed_at` timestamp NULL DEFAULT NULL COMMENT 'Horário exato da conclusão',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `habit_logs`
--

INSERT INTO `habit_logs` (`id`, `habit_id`, `log_date`, `completed`, `note`, `completed_at`, `created_at`) VALUES
(1, 1, '2025-12-04', 1, NULL, '2025-12-04 10:00:00', '2025-12-10 19:20:00'),
(2, 1, '2025-12-05', 1, NULL, '2025-12-05 10:15:00', '2025-12-10 19:20:00'),
(3, 1, '2025-12-06', 1, NULL, '2025-12-06 10:05:00', '2025-12-10 19:20:00'),
(4, 1, '2025-12-07', 1, NULL, '2025-12-07 09:50:00', '2025-12-10 19:20:00'),
(5, 1, '2025-12-08', 1, NULL, '2025-12-08 10:10:00', '2025-12-10 19:20:00'),
(6, 1, '2025-12-09', 1, NULL, '2025-12-09 10:20:00', '2025-12-10 19:20:00'),
(7, 1, '2025-12-10', 1, NULL, '2025-12-10 19:20:00', '2025-12-10 19:20:00'),
(8, 2, '2025-12-04', 1, 'Corrida de 5km', '2025-12-04 11:00:00', '2025-12-10 19:20:00'),
(9, 2, '2025-12-05', 0, 'Faltei por cansaço', NULL, '2025-12-10 19:20:00'),
(10, 2, '2025-12-06', 1, 'Treino de força', '2025-12-06 11:30:00', '2025-12-10 19:20:00'),
(11, 2, '2025-12-07', 1, 'Yoga 30min', '2025-12-07 12:00:00', '2025-12-10 19:20:00'),
(12, 2, '2025-12-08', 1, 'Corrida', '2025-12-08 11:15:00', '2025-12-10 19:20:00'),
(13, 2, '2025-12-09', 0, NULL, NULL, '2025-12-10 19:20:00'),
(14, 2, '2025-12-10', 1, 'Academia - treino completo', '2025-12-10 19:20:00', '2025-12-10 19:20:00'),
(15, 3, '2025-12-06', 1, NULL, '2025-12-06 23:00:00', '2025-12-10 19:20:00'),
(16, 3, '2025-12-07', 1, NULL, '2025-12-08 00:00:00', '2025-12-10 19:20:00'),
(17, 3, '2025-12-08', 1, NULL, '2025-12-08 23:30:00', '2025-12-10 19:20:00'),
(18, 3, '2025-12-09', 1, NULL, '2025-12-09 23:15:00', '2025-12-10 19:20:00'),
(19, 3, '2025-12-10', 1, NULL, '2025-12-10 19:20:00', '2025-12-10 19:20:00');

-- --------------------------------------------------------

--
-- Estrutura para tabela `sessions`
--

CREATE TABLE `sessions` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL COMMENT 'Usuário da sessão',
  `token_hash` varchar(255) NOT NULL COMMENT 'Token criptografado (hash)',
  `refresh_token_hash` varchar(255) DEFAULT NULL COMMENT 'Refresh token para renovação',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'Endereço IP do usuário',
  `user_agent` varchar(255) DEFAULT NULL COMMENT 'Navegador/dispositivo',
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Data de expiração da sessão',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL COMMENT 'Nome completo do usuário',
  `email` varchar(150) NOT NULL COMMENT 'Email único para login',
  `password_hash` varchar(255) NOT NULL COMMENT 'Senha criptografada (bcrypt)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `created_at`, `updated_at`) VALUES
(1, 'Usuário Demo', 'demo@doitly.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-12-10 19:20:00', '2025-12-10 19:20:00');

-- --------------------------------------------------------

--
-- Estrutura para tabela `user_category_streak`
--

CREATE TABLE `user_category_streak` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `current_streak` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `best_streak` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `last_streak_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_category_name` (`name`);

--
-- Índices de tabela `category_streaks`
--
ALTER TABLE `category_streaks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_category_date` (`category_id`,`streak_date`),
  ADD KEY `idx_category_streaks` (`category_id`,`streak_date`),
  ADD KEY `idx_streak_date` (`streak_date`);

--
-- Índices de tabela `habits`
--
ALTER TABLE `habits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_habits` (`user_id`,`active`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_start_date` (`start_date`);

--
-- Índices de tabela `habit_completions`
--
ALTER TABLE `habit_completions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_habit_date` (`habit_id`,`completion_date`) COMMENT 'Uma conclusão por hábito por dia',
  ADD KEY `idx_habit_completions` (`habit_id`,`completion_date`),
  ADD KEY `idx_completion_date` (`completion_date`);

--
-- Índices de tabela `habit_logs`
--
ALTER TABLE `habit_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_habit_date` (`habit_id`,`log_date`) COMMENT 'Um log por hábito por dia',
  ADD KEY `idx_habit_logs` (`habit_id`,`log_date`),
  ADD KEY `idx_log_date` (`log_date`),
  ADD KEY `idx_completed` (`completed`);

--
-- Índices de tabela `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token_hash` (`token_hash`),
  ADD KEY `idx_user_sessions` (`user_id`),
  ADD KEY `idx_token` (`token_hash`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Índices de tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`);

--
-- Índices de tabela `user_category_streak`
--
ALTER TABLE `user_category_streak`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_category` (`user_id`,`category_id`),
  ADD KEY `idx_user_category_user` (`user_id`),
  ADD KEY `fk_user_category_streak_category` (`category_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `category_streaks`
--
ALTER TABLE `category_streaks`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `habits`
--
ALTER TABLE `habits`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `habit_completions`
--
ALTER TABLE `habit_completions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `habit_logs`
--
ALTER TABLE `habit_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de tabela `sessions`
--
ALTER TABLE `sessions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `user_category_streak`
--
ALTER TABLE `user_category_streak`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `category_streaks`
--
ALTER TABLE `category_streaks`
  ADD CONSTRAINT `fk_streaks_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `habits`
--
ALTER TABLE `habits`
  ADD CONSTRAINT `fk_habits_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_habits_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `habit_completions`
--
ALTER TABLE `habit_completions`
  ADD CONSTRAINT `fk_completions_habit` FOREIGN KEY (`habit_id`) REFERENCES `habits` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `habit_logs`
--
ALTER TABLE `habit_logs`
  ADD CONSTRAINT `fk_logs_habit` FOREIGN KEY (`habit_id`) REFERENCES `habits` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `fk_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `user_category_streak`
--
ALTER TABLE `user_category_streak`
  ADD CONSTRAINT `fk_user_category_streak_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `fk_user_category_streak_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
