**Status:** avfärdad 2026-11-01 — beslut: webben står kvar på CI 2 (inga kända hål som träffar sajten, flytten ger användarna inget). Tas upp igen om webben ska få inloggning, formulär eller uppladdning; analysen nedan är underlaget
**Senast uppdaterad:** 2026-11-01

# Todo #36 — Webben från CodeIgniter 2 till Laravel 13

## Sammanfattning

Hur svårt är det att flytta webben (`texttv.nu/`) från CodeIgniter 2.2.6 till Laravel 13, som en egen app skild från importern? **Medelsvårt, mest för att beteendet måste bli identiskt, inte för koden.** Koden använder lite av CI och översätts mest mekaniskt. Det som tar tid är att `/api/*` och sidorna ska ge samma svar byte för byte, att nginx-cachen fortsätter fungera och att webbroten ligger kvar orörd. Uppskattning: **8–12 arbetsdagar** för en flytt i ett svep med koden och vyerna nästan som de är, plus en vecka i prod under uppsikt. En omskrivning till idiomatisk Laravel (Blade, Eloquent) är 3–4 veckor och ger användarna inget.

Bakgrund: CI 2 har inga kända hål som träffar sajten (genomgånget 2026-11-01, se #15), men får aldrig fixar. Beslutet i #15 är att stå kvar tills ett konkret behov finns, t.ex. inloggning, formulär eller uppladdning.

## Vad som ska flyttas (räknat 2026-11-01)

| Del                      | Omfång                                                                                                  |
| ------------------------ | ------------------------------------------------------------------------------------------------------- |
| Controllers              | 14 filer, 2 766 rader. Tyngst: `api.php` 718, `sida.php` 665, `status.php` 329                           |
| Modell                   | `texttv_page.php`, 1 257 rader, 31 metoder. Mest rå SQL, några query builder-anrop                      |
| Helper                   | `texttv_helper.php`, 2 132 rader, 53 globala funktioner, `get_instance()` 12 gånger                      |
| Libraries                | `Share_image.php` (GD, delningsbilderna, #34) och `Feed.php`, 543 rader                                  |
| Vyer                     | 36 filer, 4 398 rader, vanlig PHP. Största `header.php` 877                                              |
| Rutter                   | 37 rader i `routes.php` **plus automatisk routing**: varje publik metod nås som `/controller/metod/arg`, t.ex. `/api/most_read/news`, `/status/health`, `/sok`. De måste listas uttryckligen i Laravel |
| Webbroten utanför CI     | `shares/` (delningsbilder), `pi.php`, `privacy-policy.php`, `service-worker.js`, `manifest.json`, `offline.html`, `css/`, `js/`, `images/`, `fonts/`, `bloggimages/`, ikoner, `ads.txt`, `llms.txt` och verifieringsfiler. Allt ska ligga kvar på samma URL |

**CI-API:er som används** (räknat i controllers, modell, helper, libraries och vyer): `load->view` 92, `input->get` 50, `output->append_output` 23, `db->query` 21, `output->set_content_type` 11, `output->set_header` 8, `db->escape` 3, några query builder-anrop (`where`, `order_by`, `limit`, `get`, `insert_string`, `update_string`), `uri->uri_string`, `benchmark`, `agent->is_robot`. Ingen session, ingen formulärvalidering, inget `xss_clean`, ingen uppladdning eller e-post. Det är en liten yta att ersätta.

## Mätt: prestanda

Prod, söndag 11–12 (2026-11-01): 280 245 requests, 42 835 till PHP (11,9/s), resten från nginx-cachen. PHP-tid p50 4 ms, p95 10 ms, p99 15 ms. Kvällarna har 2–3 gånger mer trafik.

Lokalt, samma image (PHP 8.4, OPcache på), `ab -n 3000 -c 4`:

| Mätning                                                              | Tid per request |
| -------------------------------------------------------------------- | --------------- |
| CI 2 `/api/get/100` (hela den riktiga koden)                          | 6,1 ms          |
| Laravel 13 förenklad `/api/get/100` (en query, samma JSON-storlek)    | 3,7 ms          |
| Laravel 13 tom rutt (ramverkets grundkostnad)                         | 2,5 ms          |

Laravels grundkostnad är alltså ungefär 2,5 ms per request. Med 30–40 requests per sekund till PHP på kvällen och en vCPU som är dubbelt så långsam som Macen blir det runt 0,2 CPU-kärnor extra av 2. Hanterbart, men mät på prod-lik last innan bytet.

## Det som är svårt

1. **nginx-cachen får inte sluta fungera.** Som standard cachar nginx inte svar med `Set-Cookie` eller `Cache-Control: private/no-cache`. En tom Laravel 13 skickar båda: rutter i `web`-gruppen sätter `XSRF-TOKEN` och `laravel-session` på varje svar, och alla svar får `Cache-Control: no-cache, private`. CI skickar på `/api/get` bara `Content-Type: application/json`. Lösning: inga rutter i `web`-gruppen (egen grupp utan session, kakor och CSRF), och ingen standard-`Cache-Control`. Verifiera med `X-Cache` mot staging. Missas det går all trafik (280 000 i timmen) till PHP.
2. **`/api/*` byte för byte**, för apparna som inte kan uppdateras. Headers (`Content-Type` utan charset där CI har det, inga extra headers), JSON-formatet (CI kör `json_encode()` utan flaggor; Laravels `JsonResponse` ger samma utdata, kontrollerat 2026-11-01, men den sätter `Content-Type: application/json` och kan inte ge CI:s exakta headers utan att sättas för hand), JSONP-callbacken, statuskoder och felfallen. `make test-api` är byggt för det men jämför i dag två git-refs av samma app; det behöver kunna köra CI-versionen och Laravel-versionen sida vid sida, och HTML-sidor bör läggas till i `urls.txt`.
3. **CI:s egenheter som koden räknar med.** `input->get()` ger `FALSE` när värdet saknas, inte `null` (8 jämförelser med `false` i koden, fler i vyerna). CI tillåter bara `a-z 0-9~%.:_\-,` i URL:en (`permitted_uri_chars`) och svarar annars med fel; Laravel tar emot allt. Ett snedstreck på slutet, versaler och `%`-kodning tolkas olika. `routes.php` har `$route['api/get']` två gånger, och den sista (`api/share`) gäller. Alla sådana fall måste hittas och efterliknas eller medvetet ändras.
4. **Logik i vyerna.** Vyerna anropar CI direkt: `$this->input->get` 41 gånger, `$this->output->append_output` 11, `$this->load->view` 8, plus `benchmark` och `db->total_queries`. Antingen ett litet kompatibilitetsobjekt som vyerna får som `$this`, eller sök och ersätt (`request()->query()`, `echo view(...)`).
5. **Webbroten och deployen.** Laravel vill ha `public/` som webbrot. Antingen flyttas de statiska filerna dit (nginx-vhosten på prod ändras: `root`, `location`-blocken för `pi.php` och `privacy-policy.php`, `shares/`), eller så läggs Laravel bredvid och `index.php` i dagens webbrot pekar in. DB-uppgifterna kommer i dag som `fastcgi_param` i nginx (se `server.md`); Laravel läser `.env` eller `$_SERVER`. Ändringar i nginx på prod kräver Pärs ok.
6. **Två databasanslutningar**: `default` (`texttv.nu`, med en punkt i namnet) och `stats` (`texttv_stats`, `page_actions`). Stats används i `api.php` och `status.php`. Två anslutningar i `config/database.php`; queries med `` `texttv.nu`.texttv `` fungerar oförändrade.

## Det som är lätt

- **Vyerna kan vara kvar som PHP.** Laravel renderar `.php`-vyer med sin PHP-motor, så Blade behövs inte. Bara CI-anropen i dem ändras.
- **Helpern kan laddas oförändrad** via `autoload.files` i `composer.json`; `get_instance()` (12 ställen) byts mot `DB::` och `view()`.
- **SQL:en är rå** och flyttas till `DB::select()`/`DB::selectOne()` nästan som den är. Resultaten blir objekt (`stdClass`) som i CI:s `result()`, men CI:s `result_array()`/`row_array()` ger arrayer; varje ställe måste kontrolleras.
- **Ingen session, inloggning eller formulär** att flytta.
- **Teststödet blir bättre**: Laravels HTTP-tester, PHPStan på högre nivå än dagens nivå 1, och CI:s lokala patchar i `system/` (`AllowDynamicProperties` m.fl.) försvinner.

## Förslag: flytt i ett svep, beteendet låst av jämförelsetester

1. **Jämförelsen först** (2–3 dagar, kan göras utan att flytten bestäms): bygg ut `make test-api` så att den jämför två körande appar (CI på en port, Laravel på en annan) mot samma DB, och lägg till sidor (`/`, `/100`, `/100,300`, `/101-103`, arkiv- och dagsidor, `/nyheter/igar`, `/sida/...`, `/status`, `/sitemap.xml`, `/feed`, 404) i `urls.txt`. Ta med felaktiga URL:er (otillåtna tecken, snedstreck på slutet, versaler).
2. **Laravel-appen** i `texttv.nu-laravel/` (eller motsvarande) bredvid den gamla (4–6 dagar): rutter uttryckligen, en middleware-grupp utan session och kakor, ingen standard-`Cache-Control`, controllers översatta ett till ett, modellen och helpern med `DB::`, vyerna kvar som PHP med ett tunt kompatibilitetsobjekt, `Share_image` och `Feed` som klasser. Iterera tills jämförelsen är tom eller varje skillnad är granskad.
3. **Prod** (1–2 dagar plus en vecka under uppsikt): deploy-workflow som importerns (härdad, med återställning), nginx-vhosten med Pärs ok, byte en kväll med låg trafik och den gamla katalogen kvar för snabb återgång. Följ `X-Cache`, PHP-tid och `max_children` i `/prod-health`.

Efteråt, i egen takt: Blade, Eloquent och idiomatisk kod där det lönar sig.

## Risker

- **Tysta skillnader för apparna** (header, JSON-escaping, felfall) som inte täcks av `urls.txt`. Skydd: bredda URL-listan med verkliga anrop ur `access.log`.
- **nginx-cachen** (punkt 1 ovan). Skydd: verifiera `X-Cache` före bytet, larm på PHP-tid.
- **Årliga uppgraderingar.** Laravel släpper en major om året (två års säkerhetsfixar). CI 2 kräver inget, men får heller inga fixar. Med två Laravel-appar blir det två uppgraderingar om året, i samma takt.
- **SEO**: samma URL:er och samma HTML ger ingen påverkan; ändrad HTML eller statuskod på arkivsidor kan påverka (jfr #09–#11).

## Confidence

medel — omfånget är räknat och prestandan mätt, men uppskattningen av tiden för att hitta och efterlikna CI:s egenheter (punkt 3) är osäker, och det är där tiden går.
