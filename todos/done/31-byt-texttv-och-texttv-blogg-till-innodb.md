**Status:** klar 2026-10-31 — båda tabellerna InnoDB sedan 2026-10-28; steg 7: `/root/db-backups-2026-10-18` och `-10-22` raderade (kopior på Mac minin)
**Senast uppdaterad:** 2026-10-31

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

| Tabell         | Rader     | Data     | Index  |
| -------------- | --------- | -------- | ------ |
| `texttv`       | 1,6 milj. | 1 342 MB | 143 MB |
| `texttv_blogg` | 81        | 0        | 0      |

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

**Lokalt test 2026-10-28** (Mac mini, MariaDB 10.11 i container med 2 CPU,
buffer pool 2 GB, loggfil 96 MB, dumpen från 10-18): `texttv_blogg` under 1 s,
`texttv` 13 s. Alla 1 574 950 rader kvar, `CHECK TABLE` OK. Filen blev 1 656 MB
(MyISAM 1 313 + 115 MB), alltså bara ~16 % större. Räkna med 30–60 s på prod.

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
   - `ALTER TABLE texttv ENGINE=InnoDB;` (13 s lokalt, troligen 30–60 s på
     prod; läsningar går, skrivningar väntar).
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

## Logg

- 2026-10-28 11:05–11:15: steg 1–5 på prod. `innodb_buffer_pool_size` 2G
  dynamiskt och i `/etc/mysql/mariadb.conf.d/70-texttv.cnf` (där även
  `local_infile = 0` från #30). `texttv_blogg` → InnoDB (dump först,
  `/root/texttv_blogg-fore-innodb-20261028.sql`, 600), 81 rader. Importen
  pausad 11:10 (`#PAUSAD-INNODB` i root-crontab, backup
  `/root/crontab.bak-20261028-innodb`). `ALTER TABLE texttv ENGINE=InnoDB`
  11:12:46, **32 s**, 1 575 875 rader. Importen igång 11:13, första
  `IMPORT_SUCCESS` 11:15. Svarstider före/efter oförändrade (±10 ms) för
  `/api/get`, `most_read`, `last_updated`, arkiv, `/sida/delat`, blogg, sök;
  `/api/updated/…` var 1,5–3,5 s redan före (1,7 s efter).
- 2026-10-28 12:28: steg 6. `~/Backups/bin/backup-texttv-db.sh` + LaunchAgent
  `se.eskapism.backup-texttv-db` (07:45, bara Mac minin). `mariadb-dump
--single-transaction --quick --hex-blob --databases texttv.nu texttv_stats`
  som root via socket med nice/ionice, `gzip --fast` på servern, kontroll av
  storlek (> 700 MB), gzip och avslutningsraden, sedan `xz -9` och `xz -t`.
  14 dagars historik i `~/Backups/texttv-db/`, macOS-notis vid fel.
  Testkörning via launchd: dumpen hemma på ~1 min (1 440 MB gz), xz 1 014 MB,
  klar 12:45.

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
