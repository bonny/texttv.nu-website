**Status:** aktiv — Article JSON-LD implementerad 2026-05-19, väntar på deploy + Rich Results-validering
**Senast uppdaterad:** 2026-05-19

# Todo #05 — Utvärdera externa SEO-skills

## Sammanfattning

Tre externa skills granskade 2026-05-19. Plocka in det som ger faktiskt
mervärde utöver det #04 redan gjort, och skippa det som är dubbelarbete
eller fel målgrupp.

## Bakgrund

Användaren frågade om dessa tre skills skulle förbättra SEO-arbetet:

- https://www.skills.sh/addyosmani/web-quality-skills/seo
- https://www.skills.sh/coreyhaines31/marketingskills/seo-audit
- https://www.skills.sh/coreyhaines31/marketingskills/ai-seo

Granskning gjord — sammanfattning per skill nedan.

## Förslag

### 1. JSON-LD structured data (från addyosmani) — **klart 2026-05-19**

Granskning av befintlig JSON-LD i `header.php`:
- ✓ `WebSite`-schema globalt (rad 601-609)
- ✓ `NewsArticle` på **arkivsidor** (rad 561-594)
- ✓ `BreadcrumbList` via inline Microdata i `breadcrumbs.php`
- ✗ **Article saknades på live (non-archive) sidor** — där huvudtrafiken är

Implementerat: nytt `Article` JSON-LD-block i `header.php` för
single-page live-vyer (`!$is_archive && sizeof($pages) == 1 && !$apiAppShare`).
Använder `json_encode()` med `JSON_UNESCAPED_UNICODE` för säker escaping
av svenska tecken + emoji — befintliga arkiv-blocket escapar inte alls
(läs: risigt, men låter det vara nu).

Output verifierat lokalt med dummy-data → valid JSON, ⚽️/å/ä/ö
bibehållna, datum i ISO 8601.

**TODO efter deploy:**
1. Verifiera `curl https://texttv.nu/343 | grep -A 30 '"@type": "Article"'`
2. Validera med Google Rich Results Test:
   https://search.google.com/test/rich-results?url=https%3A%2F%2Ftexttv.nu%2F343
3. Kolla att inga app-anrop bryts (`apiAppShare` skippar Article — så app:en
   ska vara opåverkad)

### 2. coreyhaines31/seo-audit — **efter 2026-06-18**

Bra ramverk för **nästa fas** (content + authority signals + keyword
targeting per sidtyp). Matchar #04 D Fas 1 (datadriven whitelist via
mcp-gsc). Skillet täcker site-type-specifika råd för "content sites"
vilket texttv.nu är.

Använd som strukturerad genomgång när #01-mätningen är klar och vi
har GSC-data att gå på.

### 3. coreyhaines31/ai-seo — **skippa**

LLM-visibility (ChatGPT/Perplexity/Claude/Gemini-citationer). Fel
målgrupp: användare som söker "text tv 343" går till Google eller
app:en, inte till ChatGPT. Google AI Overviews skulle dessutom kunna
*minska* CTR (svaret visas i SERP utan klick) — netto negativt för
AdSense-intäkter.

Ompröva om/när AI-trafik blir mätbar källa i GA4.

## Risker

- JSON-LD: felaktig schema-typ kan skada SEO snarare än hjälpa. Validera
  med Google Rich Results Test innan deploy.
- Att vänta på #2 till efter 2026-06-18 är medvetet — vi vill inte blanda
  mätsignal med #01-experiment.

## Confidence

medel — Skill-bedömningar är subjektiva. JSON-LD-värdet (#1) är väl
underbyggt; #2/#3-prioritering kan ändras om GSC-datan visar något
oväntat 2026-06-18.
