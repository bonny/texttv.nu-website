**Status:** aktiv
**Senast uppdaterad:** 2026-05-19

# Todo #03 — Fixa `texttv:cleanup-page-actions` DB-auth

## Sammanfattning

Artisan-kommandot `texttv:cleanup-page-actions` kraschar **vid varje schemalagd körning** med:

```
SQLSTATE[HY000] [1045] Access denied for user 'root'@'localhost' (using password: YES)
(SQL: delete from `page_actions` where `created_at` < ... limit 100000)
```

Den körs frekvent (~21 fel på ~5h enligt loggsampel 2026-05-19 → uppskattningsvis hourly) och **lyckas aldrig städa `page_actions`-tabellen**. Tabellen växer obegränsat sedan tabellen togs i bruk.

Upptäckt vid diagnostik i [[02-logrotate-laravel-log-8gb]] — felen kvarstår efter att INFO-spam tystats, så de blir nu mer synliga.

## Bakgrund

`app/Console/Commands/CleanupPageActions.php:42` använder `DB::connection('mysql_stats_db')`. Connection-config i `config/database.php:66-84` läser `DB_STATS_*` från `.env`.

Prod `.env` (verifierad 2026-05-19, lösenord redaktat):

```
DB_STATS_HOST=localhost
DB_STATS_DATABASE=texttv_stats
DB_STATS_USERNAME=root
DB_STATS_PASSWORD=<satt-men-fel>
```

MariaDB (10.11.14) nekar — sannolikt fel lösenord (root-lösen kan ha ändrats sedan setup), alternativt fel grant-host. Felet är konsistent → inte transient.

Som jämförelse: `mysql`-connection (samma `.env`) funkar bra — importerns scheduler skriver/läser hela tiden. Det är **bara** `mysql_stats_db` som har trasiga creds.

Sidoeffekt: varje fail triggar `Whoops\Exception\ErrorException: Using ${var} in strings is deprecated` i `vendor/facade/ignition/.../MergeConflictSolutionProvider.php:52` (PHP 8.2-deprecation från Laravel 8 ignition). Den loggas också som ERROR och försvinner när primärfelet fixas.

## Förslag

**Steg 1 — Verifiera vilken stats-DB-user som faktiskt finns:**

```bash
# På prod, som root:
sudo mysql -e "SELECT user, host FROM mysql.user WHERE user LIKE '%stats%' OR user='root';"
sudo mysql -e "SHOW GRANTS FOR 'root'@'localhost';"
```

**Steg 2 — Två vägar att fixa:**

**A. Dedikerad stats-user (rekommenderat, principle of least privilege):**

```sql
CREATE USER 'texttv_stats'@'localhost' IDENTIFIED BY '<starkt-lösen>';
GRANT SELECT, INSERT, UPDATE, DELETE ON texttv_stats.* TO 'texttv_stats'@'localhost';
FLUSH PRIVILEGES;
```

Uppdatera prod `.env`:
```
DB_STATS_USERNAME=texttv_stats
DB_STATS_PASSWORD=<starkt-lösen>
```

Kör `php artisan config:cache` efteråt (annars läser Laravel cache:n från [[02-logrotate-laravel-log-8gb]]-arbetet).

**B. Korrigera root-lösen i .env (snabbfix, sämre säkerhet):**

```bash
# Hitta nuvarande root-lösen — finns sannolikt i /root/.my.cnf eller liknande
sudo cat /root/.my.cnf 2>/dev/null
# Uppdatera DB_STATS_PASSWORD i .env, kör config:cache
```

**Steg 3 — Verifiera:**

```bash
cd /usr/share/nginx/l.texttv.nu/importer
php artisan texttv:cleanup-page-actions --limit=1000
# Förvänta: "Rader borttagna: <N>" (inte exception)
tail -20 storage/logs/laravel.log  # bör vara tyst på SQLSTATE-fel
```

**Steg 4 — Kolla om `page_actions`-tabellen behöver engångs-städ:**

Om den har växt mycket pga 11 månaders missade städningar — kör manuellt med stor `--limit` (eller via schemaläggaren som tar 100k/körning).

```sql
SELECT COUNT(*), MIN(created_at), MAX(created_at) FROM texttv_stats.page_actions;
```

## Risker

**Låg.**

- Alt A kräver root-MySQL-access för att skapa user/grants. Standardflöde.
- Alt B är snabbt men låter Laravel köra som DB-root → bredare blast-radius om appen exploiteras (SQL-injection blir worst-case). Inte rekommenderat.
- `page_actions`-tabellen kan vara stor (11 månader utan städ). `delete ... limit 100000` per körning är bra: tar några dygn att städa ifatt utan locks/replication-press.

## Confidence

**Hög** för diagnos (felmeddelandet är entydigt, config-pathen är spårad). **Medel** för åtgärd — beror på om `DB_STATS_PASSWORD` i `.env` matchar något känt MySQL-user (möjligen root med roterat lösen). Steg 1 (verifiering) avgör vilken väg som är snabbast.

## Beroende

Inget hårt beroende, men trevligt att göra **efter** [[02-logrotate-laravel-log-8gb]] (logrotate + truncate) — då blir det enklare att verifiera att felet faktiskt försvinner när man inte behöver gräva i en 8 GB-fil.
