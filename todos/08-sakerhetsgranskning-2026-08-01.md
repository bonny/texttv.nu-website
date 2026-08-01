**Status:** aktiv
**Senast uppdaterad:** 2026-08-01

# Todo #08 — Säkerhetsgranskning 2026-08-01

Granskning av `texttv.nu/` (CodeIgniter 2.2.6) och `importer/` (Laravel 8.83).
Fynden nedan bockas av löpande. Verifierade mot prod med read-only GET där inget annat anges.

**Svar på utgångsfrågorna:**

- **SQL-injection:** inga utnyttjbara. All användarstyrd data mot SQL passerar `is_numeric`-filter
  (`Texttv_page::extract_pages_from_ranges`), `intval`, `db->escape()` eller `escape_like_str()`.
  Se L1 för latenta hål och K3 för att felmeddelandena läcker frågorna.
- **XSS:** en bekräftad reflekterad XSS i prod (K1).
- **Kan användare posta data:** ja, fem oautentiserade skrivvägar (M1).

## Kritiskt

- [x] **K1 — Reflekterad XSS via `jsoncallback`.** `views/api.php:139-145`. `get_html`-grenen sätter aldrig
      content-type → svaret går ut som `text/html` med callbacken oescapad. Ingen CSP på domänen.
      PoC: `/api/get_html/100?jsoncallback=XSSTEST%3Cscript%3E` → `content-type: text/html`.
      `get`-grenen (rad 96-112) har samma brist men räddas av `application/json` + `nosniff`.
      **Fix:** vitlista callbacken (`^[A-Za-z0-9_.]{1,64}$`) + `set_content_type("application/javascript")`.
      **Åtgärdat 2026-08-01:** vitlista `^[A-Za-z0-9_.]{1,64}$` i `Api::get_valid_jsoncallback()`,
      används av både `get` och `get_html`. Content-type lämnades medvetet orörd — att ändra den på
      `/api/*` är precis vad CLAUDE.md varnar för mot shippade appar, och vitlistan stänger hålet ensam.
      Kvar som frivillig städning: `get_html` borde svara `application/json` i stället för `text/html`.
- [x] **K2 — Fatalt fel dumpar DB-lösenordet till webbroten.** `config/config.php:6-44`. Shutdown-handlern
      skriver `$_SERVER` + `$GLOBALS` till `DOCUMENT_ROOT/debug.txt` vid varje `E_ERROR`. `$_SERVER` innehåller
      `DB_PASSWORD`/`DB_USERNAME` (injiceras som `fastcgi_param`). Fatalt fel triggas anonymt via
      `/api/updated/abc/0`. `/debug.txt` ger 404 idag (root-ägd webbrot → skrivningen misslyckas), dvs
      en rättighetsändring från publik kreddläcka.
      **Åtgärdat 2026-08-01:** hela `register_shutdown_function`-blocket borttaget ur `config.php`. Behövs
      diagnostik igen: `error_log()`, den hamnar utanför webbroten. Triggern (`/api/updated/abc/0`) är
      dessutom borta via vakten i K3, så det finns inte längre någon känd väg till ett fatalt fel här.
- [x] **K3 — `display_errors` + `db_debug` på i prod.** `config/database.php:66,86`. `/api/updated/abc/0`
      returnerar full stack trace, absoluta sökvägar och den SQL-fråga som fallerade. Samma läckage smutsar
      ner `/api/*`-svaren idag (`Undefined variable $pagedescription` mitt i JSON:en till apparna).
      **Åtgärdat 2026-08-01:** `codeigniter/index.php` hårdkodade `ENVIRONMENT = 'development'`, vilket gav
      `error_reporting(E_ALL)` på prod. Nu härleds miljön: `APP_ENV=local` (Docker, via fastcgi_param) eller
      `texttv.nu.test` (Valet) → development, allt annat → production. `db_debug` följer `ENVIRONMENT` i
      stället för att vara hårdkodat `TRUE` (live-grenen används även av Docker-dev, så villkoret måste hänga
      på miljön). Dessutom fixad i grunden: `pages_inner_output_current.php:22` saknade `isset()` på
      `$pagedescription` — det var den varning som läckte in i `/api/get_html`-svaren.
      Triggern för 500:an är också borta: `Api::updated()` bommar ut på tom sidlista i stället för att bygga
      `IN ()`. (Sedan PHP 8.1 kastar mysqli undantag i stället för att returnera `false`, så CI 2.2.6:s egen
      `db_debug`-hantering nås aldrig — `db_debug = FALSE` ensamt hade inte räckt.)
