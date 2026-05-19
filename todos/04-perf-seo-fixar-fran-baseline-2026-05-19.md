**Status:** aktiv
**Senast uppdaterad:** 2026-05-19

# Todo #04 — Perf/SEO-fixar från Lighthouse-baseline 2026-05-19

## Sammanfattning

Paraply-todo för de fix-punkter som identifierades i [Lighthouse-baseline
2026-05-19](../baselines/2026-05-19/README.md) och som **inte** redan är
gjorda i Batch 1 (`/700` meta description) eller Batch 2 (HTTP/2 +
säkerhetsheaders + statisk-asset cache). Varje punkt här är liten nog
att inte motivera egen todo, men sammantaget är listan värd att hålla
synlig så den inte tappas bort.

Mätning: efter varje större grupp av fixar, kör om
`baselines/2026-05-19-batch2/run.sh` (kopierad till ny datum-katalog
`baselines/YYYY-MM-DD/`) och skriv `vs <föregående>`-jämförelse i
README:n. Se [`baselines/README.md`](../baselines/README.md) för
metodik.

## Bakgrund

Två baselines togs 2026-05-19:
- [`baselines/2026-05-19/`](../baselines/2026-05-19/) — pre-fixar
- [`baselines/2026-05-19-batch2/`](../baselines/2026-05-19-batch2/) — efter Batch 1 + 2

Resultat efter Batch 1+2: FCP −600 ms överallt, Speed Index −200…−400 ms,
arkivsidan perf 96 → 99, `/700` SEO 85 → 92. Kvarstående audits nedan.

## Förslag — fix-punkter (sorterade efter förväntad vinst)

### A. `errors-in-console` — trivial
**Lighthouse-audit:** Best Practices 0/1.
**Konkret fel:** `Uncaught TagError: adsbygoogle.push() error: No slot
size for availableWidth=0` från
`https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js`.
Pushar `adsbygoogle` innan dess container fått layout.
**Plats:** `texttv.nu/codeigniter/application/views/footer.php` rad
~291 (`(adsbygoogle = window.adsbygoogle || []).push(...)`) + den i
samma view rad ~119 (`<div class='ad ad--before-latest'>`-blockets
script).
**Fix-idé:** vänta på `load`-event eller använd IntersectionObserver
innan push:en körs. Alternativt sätt `style="min-height:N"` på `<ins>`
så `availableWidth` inte är 0 vid push-tillfället.
**Risk:** låg. Påverkar bara annonsladdning. Bör inte bryta sidan.

### B. Defer + inline-script-audit — medium
**Lighthouse-audit:** `render-blocking-resources` (delvis mitigerat av
HTTP/2 men inte borta).
**Hinder:** `polisen.php` rad 52 anropar `jQuery.getJSON()` i inline-
script. Att defer:a `<script src="/js/jquery.min.js">` skulle bryta det
inline-anropet eftersom jQuery inte är laddad när inline-scriptet kör.
**Plan:**
1. Grep alla view-templates efter `jQuery`/`$.` i inline-`<script>`-blocks (`polisen.php:52`, `header.php:37,567`, `pages_inner_output_archive.php:59`, `blogg_overview.php:126`, `pages_current_page_top.php:14`, `appembed/pagerange.php:127`).
2. Wrapa varje inline-jQuery-anrop i `document.addEventListener('DOMContentLoaded', () => { ... })` eller motsv.
3. Lägg `defer` på `<script src="/js/jquery.min.js">`, `js.cookie.js`, `scripts.js` i `footer.php` rad 284-286.
4. Testa varje view manuellt eller via curl + grep för förväntad markup.
**Risk:** medel. Lätt att missa ett inline-anrop → trasig sida.
Verifiera mot Bruno API files innan deploy.

### C. Minifiera + tree-shake JS/CSS — medium
**Lighthouse-audits:** `unminified-javascript`, `legacy-javascript`,
`unused-javascript`, `unused-css-rules`.
**Plan:** Bygg-pipeline saknas idag (filerna i `texttv.nu/js/` och
`texttv.nu/css/` editeras direkt). Möjligheter:
1. Lägg in enkel build-step (esbuild eller swc) som minifierar +
   tree-shakear vid deploy. Risk: introducerar Node-toolchain.
