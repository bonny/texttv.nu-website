**Status:** aktiv
**Senast uppdaterad:** 2026-08-02

# Arkivsidor rankar över de levande sidorna

Utrett 2026-08-02. Utgångspunkten var hypotesen från
[#10](10-retention-raderar-arkivsidor-som-rankar.md) att arkivsidor rankar bättre
för att de har *stabil text* medan de levande byts ut var annan minut.

**Hypotesen var fel.** Orsaken är konkret och enkel: `<title>`.

## Fyndet

`resultatbörsen`, 90 dagar: levande `/330` får **0 klick**. Dess egna
arkivsnapshots får 25.

| | `<title>` som serveras |
| --- | --- |
| Levande `/330` | `330 - SVT Text TV` — **inget ämnesord** |
| `/330/resultatborsen-13586101` (2017) | `Resultatbörsen - SVT Text TV` |
| `/330/resultatborsen-11357432` (2016) | `Resultatbörsen` |

Nyckelordet finns i brödtexten på båda — `* * * RESULTATBÖRSEN * * *` står
högst upp på den levande sidan också. Det är alltså inte innehållet som
skiljer, utan titeln.

## SERP-kontroll (google.se, hl=sv, gl=se, 2026-08-02)

Sökning på `resultatbörsen`:

| # | Resultat |
| --- | --- |
| 1 | svt.se/sport/malservice/ |
| 2 | flashscore.se |
| 3 | svt.se/text-tv/330 |
| **4** | **texttv.nu/330/resultatborsen-13586101** (2017) |
| 5 | malservice.aftonbladet.se |
| 6 | svenskfotboll.se/livescore |
| 7 | svt.se/text-tv/300 |
| 8 | resultatservice.com |
| **9** | **texttv.nu/330/resultatborsen-11357432** (2016) |
| 10 | txtv.nu — `330 - Resultatbörsen - TxTV` |

**Levande `/330` finns inte på sida 1.** Två av dess egna arkivsnapshots gör
det. Och konkurrenten `txtv.nu` tar plats 10 med titeln
`330 - Resultatbörsen - TxTV` — nummer *och* ämne.

**Google skrev inte om arkivtitlarna.** De visas ordagrant som vi serverar dem.
Det är skillnaden mot `/377`, där Google skriver om titeln (se
[#09](09-varfor-tappade-377-position-och-vad-vi-gor-at-det.md)) och en
titeländring därför är verkningslös. **Här biter den.**

## Mekanismen

`views/header.php:67-70`:

```php
if (isset($is_archive)) {
	// inget sidnummer först på arkiverade sidor
} else {
	$page_title .= $pagenum . " ";
}
```

Därefter (rad 73–86) läggs sidans egen rubrik på — `get_page_name()` eller
sidans extraherade `title`. Och på rad 169–171:

```php
$meta_title = $page_title;
$generate_meta_title = true;
if (!isset($is_archive)) {
	// ... whitelist skriver över $meta_title
```

Alltså tre olika utfall:

| Sidtyp | Titel |
| --- | --- |
| Arkiv | sidans egen rubrik → `Resultatbörsen - SVT Text TV` |
| Levande, ej i whitelist | `{nummer} {rubrik}` → `330 Resultatbörsen - SVT Text TV` |
| Levande, i whitelist | hårdkodad sträng → `330 - SVT Text TV` |

## Slutsatsen som svider

**Whitelist-posten för 330 är sämre än ingen post alls.** Utan den hade
automatiken gett `330 Resultatbörsen - SVT Text TV` — i praktiken samma form som
konkurrenten txtv.nu rankar med. Den kurerade titeln *tog bort* ämnesordet.

Det gäller de tre legacy-posterna i `header.php:181-189`, som alla skrevs innan
`"NNN - SVT Text TV - <ämne>"`-konventionen infördes i #04 Fas 1:

```php
$meta_title = "376 - SVT Text TV";
$meta_title = "377 - SVT Text TV";
$meta_title = "330 - SVT Text TV";
```

Jämför med Fas 1-posterna: `"343 - SVT Text TV - Allsvenskan tabell"`,
`"336 - SVT Text TV - Premier League"`, `"358 - SVT Text TV - SHL tabell"`.

Detta **nyanserar #09**, som avfärdade titeländringar för 377. Den slutsatsen
står kvar *för 377* — Google skriver om den titeln. Men den generaliserades
felaktigt till 330 och 376. På sidor där Google inte skriver om är titeln en
verklig hävstång, och där är legacy-posterna aktivt skadliga.

## Åtgärd

Uppdatera de tre legacy-posterna till Fas 1-konventionen:

| Sida | Nu | Föreslaget |
| --- | --- | --- |
| 330 | `330 - SVT Text TV` | `330 - SVT Text TV - Resultatbörsen` |
| 376 | `376 - SVT Text TV` | `376 - SVT Text TV - Målserviceindex` |
| 377 | `377 - SVT Text TV` | `377 - SVT Text TV - Målservice` |

377 tas med för konsekvensens skull, inte för effekt — Google skriver ändå om
den. Ingen effekt bör budgeteras där.

Mät på `resultatbörsen` (26 klick, pos 6.9, allt till arkivet idag) och på om
levande `/330` börjar synas på sida 1.

## Sidoproblem: dubbla `<h1>` på nio sidor

Upptäckt under utredningen. Nio sidor har två `<h1>`: en från
`pages_inner_output_current.php` (visuell rubrik eller `sr-only`-fallback) och
en inbakad i `page_text`-texten från DB:n.

Berörda: 200, 202, 330, 336, 358, 360, 398, 551, 571 (samt
`fakta-text-tv-i-sverige`). Exempel på `/551`:

```html
<h1 class="sr-only">SVT Text TV 551</h1>
<h1>551 är text-tv-sidan för stryktips</h1>
```

**Detta är inte orsakat av H1-tabellen i `d7b585b`** — mönstret fanns före, och
`/330` hade två `<h1>` även innan (`sr-only` + `page_text`). Ändringen bytte
bara ut den ena mot en synlig.

HTML5 tillåter flera `<h1>` och Google hanterar det, så det är sannolikt
ofarligt för ranking. Men eftersom Google *väljer* rubrik att skriva titlar
från är det ostädat att ge den två konkurrerande kandidater. Låg prioritet;
enklast är att sänka `page_text`-rubrikerna till `<h2>` i DB:n (samma grepp som
377-texten redan använder).

## Risker

Låg. Titeländringar i whitelisten är samma sorts ändring som #04 Fas 1, som är
mätt och bekräftad. Reversibel med en commit.

Motargument värt att notera: arkivsidorna rankar *redan* och ger 25 klick. Om
levande `/330` börjar ranka i stället kan de klicken flyttas snarare än
adderas — nettot kan bli noll. Fördelen är då i stället att besökaren möter
aktuell data i stället för en nio år gammal ögonblicksbild, vilket är ett
produktargument snarare än ett trafikargument.

## Confidence

Hög på diagnosen (titelskillnaden är verifierad i serverad HTML, i koden och i
SERP:en). Medel på effekten — `/330` kan ha andra skäl att inte ranka som vi
inte ser.
