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