2. Eller manuell genomgång av `scripts.js` + `styles.css` för att ta
   bort uppenbart oanvänt. Lägre vinst, ingen build-komplexitet.
3. Byt ut jQuery mot vanilig JS (modern browsers behöver inte). Stort
   jobb men eliminerar största single-bundlen.
**Risk:** medel-hög beroende på väg.

### D. Bättre meta descriptions per sida (för Google-ranking, ej Lighthouse-score)
**Mål (klargjort 2026-05-19):** Inte SEO-score per se — utan
**ranking + CTR på text-tv-queries** i Google för AdSense-intäkter.
Statiska keyword-rika descriptions vinner över dynamic content-snippets
(text-tv-sidor ändras varje 2:a min, dynamic snippets ger inkonsekvent
SERP).

**Tvådelad plan:**

**Fas 2 — Blockbaserad fallback för icke-whitelist:ade sidor.** Ge
description baserat på sidnummer-block (100-199 inrikes, 200-299
ekonomi, 300-399 sport, osv. enligt SVT Text TV:s konvention som
matchar `breadcrumbs.php`). 95 % av specificiteten med 5 % av jobbet
jämfört med per-sida-tuning. ~700 sidor får keyword-rik description
istället för generic "SVT Text sid NNN".

**Fas 1 — Datadriven utvidgning av whitelist (VÄNTA till efter
2026-06-18).** Använd `mcp-gsc` för att lista topp 100 sidor efter
impressions, korsreferera mot existing whitelist, lägg till 30–50
nya. **Varför vänta:** Todo #01:s 30d-mätperiod (deployerade 18
sidor 2026-05-19) pågår till 2026-06-18. Om vi puttar in 30 sidor
till nu blandas signalerna — vi vet inte vilka som drev förändringen
om #01:s resultat blir bra. Datat från #01:s mätning ger sedan bättre
prioritering för Fas 1 — vi kan välja kandidater där bra description
faktiskt höjde CTR.

**Plats för Fas 2:** `texttv.nu/codeigniter/application/views/header.php`
efter befintliga whitelist-checks (rad 273+). Aktiveras bara om
`$twitter_description` fortfarande är tom efter alla specifika fall.

**Risk:** låg. Påverkar bara SEO/SERP-snippets, inget kan brytas.
Block-mappningen matchar redan `breadcrumbs.php`-konventionen så det
finns historisk konsistens.

### E. `<h1>` per sida
**Lighthouse-audit:** flaggades inte explicit, men avsaknad är ett
välkänt SEO-tapp.
**Plan:** Lägg in `<h1 class="sr-only">NNN — <titel></h1>` i sid-views.
Sr-only = visuellt dold men läses av screenreaders + crawlers.
**Risk:** låg.

### F. `color-contrast`
**Lighthouse-audit:** Accessibility 0/1.
**Plan:** Öppna baseline-HTML-rapport och se exakt vilket element som
flaggas. Text-tv-paletten är speciell (svart bg, färgad text) — kan
vara false-positive eller verklig WCAG-fail. Undersök först, fix sen.
**Risk:** låg-medel. Att fixa kontrast utan att förstöra text-tv-
estetiken kräver finess.

### G. `crawlable-anchors`
**Lighthouse-audit:** SEO 0/1.
**Plan:** Förmodligen cookieinställningar-länken i
`footer.php` rad ~264: `<a onclick="googlefc.showRevocationMessage()">`
saknar `href`. Lägg `href="#"` eller byt till `<button>`.
**Risk:** trivial.

