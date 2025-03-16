# TextTV.nu Teknisk Dokumentation

Detta dokument beskriver den tekniska arkitekturen och komponenterna i TextTV.nu, ett modernt webbgränssnitt för SVT:s Text TV-tjänst.

## Projektöversikt

TextTV.nu är byggd med en hybridarkitektur som består av två huvudkomponenter:

1. Frontend-applikation (CodeIgniter)
2. Dataimporttjänst (Laravel)

## Arkitekturkomponenter

### Frontend-applikation (`texttv.nu/`)

Frontend-delen är byggd med PHP-ramverket CodeIgniter och fungerar som det huvudsakliga webbgränssnittet som användarna interagerar med. Den innehåller:

- **Webbgränssnitt**: Visar Text TV-sidor i ett modernt, mobilanpassat format
- **Progressive Web App (PWA)**-funktioner:
  - Service worker för offline-funktionalitet
  - Manifest för installationsbar webbapp
  - Mobiloptimerat gränssnitt
- **Resursstruktur**:
  - `css/` - Stilmallar
  - `js/` - JavaScript-filer
  - `images/` - Bildresurser
  - `codeigniter/` - Kärnapplikationsfiler
  - `fonts/` - Typsnitt

### Dataimporttjänst (`importer/`)

En Laravel-baserad applikation som hanterar dataimport och databehandling. Denna tjänst:

- Hämtar Text TV-data från SVT:s tjänster
- Bearbetar och lagrar data
- Hanterar historisk data och arkiv
- Utför städning för att upprätthålla databasernas effektivitet

Huvudkomponenter:

- Databasmigrationer och modeller
- Konsolkommandon för dataimport och underhåll
- API-endpoints för interna tjänster
- Schemalagda uppgifter för automatiserad datauppdatering

## Utvecklingsmiljö

Projektet använder Laravel Valet för lokal utveckling. För att sätta upp utvecklingsmiljön:

1. Installera PHP 8.x och nödvändiga tillägg
2. Installera Composer för PHP-pakethantering
3. Installera Node.js och npm för frontend-resurshantering
4. Konfigurera Laravel Valet
5. Ställ in lokala miljövariabler med `.env`-filer

## Databasstruktur

Applikationen använder flera databaser:

- Huvuddatabas för innehållsvisning
- Statistikdatabas för användningsanalys
- Arkivdatabas för historiskt innehåll

## Driftsättning

Projektet innehåller driftsättningsskript (`server_deploy.sh`) för automatiserad driftsättning.

## Nyckelteknologier

- **Frontend**:

  - CodeIgniter PHP-ramverk
  - JavaScript
  - Progressive Web App-teknologier
  - Responsiv CSS

- **Backend**:

  - Laravel 8+
  - MySQL
  - PHP 8.x
  - Composer för beroendehantering

- **Utvecklingsverktyg**:
  - Laravel Valet
  - npm för frontend-resurshantering
  - Git för versionshantering

## API-dokumentation

API-dokumentation finns i Bruno API-filkatalogen, som innehåller API-specifikationer och exempel.

## Övervakning och underhåll

Regelbundna underhållsuppgifter inkluderar:

- Databasrensning (se `CleanupPageActions`-kommandot)
- Loggrotering
- Prestandaövervakning
- Säkerhetsuppdateringar för beroenden
