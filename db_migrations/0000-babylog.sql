--
-- 0000-babylog.sql
--
-- DESCRIPTION --
-- * THIS IS THE ROOT OF ALL MIGRATIONS --
-- ADDITIONAL --
-- * Here you describe any additional steps like loading data and what not --

-- Cleanup

-- Create
DROP TABLE IF EXISTS migrations;
CREATE TABLE `migrations` (
     `version` int(11) UNSIGNED UNIQUE PRIMARY KEY
);
INSERT INTO migrations (version) VALUES(0000);

DROP TABLE IF EXISTS events;
CREATE TABLE `events` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `event_id` char(34) DEFAULT NULL,
    `account_id` int(11) NOT NULL,
    `user_id` int(11) NOT NULL,
    `type` smallint(3) NOT NULL,
    `event_date` datetime DEFAULT NULL,
    `content` varchar(512),
    `created_at` datetime DEFAULT NULL,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `event` (`account_id`, `event_id`),
    KEY `user` (`user_id`),
    KEY `type` (`type`)
);

DROP TABLE IF EXISTS accounts;
CREATE TABLE `accounts` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `owner_id` int(11) NOT NULL,
    `name` varchar(255) DEFAULT NULL,
    `created_at` datetime DEFAULT NULL,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `owner` (`owner_id`)
);

DROP TABLE IF EXISTS user_accounts;
CREATE TABLE `user_accounts` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `account_id` int(11) NOT NULL,
    `user_id` int(11) NOT NULL,
    `created_at` datetime DEFAULT NULL,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `account` (`user_id`, `account_id`)
);


DROP TABLE IF EXISTS users;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `default_account_id` int(11) NOT NULL,
  `firstname` varchar(255) DEFAULT NULL,
  `lastname` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `salt`  varchar(255) DEFAULT NULL,
  `phone_number` char(20) DEFAULT NULL,
  `gender` char(10) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `profile_image_large` varchar(255) DEFAULT NULL,
  `login_count` int(11) NOT NULL DEFAULT '0',
  `last_login_at` datetime DEFAULT NULL,
  `last_login_ip` varchar(255) DEFAULT NULL,
  `last_login_agent` varchar(255) DEFAULT NULL,
  `disabled` tinyint(3) NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user` (`email`,`username`)
);

DROP TABLE IF EXISTS user_social;
CREATE TABLE `user_social` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` tinyint(3) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `remote_user_id` bigint(10) DEFAULT NULL,
  `auth_token` varchar(255) DEFAULT NULL,
  `expires` int(11) DEFAULT NULL,
  `auth_token_secret` varchar(255) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user` (`user_id`),
  KEY `remote_user` (`type`,`remote_user_id`)
);

DROP TABLE IF EXISTS kids;
CREATE TABLE `kids` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firstname` varchar(255) DEFAULT NULL,
  `lastname` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `birth_date` datetime DEFAULT NULL,
  `account_id` int(11) NOT NULL,
  `sex` char(1),
  `created_at` datetime DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `account` (`account_id`)
);

DROP TABLE IF EXISTS groups;
CREATE TABLE `groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64),
  `created_at` datetime DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `group_name` (`name`)
);

DROP TABLE IF EXISTS user_groups;
CREATE TABLE `user_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_group` (`user_id`, `group_id`)
);

DROP TABLE IF EXISTS permissions;
CREATE TABLE `permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);

DROP TABLE IF EXISTS group_permissions;
CREATE TABLE `group_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `group_permission` (`group_id`, `permission_id`)
);