### H. API `Content-Type: text/json` → `application/json`
**Inte i Lighthouse, fynd från curl-verifiering 2026-05-19.**
`controllers/api.php` (CodeIgniter) returnerar `text/json` istället för
`application/json`. Icke-standard, kan bita strict klient. Native iOS/
Android-apparna fungerar idag men dependent på lös parsing.
**Plan:** Hitta `$this->output->set_content_type(...)` i `api.php` och
ändra. Verifiera mot Bruno API files + båda apparna efter deploy.
**Risk:** medel. App-impact-risk (se [`CLAUDE.md`](../CLAUDE.md) sista
gotcha).

### J. Refaktorera header.php-whitelist till associativ array
**Bakgrund (från self-review 2026-05-19):** Whitelist:en på rad ~181–275
har vuxit till 30+ `else if`-grenar med duplicerad struktur (`$meta_title
= "..."; $meta_description = "...";`). Plus 13 block-fallback-grenar
under det.

**Förslag:** Definiera `$meta_overrides = [pagenum => ["title" => "...",
"description" => "..."]]` och `$meta_block_fallbacks = [["min" => N,
"max" => M, "title" => "...%d...", "description" => "...%d..."]]` ovanför
if-blocket. Ersätt if-chain med array-lookup + sprintf för block-fall.

**Varför inte gjord idag:** Försökte 2026-05-19 men Edit av ~150 rad
block misslyckades pga whitespace-mismatch. Att splita i flera mindre
edits ökar regressionsrisken. Bättre i en lugn session med ett enda
fokus, INTE i slutet av en dag med 10+ deploys.

**Plan när vi tar oss an:**
1. Read aktuell header.php-fil
2. Skriv om hela ändrings-blocket via Write (replace entire file or
   targeted range)
3. Verifiera mot ~10 olika sid-typer via curl
4. Förvänta sig ~50% kortare och mer scannable

**Risk:** medel. Refaktor utan tester. Värt försiktighet.

### I. Rotera DB_PASSWORD + VIEW_PHPINFO_SECRET
**Skäl:** Värdena gick genom Claude:s kontext 2026-05-19 när
nginx-config klistrades in. Inte i repot, inte publikt — men inte
längre "bara på servern".
**Plan:**
1. SSH:a in på hetzner. Generera nya värden (`openssl rand -base64 24`).
2. Uppdatera `/etc/nginx/sites-enabled/texttv.nu` `fastcgi_param`-rader.
3. Uppdatera MariaDB-användaren: `ALTER USER 'root'@'localhost' IDENTIFIED BY '<nytt>';`
4. `nginx -t && systemctl reload nginx`.
5. Verifiera att sajten fortfarande renderar och att
   `?VIEW_PHPINFO_SECRET=<gammalt>` inte längre fungerar.
**Risk:** medel. Bryts om password ändras på en sida men inte den
andra. Kör i en sittning.

## Risker

- **Defer-jobbet (B)** är största fallgrop. Lätt att missa en inline-
  jQuery-användning och få trasig sida. Bör verifieras view-för-view.
- **Minify-pipelinen (C)** introducerar Node-toolchain i ett PHP-repo —
  värt att överväga om kostnaden är värd vinsten.
- **API content-type (H)** påverkar shippade app-versioner direkt.
  CLAUDE.md flaggar det.

## Föreslagen ordning

**Status 2026-05-19 kväll:** A delvis fixad, F släppt (tredjepart), G/E/H klara, I/B/C deprioriterade.

1. ~~**A — `errors-in-console`.**~~ Delvis fixat (`bf88bab`), släppt — felet kvarstår men syns inte i normal användning.
2. ~~**G — `crawlable-anchors`.**~~ Klart (`d5a37db`).
3. ~~**I — Rotera credentials.**~~ Deprioriterat 2026-05-19.
4. ~~**E — `<h1>` per sida.**~~ Klart (`7cfa051`).
5. ~~**F — `color-contrast` audit.**~~ Släppt — fyndet är i Googles FC-modal, ej vår kod.
6. ~~**H — API `Content-Type`-fix.**~~ Klart (`1449808`), app-verifierat.
7. **D — Dynamiska meta descriptions.** ENDA KVARSTÅENDE. Större jobb, stor SEO-vinst.
8. ~~**B — Defer + inline-audit.**~~ Deprioriterat — HTTP/2-multiplexing har sänkt vinsten.
9. ~~**C — Minify/tree-shake.**~~ Deprioriterat — kräver build-pipeline-beslut.

