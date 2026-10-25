**Status:** aktiv
**Senast uppdaterad:** 2026-10-25

# Fler kurerade titlar och descriptions, efter vinsten i #01

## Bakgrund

GSC 2026-10-25, 28 dagar (26/9–23/10) mot samma dagar 2025, arkivsidor
bortfiltrerade. Sajten totalt **+6 %**, medan kontrollen `/377` låg på −1 %.
Ökningen delad per ändring:

| Ändring | Sidor | 2026 | 2025 | YoY |
| --- | --- | --- | --- | --- |
| #01, 2026-05-19, titel och description | 19 | 846 | 451 | **+88 %** |
| #04 Fas 1, 2026-06-22, titel och description | 18 | 105 | 63 | +67 % |
| #09/#11, 2026-08-02, H1 och ämnesord i titeln (330, 376, 360, 551, 571) | 5 | 198 | 254 | −22 % |
| 378, 379 (#04 och H1 i aug) | 2 | 123 | 210 | −41 % |
| Kontroll `/377` | 1 | 9 876 | 9 972 | −1 % |
| Kontroll `/` | 1 | 3 369 | 3 127 | +8 % |
| Kontroll `/100` | 1 | 444 | 510 | −13 % |

Vinsten i #01 kommer främst från /300 (+142), /358 (+117), /345 (+50),
/365 (+43) och /343 (+28). Titlar som säger vad sidan innehåller fungerar.
H1-ändringarna gav inget som syns i klicken, och målservicesidorna tappar
av de skäl som står i #09 (SVT och AI-översikten tar platserna).

## Spår A: sidor som bara har blockfallbacken

De här sidorna har ingen kurerad titel och får fallbacken från #04 Fas 2,
till exempel `338 - SVT Text TV - Sport` med en generell sport-description.
Siffror för 90 dagar (2026-07-26 → 10-23). Innehållet hämtades från
API:t 2026-10-25.

| Sida | Innehåll 2026-10-25 | Visningar | Klick | CTR | Pos |
| --- | --- | --- | --- | --- | --- |
| 360 | Ishockey, Hockeyallsvenskan | 10 743 | 86 | 0,80 % | 4,0 |
| 350 | Fotboll, Elitettan damer | 1 407 | 13 | 0,92 % | 8,1 |
| 338 | Fotboll, Serie A | 1 247 | 4 | 0,32 % | 5,7 |
| 333 | Fotboll, Champions League | 1 206 | 6 | 0,50 % | 8,3 |
| 733 | Golf, världsranking herrar | 1 126 | 9 | 0,80 % | 3,0 |
| 303 | Sportnyhet (växlar) | 1 095 | 8 | 0,73 % | 8,1 |
| 354 | Handbollsligan herrar | 988 | 6 | 0,61 % | 5,5 |
| 745 | Motor, Rally-VM | 930 | 6 | 0,65 % | 4,3 |
| 362 | Ishockey, CHL | 914 | 3 | 0,33 % | 8,5 |
| 361 | Ishockey, SDHL | 896 | 4 | 0,45 % | 10,2 |
| 342 | Fotboll, danska ligan | 727 | 4 | 0,55 % | 5,4 |
| 340 | Fotboll, franska ligan | 668 | 4 | 0,60 % | 6,5 |
| 341 | Fotboll, turkiska ligan | 558 | 8 | 1,43 % | 6,0 |
| 356 | Handboll, Allsvenskan herrar | 415 | 4 | 0,96 % | 5,5 |

Sammanlagt ungefär 23 000 visningar och 165 klick per 90 dagar. Om CTR går
från 0,7 % till 1,0 % ger det **bara ungefär +20–25 klick i månaden**. Det
är en liten vinst, men insatsen är också liten (rader i `header.php`).

**Risker:**

- Sidorna byter innehåll med säsongen, till exempel 333 Champions League
  och 745 Rally-VM. Kontrollera i arkivet vad varje sida har visat det
  senaste året innan titeln skrivs. Ta bara med sidor vars innehåll är
  stabilt, och skriv annars en bredare titel (`Fotboll, Europa`).
- 303 och andra nyhetssidor växlar ämne varje dag och ska inte få en
  ämnestitel.
- Målservicesidorna 382–388 byter liga per dag (384 Allsvenskan, 385/386
  Damallsvenskan, 387 Superettan, 388 Premier League den 25/10). De ska inte
  med.

**Undersök separat:** 190 (3 723 visningar, 0,13 %, pos 3,7) är inte i
sändning och får titeln `Utrikes`, vilket troligen är fel. Samma gäller 150,
199 och 112. Ta reda på vad 190 visar när den sänds (arkivet), innan den
får en titel.

## Spår B: andra varvet på stora sidor som redan är kurerade

Här finns mer att hämta än i spår A, eftersom volymen är 5–10 gånger
större:

| Sida | Visningar 90d | Klick | CTR | Pos | Kurerad i |
| --- | --- | --- | --- | --- | --- |
| 100 | 151 177 | 1 314 | 0,87 % | 5,0 | tidigare |
| 300 | 120 503 | 726 | 0,60 % | 4,1 | #01 |
| 343 | 94 524 | 602 | 0,64 % | 3,1 | #01 |
| 101 | 10 693 | 25 | 0,23 % | 3,7 | #01 |
| 344 | 8 009 | 27 | 0,34 % | 4,4 | #01 |
| 106 | 4 124 | 14 | 0,34 % | 4,6 | #01 |

Om /300 och /343 når 1 % CTR blir det ungefär +850 klick per 90 dagar,
mer än hela spår A. Gör så här:

1. Hämta sökfrågorna per sida (`get_search_by_page_query`, 90d) och se
   vilken avsikt som dominerar. För /343 är det troligen `allsvenskan tabell`,
   som enligt #10 rankade på position 51.
2. Jämför titel och description med de frågorna och SERP:en (google.se).
3. Ändra en sida åt gången, eller som en kohort med fast mätdatum. Mät
   YoY med `/377` som kontroll (se memory om GSC-mätning).

/101 har låg CTR trots position 3,7. Kontrollera om den rankar på frågor
där SVT:s egen sida vinner, eftersom det kan förklara siffrorna.

## Plan

1. Spår B först, /300 och /343 (störst effekt).
2. Spår A i en kohort, efter att arkivet visat vilka sidor som har stabilt
   innehåll.
3. Mät 30d och 60d efter deploy, YoY mot samma fönster 2025 med `/377`
   och `/` som kontroll.
