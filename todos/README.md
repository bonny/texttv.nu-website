# Todos – konvention

Ett index över förbättringsarbete ligger i [`/todo.md`](../todo.md). Den
här mappen innehåller en fil per todo med fullständig analys, risker,
fördelar, öppna frågor och föreslagen plan.

## Mappstruktur

- `todos/` — aktiva todos
- `todos/done/` — klara todos (datum i filens header)
- `todos/rejected/` — avfärdade eller sammanslagna

Filer flyttas mellan mapparna när status ändras. Inget raderas — historiken
behålls för spårbarhet och eftersökning.

## Filhuvud

Varje todo-fil börjar med:

```markdown
**Status:** <statusord> — <kort läge, utan framtida datum>
**Senast uppdaterad:** YYYY-MM-DD
**Blockerad av:** #N (om relevant)
```

Nummer matchar filnamn (`NN-kort-slug.md`) och raden i `todo.md`.

## Statusord

Status börjar alltid med ett av de här orden, både i filhuvudet och i
Status-kolumnen i `todo.md`. Filhuvudet och indexet har samma ord.

| Ord                   | Betyder                                                                  |
| --------------------- | ------------------------------------------------------------------------ |
| `aktiv`               | Något går att göra nu, även om en mätning också pågår                    |
| `väntar`              | Inget går att göra förrän en uppföljning; kräver en rad i Uppföljningar  |
| `blockerad`           | Väntar på en annan todo eller en person, utan datum. Skriv vad i texten  |
| `pausad`              | Medvetet nedprioriterad, eller ny och otriagerad (`pausad — otriagerad`) |
| `klar YYYY-MM-DD`     | Bara i `done/`                                                           |
| `avfärdad YYYY-MM-DD` | Bara i `rejected/`                                                       |

Det som bara Pär kan göra (konsolklick, nycklar, beslut) skrivs `Pär: <vad>`
i statusen eller i uppföljningen. `todos.py par` listar dem.

Orden är samma i alla projekt i `~/Projects`, så att den samlade översikten
(`~/Projects/.claude/skills/todos/`) kan filtrera på dem.

## Status och datum

- Statusen är ett kort läge, högst 250 tecken: vad som är gjort och vad som
  återstår. Historik och mätvärden hör hemma längre ner i todo-filen.
- **Framtida datum står bara i Uppföljningar**, inte i statusen eller
  filhuvudet. Översikten visar nästa uppföljning per todo därifrån.
- Uppföljningar-tabellen hålls sorterad på datum, äldst först. Datum skrivs
  `YYYY-MM-DD`; `YYYY-MM` går för grova deadlines (räknas som sista dagen i
  månaden), `(snarast)` för det som redan borde vara gjort.
- Återkommande uppföljningar slutar med `Återkommer: månadsvis.` (eller
  `veckovis`, `var 14:e dag`). När raden bockas av läggs nästa rad in.
- Bocka av och skjut upp med skriptet, det sorterar och kör prettier:

    ```bash
    python3 ~/Projects/.claude/skills/todos/todos.py bocka <proj> <nr> <datum> --resultat "<vad den visade>"
    python3 ~/Projects/.claude/skills/todos/todos.py flytta <proj> <nr> <datum> <nytt datum>
    ```

- Raden `Senast uppdaterad:` i `todo.md` beskriver bara den senaste ändringen
  och ersätts varje gång. Historiken finns i git.
- Kontrollera med `python3 ~/Projects/.claude/skills/todos/todos.py kontroll`.

## Synka index

När en todo ändrar status:

1. Uppdatera fil-headern (`Status:` + `Senast uppdaterad:`).
2. Flytta filen till rätt mapp (`git mv`).
3. Uppdatera `/todo.md` så raden hamnar i rätt tabell.
