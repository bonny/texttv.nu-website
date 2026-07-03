-- Körs en gång av mariadb-imagens entrypoint (efter att MARIADB_DATABASE +
-- MARIADB_USER skapats). Skapar stats-DB:n som webben förväntar sig och ger
-- dev-användaren rättigheter på båda databaserna.
--
-- texttv_nu skapas av MARIADB_DATABASE i compose.yaml. texttv_stats måste
-- skapas här. (Prod heter DB:n `texttv.nu` med punkt; lokalt underscore — se
-- CLAUDE.md. CI:s database.php-switch hanterar skillnaden.)
CREATE DATABASE IF NOT EXISTS `texttv_stats` CHARACTER SET utf8 COLLATE utf8_general_ci;

GRANT ALL PRIVILEGES ON `texttv_nu`.*    TO 'texttv'@'%';
GRANT ALL PRIVILEGES ON `texttv_stats`.* TO 'texttv'@'%';
FLUSH PRIVILEGES;

-- page_actions: analytics-tabellen webben skriver till (controllers/api.php:693).
-- I Docker når CI:s stats-anslutning inte hit (hårdkodad hostname 'localhost' i
-- database.php), men vi skapar tabellen så schemat finns om någon wire:ar stats
-- lokalt senare.
USE `texttv_stats`;
CREATE TABLE IF NOT EXISTS `page_actions` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `page_ids`   VARCHAR(100) NOT NULL,
  `type`       VARCHAR(20)  NOT NULL,
  `created_at` DATETIME     NOT NULL,
  PRIMARY KEY (`id`),
  KEY `created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
