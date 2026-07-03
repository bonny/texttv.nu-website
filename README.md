# texttv.nu – webbplats för SVT Text

Koden för webbplatsen för SVT Text TV-sajten https://texttv.nu finns här.

Vi har även appar för [Iphone](https://apps.apple.com/se/app/texttv-nu-svt-text-tv/id607998045) och [Android](https://play.google.com/store/apps/details?id=com.mufflify.TextTVnu&hl=sv).

![texttv-nu-skärmdump](https://user-images.githubusercontent.com/221570/117265957-0e483b00-ae55-11eb-9ad6-92c21b15732b.png)

## Text-tv – för att 2 miljoner svenskar kan inte ha fel

Trots att text-tv har många år på nacken så används den fortfarande flitigt. Enligt SVT så använder fler än 2 miljoner personer SVT Text varje dag.

## Vårt mål: lite bättre Text TV

Vi är kanske lite galna, men vi tror att det går att göra klassiska text-tv ännu lite bättre. 

### Vad gör TextTV.nu till en bättre Text TV-tjänst

Några av de saker som utmärker texttv.nu är:

- Bättre anpassad för smartphones som iPhone och diverse Android
- Stöd för färg och grafik på alla sidor
  - Härligt gröna och gula färger på [sportsidan 377](https://texttv.nu/377)
  - Fungerande grafik på [väderprognosen på sidan 401](https://texttv.nu/401)
- Stöd för permalänkar, så det går att dela en en nyhet på Twitter och Facebook - utan att nyheten "skrivs över" nästa dag
- Möjlighet att visa flera sidor samtidigt. T.ex. såhär:
  - [Inrikesnyheter, sid 101 till 103](https://texttv.nu/101-103)
  - [Startsida 100 och sportnyheterna på sidan 300](https://texttv.nu/100,300)
  - [På TV Svt 1 & Svt 2, dvs. sidorna 601-604](https://texttv.nu/601-604)
- Hjälp oss göra TextTV.nu ännu bättre!
- Har du förslag på hur man kan göra texttv.nu ännu bättre?

Tipsa oss på kontakt@texttv.nu eller på Twitter @texttv_nu. Vi har också en egen sida på Facebook.

### Lokal utveckling

Enklaste sättet är **Docker** – `make up` så snurrar hela sajten lokalt. Kräver bara [Docker Desktop](https://www.docker.com/products/docker-desktop/).

#### Kom igång (första gången)

```bash
make up
```

Det bygger allt och startar fyra saker: webben (CodeIgniter), importern (Laravel), databasen (MariaDB) och ett engångsjobb som hämtar några startsidor direkt från SVT. Efter ~10 sekunder:

- **Sajten:** http://localhost:8380/100
- **Startsidan:** http://localhost:8380/
- **Sport med färg/grafik:** http://localhost:8380/377

#### Varje dag

När datorn startats (och Docker Desktop är igång) räcker det med:

```bash
make up
```

Bygger inte om något – startar bara. Redigerar du PHP-kod syns ändringarna direkt, ingen omstart behövs.

#### Vanliga kommandon

| Kommando | Gör |
| --- | --- |
| `make up` | Starta allt |
| `make down` | Stoppa (behåller databasen) |
| `make logs` | Se vad som händer |
| `make import RANGE=110-115` | Hämta fler sidor från SVT |
| `make mysql` | Öppna databasen |
| `make scheduler` | Starta auto-import från SVT (av som standard) |
| `make destroy` | Nollställ allt inkl. databasen |

`make help` listar alla kommandon. Detaljer och fallgropar finns i [`CLAUDE.md`](CLAUDE.md).

#### Alternativ: Laravel Valet

Går även att köra utan Docker via Valet: starta valet och gå till http://texttv.nu.test/

