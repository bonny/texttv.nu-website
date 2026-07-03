-- Webb-ägda hjälptabeller i texttv_nu som CodeIgniter läser/skriver men som
-- INTE skapas av importerns Laravel-migrationer (de finns bara i prod, skapade
-- manuellt). Utan dem ger sidrendering "table doesn't exist" (db_debug=TRUE).
-- Tomma lokalt — webben tål det (queries returnerar inga rader).
USE `texttv_nu`;

-- SEO-landningstext per sida (kurerad manuellt i prod — jfr todo #04 D).
-- Läses av views/page_text.php och controllers/fakta.php.
CREATE TABLE IF NOT EXISTS `texttv_page_text` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `pagedescription` VARCHAR(255) NOT NULL DEFAULT '',
  `title`           VARCHAR(255) NOT NULL DEFAULT '',
  `text`            MEDIUMTEXT   NULL,
  PRIMARY KEY (`id`),
  KEY `pagedescription_index` (`pagedescription`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Utvecklingsbloggen (controllers/blogg.php, rssfeed.php).
CREATE TABLE IF NOT EXISTS `texttv_blogg` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `date_published` DATETIME     NOT NULL,
  `permalink`      VARCHAR(255) NOT NULL DEFAULT '',
  `title`          VARCHAR(255) NOT NULL DEFAULT '',
  `content`        MEDIUMTEXT   NULL,
  PRIMARY KEY (`id`),
  KEY `permalink_index` (`permalink`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Loggtabell (helpers/texttv_helper.php skriver hit).
CREATE TABLE IF NOT EXISTS `texttv_log` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `date_added` DATETIME     NOT NULL,
  `log_key`    VARCHAR(255) NOT NULL DEFAULT '',
  `log_text`   MEDIUMTEXT   NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