- [x] **K4 — Facebook page access token hårdkodad i publikt repo.** `controllers/fb.php:8,13`, repot är
      PUBLIC på GitHub. `/fb/webhook` (rad 31) verifierade ingen `X-Hub-Signature` → vem som helst kunde få
      servern att skicka Messenger-meddelanden via vår sida till godtyckligt `recipient.id`. Verify-token
      var `"yolo_verify"`. **Åtgärdat 2026-08-01:** hela `fb`-controllern och rutterna borttagna, deployad
      och verifierad (`/fb/webhook` → 404).
      **Token verifierad död 2026-08-01** via Metas Access Token Debugger: `Valid: False`, invaliderad av
      Facebook. Utfärdad 2016-04-15, data access utgången 2020-08-25. App ID 323210141035659 (TextTV.nu),
      Page ID 217037721716285. Scopes den *hade*: `pages_messaging`, `pages_manage_ads`,
      `business_management`, `pages_read_user_content` m.fl. — brett, men aldrig utnyttjbart eftersom
      sessionen var invaliderad långt innan repot granskades. Ingen ytterligare åtgärd krävs.
      Frivillig upprensning: appen 323210141035659 finns kvar och är fortfarande kopplad till sidan med
      en webhook mot en URL som nu ger 404. App-ID:t används **inte** någonstans i kodbasen (ingen FB SDK,
      ingen `fb:app_id`, inga delningsknappar), så appen kan raderas utan att något på sajten påverkas.

## Medel

- [ ] **M1 — Oautentiserade skrivningar till databasen.** Ingen är injicerbar, men "ingen kan posta data"
      stämmer inte idag:
      - `/api/page/{ids}/{type}` (`api.php:640-704`) → INSERT i `page_actions`. `page_ids` joinas mot `texttv`
        i `get_most_read_pages_for_period()` → **"mest lästa"-listan kan manipuleras fritt**.
      - `/api/share/{ids}`, `/api/get_permalink/{ids}`, `/oembed?url=` → `UPDATE texttv SET is_shared = is_shared + 1`
        (`texttv_helper.php:mark_archive_ids_as_shared`) → delningsräknare och "mest delade" kan pumpas.
      - ~~`POST /fb/webhook` → INSERT i `texttv_log` av hela råa bodyn~~ — borttagen med K4.
      **Fix:** rate limiting per IP + storleksgräns; helst delad hemlighet från apparna för `/api/page/`.
- [ ] **M2 — Anonym processpawn och diskfyllning.** `api.php:566` kör `exec(wkhtmltoimage …)` per
      `/api/screenshot/{ids}`-anrop. Argumenten är rent numeriska (ingen kommandoinjektion), men filnamnet är
      `md5($url)` → olika ordning/intervall ger obegränsat antal nya JPG:er i `/shares/`.
      `l.texttv.nu/live/{n}` är publik och hämtar live från svt.se vid varje anrop.
      (`fb.php:382` `system(phantomjs …)` — borttagen med K4.)
- [x] **M3 — HTML-injektion i oembed.** `controllers/oembed.php:135` — `$url` från query-strängen läggs
      oescapad i `<a href="%s">` i `html`-fältet som JSON-konsumenter (WordPress m.fl.) bäddar in.
      **Åtgärdat 2026-08-01:** permalänken byggs nu från de laddade sidorna via
      `get_permalink_from_pages()` i stället för att eka tillbaka `?url=`. Användarinput lämnar därmed
      svaret helt — bättre än att escapa den — och länken blir alltid den kanoniska. `$url` används
      fortfarande för att parsa ut id:n, men skrivs aldrig ut.
- [ ] **M4 — EOL-ramverk.** CodeIgniter 2.2.6 (support slut 2015), Laravel 8.83.27 (säkerhetsstöd slut
      jan 2023). Inga kända CVE:er slår direkt mot koden som den används här, men inga fixar kommer.
