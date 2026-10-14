-- Förbereder den lokala Docker-DB:n för API-testerna (tests/api/run.sh).
-- Idempotent: körs som root i mariadb-containern före varje testkörning.
--
-- /api/most_read hårdkodar prod-namnen `texttv.nu`.texttv och
-- texttv_stats.page_actions på default-anslutningen. Lokalt heter DB:n
-- texttv_nu, så en vy med prod-namnet pekar dit. Vyn kan inte ligga i
-- deploy/mariadb/init/, eftersom tabellen skapas först av importerns
-- migrationer.
CREATE DATABASE IF NOT EXISTS `texttv.nu`;
CREATE OR REPLACE VIEW `texttv.nu`.texttv AS SELECT * FROM texttv_nu.texttv;
GRANT SELECT ON `texttv.nu`.* TO 'texttv'@'%';
GRANT SELECT ON texttv_stats.* TO 'texttv'@'%';
FLUSH PRIVILEGES;

-- Deterministiska visningar för idag och igår, så att most_read har något
-- att räkna på. Antalet per sida är en funktion av id, så topplistan blir
-- densamma vid varje körning samma dag. page_actions skrivs aldrig av webben
-- lokalt (stats-anslutningen når inte mariadb, se CLAUDE.md), så det är
-- ofarligt att tömma den.
-- seq_*-tabellerna (Sequence-motorn) kräver en vald databas.
USE texttv_stats;
TRUNCATE TABLE page_actions;

INSERT INTO page_actions (page_ids, type, created_at)
SELECT tt.id, 'VIEW',
       CURDATE() - INTERVAL d.seq DAY + INTERVAL ((tt.id * 97 + s.seq * 613) % 86000) SECOND
FROM texttv_nu.texttv AS tt
JOIN seq_0_to_1 AS d
JOIN seq_1_to_40 AS s ON s.seq <= 1 + (tt.id * (7 + d.seq * 4)) % 37
WHERE tt.page_num BETWEEN 100 AND 399;
