-- THORIUS Interactions Table
-- Stores all chat interactions for continuous learning and improvement

CREATE TABLE IF NOT EXISTS `vortex_thorius_interactions` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `user_message` text NOT NULL,
  `thorius_response` longtext NOT NULL,
  `response_type` varchar(50) NOT NULL DEFAULT 'info',
  `escalation_needed` tinyint(1) NOT NULL DEFAULT 0,
  `interaction_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_satisfaction` int(1) NULL DEFAULT NULL,
  `session_id` varchar(64) NULL,
  `context_data` longtext NULL,
  `response_time_ms` int(11) NULL,
  `ip_address` varchar(45) NULL,
  `user_agent` text NULL,
  `platform_page` varchar(255) NULL,
  `conversation_thread` varchar(64) NULL,
  `feedback_provided` tinyint(1) NOT NULL DEFAULT 0,
  `helpful_rating` int(1) NULL,
  `issue_resolved` tinyint(1) NULL,
  `follow_up_needed` tinyint(1) NOT NULL DEFAULT 0,
  `category_tags` varchar(255) NULL,
  `sentiment_score` decimal(3,2) NULL,
  `language_detected` varchar(10) NULL DEFAULT 'en',
  `security_flags` varchar(255) NULL,
  `learning_priority` int(1) NOT NULL DEFAULT 3,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_interaction_time` (`interaction_time`),
  KEY `idx_response_type` (`response_type`),
  KEY `idx_escalation` (`escalation_needed`),
  KEY `idx_satisfaction` (`user_satisfaction`),
  KEY `idx_session` (`session_id`),
  KEY `idx_conversation` (`conversation_thread`),
  KEY `idx_priority` (`learning_priority`),
  KEY `idx_archived` (`archived`),
  KEY `idx_user_time` (`user_id`, `interaction_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- THORIUS Learning Analytics Table
-- Aggregated learning metrics for AI optimization

CREATE TABLE IF NOT EXISTS `vortex_thorius_learning_metrics` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `metric_date` date NOT NULL,
  `total_interactions` int(11) NOT NULL DEFAULT 0,
  `avg_response_time` decimal(8,2) NOT NULL DEFAULT 0,
  `satisfaction_avg` decimal(3,2) NULL,
  `escalation_rate` decimal(5,2) NOT NULL DEFAULT 0,
  `resolution_rate` decimal(5,2) NOT NULL DEFAULT 0,
  `feedback_count` int(11) NOT NULL DEFAULT 0,
  `top_categories` text NULL,
  `improvement_areas` text NULL,
  `performance_score` decimal(3,2) NOT NULL DEFAULT 0,
  `learning_iterations` int(11) NOT NULL DEFAULT 0,
  `optimization_applied` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_date` (`metric_date`),
  KEY `idx_performance` (`performance_score`),
  KEY `idx_satisfaction` (`satisfaction_avg`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- THORIUS Supervision Log Table
-- Platform supervision and security events

CREATE TABLE IF NOT EXISTS `vortex_thorius_supervision` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `action_type` varchar(100) NOT NULL,
  `action_data` longtext NULL,
  `supervision_result` enum('approved','warning','blocked','flagged') NOT NULL DEFAULT 'approved',
  `warning_messages` text NULL,
  `recommendations` text NULL,
  `risk_score` int(3) NOT NULL DEFAULT 0,
  `automated_action` varchar(100) NULL,
  `manual_review_needed` tinyint(1) NOT NULL DEFAULT 0,
  `reviewer_id` bigint(20) UNSIGNED NULL,
  `review_status` enum('pending','approved','rejected') NULL,
  `review_notes` text NULL,
  `supervision_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `resolved_time` datetime NULL,
  `ip_address` varchar(45) NULL,
  `session_data` text NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action_type` (`action_type`),
  KEY `idx_result` (`supervision_result`),
  KEY `idx_risk_score` (`risk_score`),
  KEY `idx_manual_review` (`manual_review_needed`),
  KEY `idx_supervision_time` (`supervision_time`),
  KEY `idx_user_time` (`user_id`, `supervision_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 