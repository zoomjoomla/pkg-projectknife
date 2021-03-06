CREATE TABLE IF NOT EXISTS `#__pk_tasks` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `milestone_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `title` varchar(128) NOT NULL DEFAULT '',
  `alias` varchar(128) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `published` tinyint(3) NOT NULL DEFAULT '0',
  `access` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `access_inherit` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `priority` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `progress` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `assignee_count` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `ordering` int(11) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_by` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_by` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `completed` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `completed_by` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `start_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `start_date_inherit` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `due_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `due_date_inherit` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `checked_out` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_alias` (`project_id`,`alias`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_published` (`published`),
  KEY `idx_access` (`access`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_milestone_id` (`milestone_id`),
  KEY `idx_completed_by` (`completed_by`) USING BTREE,
  KEY `idx_checked_out` (`checked_out`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__pk_task_assignees` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `task_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `user_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_task_user` (`task_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__pk_task_dependencies` (
  `predecessor_id` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `successor_id` int(11) UNSIGNED NOT NULL DEFAULT '0',
  UNIQUE KEY `idx_successor` (`successor_id`,`predecessor_id`) USING BTREE,
  UNIQUE KEY `idx_predecessor` (`predecessor_id`,`successor_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;