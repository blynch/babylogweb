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
    `user_id` int(11) NOT NULL,
    `type` smallint(3) NOT NULL,
    `event_date` datetime DEFAULT NULL,
    `content` varchar(512),
    `created_at` datetime DEFAULT NULL,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `event` (`user_id`, `event_id`),
    KEY `user` (`user_id`),
    KEY `type` (`type`)
);
