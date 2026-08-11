# 13 – `access.log` dubbelloggar, ip-anonymiseringen är verkningslös

**Status:** aktiv — **läckan är stoppad 2026-08-10**, kvar är bara frågan om historiken (se "Kvar att besluta")
**Senast uppdaterad:** 2026-08-10

## Sammanfattning

Det finns **två `access_log`-direktiv mot samma fil**, båda på http-nivå:

```
/etc/nginx/nginx.conf:40          access_log /var/log/nginx/access.log;
/etc/nginx/conf.d/general.conf:33 access_log /var/log/nginx/access.log anonymized;
```

nginx skriver en rad per direktiv. Varje request loggas därför två gånger:
en gång i default-formatet `combined` — **med full, oanonymiserad ip** — och
en gång i `anonymized`-formatet.

Det innebär att **ip-anonymiseringen inte har någon effekt**. Någon har
medvetet byggt tre `map`-block i `general.conf` för att maska sista oktetten,
och den maskeringen kringgås tyst av raden i `nginx.conf`.

Upptäckt 2026-08-10 vid verifiering av `rt=`-fälten (todo #12, alternativ B).

## Bevis

Samma request, två rader, samma sekund:

```
94.234.68.93 - - [10/Aug/2026:09:08:26 +0200] "GET /api/page/37295100/view..." 200 110 "-" "Mozilla/5.0 (iPhone...)"
94.234.68.0  - - [10/Aug/2026:09:08:26 +0200] "GET /api/page/37295100/view..." 200 110 "-" "Mozilla/5.0 (iPhone...)" rt=0.004 urt=0.004 cache=MISS
```

Första raden har `.93` — den råa ip:n. Andra har `.0` — den maskerade.
Exakt hälften av raderna i loggen innehåller `rt=` (100 av 200 vid
stickprov), vilket bekräftar att det är konsekvent 1:1.

## Konsekvenser

1. **Personuppgifter.** Fullständiga besökar-ip:n lagras i `access.log`
   trots att sajten uppenbart är byggd för att inte göra det. Loggen roteras
   men sparas i flera generationer. Detta är den allvarliga delen.
2. **Dubbel loggvolym.** `access.log` är 1,3 GB och hela `/var/log/nginx`
   3,3 GB. Ungefär hälften är ren dubblett.
3. **Felräkning.** Varje trafikmätning som greppar loggen utan att filtrera
   ger dubbla siffror. Volymuppgiften i `server.md` (~130 000 requests på
   20 min) var av just den anledningen dubbelt så hög som verkligheten.

## Åtgärd — utförd 2026-08-10

Raden i `/etc/nginx/nginx.conf:40` är utkommenterad (med en förklarande
kommentar kvar i filen, eftersom `/etc/nginx/` inte ligger under
versionshantering). `general.conf` sätter både format och destination och
laddas in i samma http-block via `include /etc/nginx/conf.d/*.conf`, så
loggningen är oförändrad i övrigt.

- Backup: `/etc/nginx/nginx.conf.bak-2026-08-10`
- `nginx -t` ok före reload, graceful `systemctl reload nginx`, ingen nertid
- Rättigheter oförändrade (`-rw-r--r-- root root`)

### Verifiering

| Kontroll                                    | Resultat        |
| ------------------------------------------- | --------------- |
| Rader med `rt=` av 300 kollade              | **300**         |
| Rader utan `rt=` (dvs. dubbletter)          | **0**           |
| Ip:n som slutar på `.0` (anonymiserade)     | **300**         |
| Ej anonymiserade ip:n                       | **0**           |
| `/100`, `/`, `/api/get/100`, `api.texttv.nu`| alla 200        |

**IPv6 är inte verifierat** — det finns ingen AAAA-post för `texttv.nu`
(sajten är IPv4-only), så ingen IPv6-trafik når servern och `map`-blockets
IPv6-gren är vilande. Den behöver kontrolleras om IPv6 någon gång slås på.

## Kvar att besluta: historiken

De roterade loggarna innehåller fortfarande råa ip:n bakåt i tiden. Men
`/etc/logrotate.d/nginx` har `daily` + `rotate 14`, så:

- Äldsta filen är `access.log.14.gz` (2026-07-28).
- **All logg som skrevs före fixen är utrotererad senast 2026-08-24** — det
  sker automatiskt, utan åtgärd.

Alternativen är alltså att låta dem åldras ut (klart 2026-08-24) eller att
rensa aktivt nu. Ingen åtgärd är vidtagen — detta är ett beslut för Pär.

Volym idag: `access.log.1` 1,4 GB okomprimerad, `.2`–`.14` ~55-70 MB/st
komprimerade. Efter fixen bör den dagliga volymen ungefär halveras.

## Öppna frågor

- Hur länge har det pågått? `general.conf` är daterad 2025-06-05, så
  sannolikt sedan dess — men `nginx.conf`-raden är daterad 2025-03-12 och kan
  vara äldre än anonymiseringen. Troligen har anonymiseringen aldrig fungerat.
- Hör detta ihop med todo **#08** (säkerhetsgranskningen)? Det är samma
  kategori av fynd och kan vara värt att foga in där.

## Relaterat

- Todo [#12](12-php-fpm-poolen-slar-i-taket.md) — upptäckten gjordes under
  dess alternativ B.
- Todo [#08](08-sakerhetsgranskning-2026-08-01.md) — säkerhetsgranskningen.
- [`server.md`](../server.md) — avsnittet "Loggar".
