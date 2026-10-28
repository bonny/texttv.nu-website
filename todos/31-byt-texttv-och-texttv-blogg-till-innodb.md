**Status:** aktiv
**Senast uppdaterad:** 2026-10-28

# Todo #31 — Byt `texttv` och `texttv_blogg` till InnoDB

## Sammanfattning

`texttv` och `texttv_blogg` är de enda MyISAM-tabellerna på någon av servrarna
(BPK och eskapism.se är helt InnoDB). Byt till InnoDB för kraschsäkerhet och för
att kunna ta konsekventa backuper utan lås. Sätt sedan upp en nattlig dump till
Mac minin, som för BPK.

## Bakgrund

- **Kraschsäkerhet.** Hetzners nattliga image (7 sparade, fönster 18–22) tas
  medan MariaDB kör. InnoDB spelar upp sin redo-logg vid start och blir hel;
  en MyISAM-tabell kan vara markerad som trasig och behöva repareras, och
  rader kan gå förlorade. Samma sak vid strömavbrott eller OOM.
- **Backup.** `mariadb-dump --single-transaction` ger en konsekvent dump utan
  lås, men bara för InnoDB. Idag finns ingen regelbunden logisk dump av texttv
  alls; den enda fullständiga är `/root/db-backups-2026-10-18` (1,5 GB gz),
  kopierad till `~/Backups/texttv-db/` på Mac minin 2026-10-28.
- **Transaktioner** gör att ROLLBACK fungerar (jfr #30, där rättigheterna fick
  testas med satser som inte träffar några rader).
- Radlås spelar liten roll idag: 149 väntande tabellås av 4,5 miljoner på 30 h.

Mätt 2026-10-28:

| Tabell | Rader | Data | Index |
| --- | --- | --- | --- |
| `texttv` | 1,6 milj. | 1 342 MB | 143 MB |
| `texttv_blogg` | 81 | 0 | 0 |

- Schemat är enkelt: inga FULLTEXT-, MERGE- eller andra MyISAM-specifika
  funktioner, och koden har inga `COUNT(*)` (som blir långsamt i InnoDB).
- **`page_content` är komprimerad med `COMPRESS()`** (importern skriver
  `COMPRESS(?)`, webben läser `UNCOMPRESS()`, importern jämför med
  `gzuncompress(substr(…, 4))`). Infört när disken tog slut. Mätt på de senaste
  42 663 raderna: 37 MB lagrat mot 323 MB okomprimerat, ~8,6×. Påverkar inte
  bytet: `COMPRESS()` är en SQL-funktion oberoende av motor, och ALTER kopierar
  bytena oförändrade. Snitt ~880 byte per rad, så innehållet ryms i raden
  (ROW_FORMAT=DYNAMIC) i stället för på egna sidor. Använd **inte** InnoDB:s
  `ROW_FORMAT=COMPRESSED` eller sidkomprimering: datan är redan komprimerad,
  så vinsten blir liten och CPU-kostnaden kvar. Det förklarar också varför
  dumpen bara krymper ~45 % med gzip.
- `innodb_buffer_pool_size` är 128 MB (Debians default). BPK har 1 GB.
- Disken har 54 GB ledigt. InnoDB-filen blir ~1,5–2× större och krymper inte av
  städjobbet, men städjobbet raderar sammanhängande gamla id:n, så hela sidor
  frigörs och återanvänds; storleken bör hålla sig stabil.

## Förslag

1. **Buffer pool.** `SET GLOBAL innodb_buffer_pool_size = 2G` (dynamisk), och
   samma värde i en egen fil under `/etc/mysql/mariadb.conf.d/`. Följ minnet
   (8 GB totalt, ~6 GB OS-cache idag).
2. **Mät före.** Svarstider med cache-buster för `/api/get/100`,
   `/api/most_read/news`, `/api/last_updated/news`, en arkivsida och `/sida/delat`.
   Notera slow-loggen.
3. **Generalrepetition: `texttv_blogg`.** `ALTER TABLE texttv_blogg ENGINE=InnoDB`.
   Sekunder. Kontrollera bloggen och `/feed/blogg`.
4. **`texttv`, en natt runt 03–05.**
   - Färsk dump först (`mariadb-dump` av `texttv.nu`), till Mac minin.
   - Pausa importen (kommentera ut `schedule:run` i root-crontab).
   - `SET SESSION lock_wait_timeout = 10;` så att ALTER ger upp i stället för
     att blockera alla frågor bakom sig när den väntar på metadata-låset.
   - `ALTER TABLE texttv ENGINE=InnoDB;` (1–3 min, läsningar går, skrivningar
     väntar).
   - Slå på importen, följ `IMPORT_SUCCESS`, `laravel.log`, php-fpm-poolen.
5. **Mät efter** samma anrop som i steg 2, och slow-loggen ett dygn.
6. **Nattlig dump till Mac minin**, mönster `~/Backups/bin/backup-bpk-db.sh`:
   `gzip --fast` på servern, hem via SSH, packas om till `xz -9` på Mac minin
   (som BPK sedan 2026-10-28), egen LaunchAgent, larm vid fel eller misstänkt
   liten dump, 14 dagars historik. Mätt 2026-10-28 på dumpen från 10-18:
   `texttv.nu` gz 1 501 MB → xz 1 071 MB (zstd -19 1 062 MB, innehållet är redan
   `COMPRESS()`-at), `texttv_stats` 28,9 → 11,7 MB.
7. **Städa** `/root/db-backups-2026-10-18` och `-10-22` på servern när steg 6
   har gått några nätter.

## Risker

- **Sajten står still under ALTER.** När ALTER ska byta tabell väntar den på
  pågående frågor, och nya frågor köar bakom den. php-fpm-poolen (10 barn) kan
  fyllas. Skydd: låg trafik, pausad import, kort `lock_wait_timeout`, nginx
  fastcgi-cache.
- **En fråga blir långsammare.** InnoDB lagrar i primärnyckelordning och kan
  välja andra index. Skydd: mätningen i steg 2 och 5. Tillbaka med
  `ALTER TABLE texttv ENGINE=MyISAM`.
- **Glömd buffer pool** ger disk-läsningar för allt. Därför steg 1 först.

## Confidence

hög — små tabeller, enkelt schema, vägen tillbaka är samma ALTER.
