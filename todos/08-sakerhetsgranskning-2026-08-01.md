**Status:** aktiv — 12 av 19 fynd stängda 2026-08-01, 7 kvar
**Senast uppdaterad:** 2026-08-01

# Todo #08 — Säkerhetsgranskning 2026-08-01

Granskning av `texttv.nu/` (CodeIgniter 2.2.6) och `importer/` (Laravel 8.83).
Kodgenomläsning av controllers, modell, helpers, vyer, konfig och deploy, plus riktad
provning mot prod. **Inte** ett penetrationstest — `codeigniter/system/` lästes bara punktvis.

**Svaren på utgångsfrågorna:**

- **SQL-injection:** inga utnyttjbara. All användarstyrd data mot SQL passerar `is_numeric`-filter
  (`Texttv_page::extract_pages_from_ranges`), `intval`, `db->escape()` eller `escape_like_str()`.
- **XSS:** en bekräftad reflekterad XSS i prod (K1) — stängd.
- **Kan användare posta data:** ja. Var fem oautentiserade skrivvägar, är nu tre (se M1).

Efter 2026-08-01 finns inget känt kvar där en utomstående kan köra kod, läsa ut credentials
eller komma åt något de inte ska. Resten är missbruk och härdning.

---

## Kvar att göra (7)

| # | Vad | Lösning | Var |
| --- | --- | --- | --- |
| **M1** | Oautentiserad skrivning till DB. `/api/page/` styr "mest lästa" (INSERT i `page_actions`, joinas mot `texttv` i `get_most_read_pages_for_period()`), `/api/share/` + `/api/get_permalink/` + `/oembed` styr "mest delade" (`is_shared + 1` via `mark_archive_ids_as_shared`) | Rate limiting per IP; helst delad hemlighet från apparna på `/api/page/` | Kod |
| **M2** | `api.php:566` kör `exec(wkhtmltoimage …)` per `/api/screenshot/`-anrop. Filnamn = `md5($url)`, så olika id-ordning ger obegränsat antal JPG:er i `/shares/`. `l.texttv.nu/live/{n}` skrapar svt.se per anrop | Rate limiting, tak för antal id per anrop, städjobb för `/shares/` | Kod |
| **M4** | EOL-ramverk: CodeIgniter 2.2.6 (2015), Laravel 8.83.27 (jan 2023). Inga kända CVE:er träffar koden som den används, men inga fixar kommer | Laravel 8 → 10/11 först. CI 2 → omskrivning. Plan, inte patch | Kod |
| **M5** | Publika dev-ytor: `/dev/search`, `/dev/stats`, `/dev/updated`, `l.texttv.nu/importstatus`, `/live/{n}`, `/db/{n}`. `/pi.php` är skyddad av env-secret men bör bort | Radera `dev`-controllern (ser oanvänd ut); importerns rutter bakom basic auth eller IP-spärr | Kod + server |
| **M6** | Ingen `Content-Security-Policy`, ingen `X-Frame-Options`. `Access-Control-Allow-Origin: *` på alla HTML-sidor, inte bara API:t | Nginx: CSP i `report-only` först, `X-Frame-Options: SAMEORIGIN`, `ACAO` bara på `/api/*` | Server |
| **L2** | `importer/app/Classes/Importer.php:109-190` lägger varje tecken från SVT rått i `<span>`. Att `<` inte blir XSS beror bara på att varje tecken hamnar i egen span; `if (!$charInfo) return $char;` (rad 118) släpper igenom obehandlade tecken i följd | `htmlspecialchars($char, ENT_QUOTES)` | Kod |
| **L7** | Ingen rate limiting någonstans i stacken | Går upp i M1 + M2 | Kod + server |

**Föreslagen ordning:** M6 ger mest per insats (ren nginx-config, ingen PHP rörs, och en CSP är
djupförsvar mot hela XSS-klassen). Sedan M1 + M2 + L7 som ett paket. M4 är den strukturella skulden.

**M5 och M6 bor på servern** — prod-nginx är inte versionshanterat i repot, så de kan inte deployas
via `main` som allt annat i den här todon.

---

## Klart 2026-08-01

Alla deployade via `main` och verifierade mot prod direkt efter deploy.