## Confidence

**Hög** — fix-punkterna är konkreta och bunden till Lighthouse-output
i `baselines/2026-05-19/` och `baselines/2026-05-19-batch2/`. Två är
trivial-fixar (A, G), en är säkerhets-rotation (I), resten är
välavgränsade kod-ändringar med tydliga mätbara baselines att jämföra
mot.

## Status-logg

- **2026-05-19** — Skapad efter dagens audit + Batch 1 + Batch 2
  deployerade. Innan start.
- **2026-05-19** — Punkterna **I, B, C deprioriterade** (användarbeslut):
  - **I** (rotera DB_PASSWORD + VIEW_PHPINFO_SECRET) — säkerhetsåtgärd
    skippad. Värdena ligger fortfarande i Claude:s konversations-
    kontext från idag. Inte publikt, men inte längre "bara på
    servern". Om de blir komprometterade någon annan väg är vi mer
    sårbara.
  - **B** (defer + inline-script-audit) — vinsten har sjunkit efter
    HTTP/2 multiplexing (Batch 2). Inte värd den komplexiteten just
    nu.
  - **C** (minify/tree-shake JS/CSS) — kräver build-pipeline. Större
    infrastrukturbeslut, väntar.
- **2026-05-19** — Punkt **H klar** (commit `1449808`): API
  content-type bytt från `text/json` till `application/json` i 4
  ställen (3 i `controllers/api.php` + 1 i `views/api.php`). 5 andra
  ställen hade redan rätt värde. Verifierat live mot
  `api.texttv.nu/api/get/100` och `texttv.nu/api/get/100`: båda
  returnerar `application/json` + giltig JSON med oförändrad struktur
  (num, title, date_updated_unix, content_root). Native app-klienter
  parse:ar JSON från raw bytes oberoende av MIME, så ingen påverkan
  förväntad. **App-rökttest 2026-05-19: appen fungerade ✓** — ingen
  regression från MIME-bytet.
- **2026-05-19** — Punkt **F släppt** (tredjepartskod):
  color-contrast-fyndet är inom `.fc-dialog-content` → `.fc-faq-label`
  ("Läs mer") från **Googles Funding Choices** consent-modal som
  AdSense injekterar. Inte vår CSS, kan inte fixas härifrån.
- **2026-05-19** — Punkt **E klar** (commit `7cfa051`):
  `pages_inner_output_current.php` har fått en sr-only h1-fallback
  ("SVT Text TV NNN") för alla sidor som inte träffar specifika
  headline-fall (startpage, 377, 101-105). Använder befintlig
  `.sr-only`-klass (styles.css rad 1302). Arkivvyn har egen h1-fråga,
  lämnas till senare. Verifierat live på /100.
- **2026-05-19** — Punkt **G klar** (commit `d5a37db`):
  cookieinställningar-länken har fått `href="#cookieinstallningar"` +
  `return false`. Verifierat live: SEO 92 → **100** på `/100`
  (crawlable-anchors 0 → 1). Andra URL:er sannolikt också +8 SEO
  eftersom samma footer renderas överallt.
- **2026-05-19** — Punkt **A delvis fixad** (commit `bf88bab`):
  `pages_current_page_top.php`-ad-blockets push() hoppar nu över när
  `<ins>` är dolt (`.ad--beforeMainText` utan `.breadcrumbs`-syskon).
  **MEN:** Lighthouse-felet kvarstår efter deploy (Best Practices
  fortsatt 96, `errors-in-console` fortsatt 0). Resterande fel kommer
  troligen från annat ad-block (`pages_inner_output_current.php`-
  `.ad--before-latest`) eller `enable_page_level_ads: true` i
  `footer.php`. **Släppt:** felet syns inte i normal användning,
  prioritet låg. Återuppta vid behov.