- [ ] **M5 — Publika debug-/dev-ytor.** `/dev/search`, `/dev/stats`, `/dev/updated` (200 i prod),
      `l.texttv.nu/importstatus`, `/live/{n}`, `/db/{n}`. `/pi.php` är skyddad av env-secret (OK men bör bort).
- [ ] **M6 — Saknade svarsheaders.** Ingen `Content-Security-Policy`, ingen `X-Frame-Options`/`frame-ancestors`.
      `Access-Control-Allow-Origin: *` sitter på HTML-sidorna, inte bara `/api/*`. En CSP hade neutraliserat K1.

## Lågt / härdning

- [ ] **L1 — Latenta SQL-injektioner.** `helpers/texttv_helper.php`: `$maxcount` (`get_latest_updated_pages`),
      `$days`/`$limit` (`get_shared_pages`) interpoleras rakt in i `LIMIT`/`INTERVAL`. Alla anropare skickar
      literaler idag → inte nåbart. Kasta till `(int)`.
- [ ] **L2 — Ingen output-escaping av SVT-innehåll.** `importer/app/Classes/Importer.php:109-190` lägger varje
      tecken rått i `<span>`. Att `<` inte blir XSS beror bara på att varje tecken hamnar i egen span;
      `if (!$charInfo) return $char;` (rad 118) släpper igenom obehandlade tecken i följd.
      **Fix:** `htmlspecialchars($char, ENT_QUOTES)`.
- [ ] **L3 — Saknade null-kollar ger fatala fel** (→ K2/K3): `controllers/fakta.php:16` (`$res->row()->title`
      vid okänd slug), `rssfeed.php:65` (`$firstEntry->…` om bloggtabellen är tom).
- [ ] **L4 — `blogg.php:33`** använder `mysqli_real_escape_string($this->db->conn_id, …)` direkt istället för
      `$this->db->escape()`. Funkar, men går sönder tyst om drivrutinen byts.
- [ ] **L5 — `views/blogg_overview.php:43-44`** — `json_encode()` i `<script type="application/ld+json">`
      escapar inte `</script>`. Bloggtiteln är admin-skriven, så bara teoretiskt.
- [ ] **L6 — `config.php:269` `encryption_key` tom.** Ofarligt idag (sessioner används inte), kritiskt om de slås på.
- [ ] **L7 — Ingen rate limiting** någonstans i stacken.
- [x] **L9 — oembed plockar fel id ur slugs med siffror.** `oembed.php:22` matchar `!\d+!`, dvs *första*
      siffergruppen i sista URL-segmentet. `/100/topp-10-nyheter-8490933/` ger id `10` i stället för
      `8490933`. Upptäckt vid M3-testet (payloaden `alert(1)627` gav id `1`). Ofarligt men fel sida
      returneras. **Fix:** matcha sista siffergruppen (`!(\d+)$!`). Lämnad orörd tills vidare eftersom
      den ändrar vilken sida befintliga inbäddningar löser upp till — egen commit, egen verifiering.
- [ ] **L8 — Död kod efter K4.** `log2db()`, `json_encode_pretty()` och `removeWhiteSpace()` i
      `helpers/texttv_helper.php` hade bara `fb.php` som anropare (0 träffar kvar i `application/`).
      `log2db()` var skrivvägen in i `texttv_log`. Medvetet kvarlämnade för att hålla K4-deployen liten;
      ta bort dem (och ev. `texttv_log`-tabellen) i en egen commit.

## Ordning

1. ~~K4 (ta bort fb-controllern)~~ — klar 2026-08-01, deployad.
2. ~~K1, K2, K3~~ — klara 2026-08-01, deployade i samma omgång.
3. M6 — CSP + `X-Frame-Options`, begränsa `ACAO: *` till `/api/*`.
4. M1 + M2 — rate limiting.
5. M3 (oembed), M5 (dev-ytor), sedan L-listan.

## Anteckningar

- Granskningen gjorde ett skrivande testanrop mot prod: `GET /api/page/999999/view` (en rad i
  `page_actions`, demonstrerar M1). Städas av `texttv:cleanup-page-actions` inom retentionen.
- App-repona (`texttv.nu (app)`, iOS/Android) ingick inte i granskningen.