| # | Fynd | Åtgärd | Commit |
| --- | --- | --- | --- |
| **K1** | Reflekterad XSS: `jsoncallback` echoades rått och `get_html` gick ut som `text/html`. Ingen CSP | Vitlista `^[A-Za-z0-9_.]{1,64}$` i `Api::get_valid_jsoncallback()`. Content-type medvetet orörd — CLAUDE.md varnar för det mot shippade appar, och vitlistan räcker | `e1bcbad` |
| **K2** | Shutdown-handler skrev `$_SERVER` + `$GLOBALS` (dvs `DB_PASSWORD`) till `DOCUMENT_ROOT/debug.txt` vid varje fatalt fel. Triggades anonymt via `/api/updated/abc/0`. Skrivningen misslyckades på root-ägd webbrot — en filrättighet från kreddläcka | Hela `register_shutdown_function`-blocket borttaget | `e1bcbad` |
| **K3** | `ENVIRONMENT` hårdkodat `development` → `error_reporting(E_ALL)` i prod. Gav stack traces med sökvägar och SQL till vem som helst, och PHP-varningar mitt i `/api/*`-JSON:en | Miljön härleds: `APP_ENV=local` (Docker) eller `texttv.nu.test` (Valet) → development, allt annat → production. `db_debug` följer `ENVIRONMENT`. Två grundorsaker fixade: `isset()` på `$pagedescription`, och vakt mot `IN ()` i `Api::updated()` — sedan PHP 8.1 kastar mysqli undantag, så CI:s egen `db_debug`-hantering nås aldrig och `db_debug = FALSE` hade inte räckt | `e1bcbad` |
| **K4** | FB page access token hårdkodad i publikt repo. `/fb/webhook` utan signaturverifiering → godtyckliga Messenger-meddelanden via vår token, obegränsad skrivning i `texttv_log`, phantomjs-process per siffergrupp | Hela `fb`-controllern + rutterna borttagna. Token verifierad **död** hos Meta (`Valid: False`, utfärdad 2016-04-15, data access utgången 2020-08-25) | `9a0c385` |
| — | scp-deployen speglar inte bort raderade filer, och CI auto-routar till en controller även utan route-rad | `rm -f` för borttagna controllers i ssh-steget i `deploy-website.yml` | `af7b178` |
| **M3** | oembed ekade `?url=` oescapad in i `html`-fältet som WordPress m.fl. bäddar in | Permalänken byggs från de laddade sidorna via `get_permalink_from_pages()`. Användarinput lämnar svaret helt i stället för att escapas | `6abc10a` |
| **L1** | `$maxcount`, `$days`, `$limit` interpolerades i `LIMIT`/`INTERVAL`. Inte nåbart (alla anropare skickar literaler) | Cast till `(int)` | `1d74fc0` |
| **L3** | Fatala fel på saknade rader: `fakta.php` (okänd slug), `rssfeed.php` (tom bloggtabell) | 404-vy respektive fallback på `time()`. Tog även bort en dubbelkörd, oanvänd query i `fakta.php` | `1d74fc0` |
| **L4** | `blogg.php` anropade `mysqli_real_escape_string()` direkt med `conn_id` | `$this->db->escape()` | `1d74fc0` |
| **L5** | `json_encode()` i ld+json escapar inte `</script>` | `JSON_HEX_TAG` i bloggen. Genomgången hittade **två värre fall i `header.php`**: arkivsidans `NewsArticle`-block echoade `$page_title`/`$meta_description` m.fl. helt oescapade (ett citattecken i en nyhetstext bröt Googles strukturerade data för hela sidan), och live-blocket saknade `JSON_HEX_TAG` trots `JSON_UNESCAPED_SLASHES`. Båda fixade | `1d74fc0` |
| **L6** | `encryption_key` tom | Läses från `$_SERVER['ENCRYPTION_KEY']`, tom sträng som fallback. **Ingen nyckel committas** — repot är publikt, en nyckel i filen vore samma sak som ingen nyckel | `1d74fc0` |
| **L8** | `log2db()`, `json_encode_pretty()`, `removeWhiteSpace()` döda efter K4 | Borttagna. `texttv_log`-tabellen är **kvar** i databasen med sin historik | `1d74fc0` |
| **L9** | oembed matchade `!\d+!`, dvs *första* siffergruppen. `/149/vm-2026-guld-till-sverige-7336730` löste upp till id 2026 — årtal i rubriker är vanligt, så riktiga inbäddningar har visat fel sida | Matchar sista siffergruppen; query och fragment kapas först (annars blev `?fbclid=abc123` tolkat som id) | `e8a6b2d` |

## Kvarstående manuella åtgärder (ej kod)

- **Radera Facebook-appen 323210141035659.** Ren städning — app-ID:t används inte någonstans i
  kodbasen, och Facebook-*sidan* påverkas inte. Appen har fortfarande en webhook mot en URL som ger 404.
- **Överväg att rotera prod:ens DB-lösenord.** Inte för att det läckt — `/debug.txt` gav 404 hela
  tiden — utan för att det i flera år funnits en kodväg som skrev det till webbroten (K2).

## Anteckningar

- Granskningen gjorde ett skrivande testanrop mot prod: `GET /api/page/999999/view` (en rad i
  `page_actions`, demonstrerar M1). Städas av `texttv:cleanup-page-actions` inom retentionen.
- App-repona (`texttv.nu (app)`, iOS/Android) ingick inte i granskningen.
