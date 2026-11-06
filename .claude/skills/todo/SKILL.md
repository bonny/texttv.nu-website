---
name: todo
description: "Hantera projektets todos i `todo.md` + `todos/`. Använd när användaren frågar/ber om todos — t.ex. 'todos', 'översikt', 'vad är nästa todo', 'vilka todos har vi', 'vad väntar', 'visa todo #N', 'markera #N klar', 'avfärda #N', 'skapa todo …', 'uppföljningen för #N är gjord', 'skjut upp #N'. Subkommandon: (tomt)=översikt, list, next, show <nr>, done <nr>, reject <nr>, new <titel>, bocka <nr> <datum>, flytta <nr> <datum> <nytt>."
---

# /todo — todo-hantering

<!-- Samma kärna i brottsplatskartan-web, brottsplatskartan-app, texttv.nu-website och texttv-nu-smartare.
     Ändra i alla fyra samtidigt; bara avsnittet "Projektspecifikt" längst ner skiljer sig. -->

Todos finns på två platser och måste hållas synkade:

- `todo.md` (repo-roten) — index med tabellerna: **Aktiva**, **Uppföljningar**
  (datum-bundna manuella check-ins), **Klara**, **Avfärdade / sammanslagna**.
  Raden `Senast uppdaterad: YYYY-MM-DD (<senaste ändringen>)` ersätts vid varje ändring.
- `todos/<NN>-<slug>.md` — en fil per todo med full analys.
    - `todos/done/` — klara
    - `todos/rejected/` — avfärdade / sammanslagna

Konventionsdokument: `todos/README.md` (statusord, datumregler).

## Statusord och datum

Reglerna står i `todos/README.md`; kort:

- Status börjar med `aktiv`, `väntar`, `blockerad` eller `pausad`, med samma ord i
  filhuvudet (`**Status:** väntar — …`) och i Status-kolumnen i `todo.md`.
- `väntar` bara när inget går att göra före nästa uppföljning. Finns något kvar
  att göra är todon `aktiv`, även om en mätning pågår.
- Det bara Pär kan göra skrivs `Pär: <vad>`.
- Statusen är högst 250 tecken och har inga framtida datum. Datum står bara i
  **Uppföljningar** (`| YYYY-MM-DD | <åtgärd> | [#NN](...) |`, sorterad äldst först).
- Klassificera alltid på statusordet, gissa inte ur fritexten. Ändras läget:
  byt ordet både i filhuvudet och i indexet.

**Byt status med `todos.py satt <id> <nr> "<status>"`**: den skriver både filhuvudet och raden i
`todo.md`, så att de inte glider isär (kontrollen flaggar det annars).

## Skriptet

`../../.claude/skills/todos/todos.py` (i `~/Projects`; finns den inte på datorn,
läs `todo.md` direkt och gör ändringarna för hand) läser alla backloggar och
skriver uppföljningar. `<id>` står under Projektspecifikt.

```bash
S=../../.claude/skills/todos/todos.py
python3 $S oversikt --projekt <id>       # dashboard
python3 $S alla --projekt <id>           # alla aktiva med nästa uppföljning
python3 $S bocka <id> <nr> <datum> --resultat "<vad den visade>"
python3 $S flytta <id> <nr> <datum> <nytt datum>
python3 $S kontroll --projekt <id>       # efter varje ändring
```

Kör `kontroll` efter varje ändring av `todo.md` eller ett filhuvud och rätta det
den hittar (förfallna uppföljningar tar du upp med användaren).

## Tolka argumentet

| Argument                     | Subkommando              |
| ---------------------------- | ------------------------ |
| (tomt)                       | Översikt / dashboard     |
| `list`                       | Lista aktiva             |
| `next`                       | Rekommendera nästa       |
| `show <nr>`                  | Visa todo-fil            |
| `done <nr>`                  | Markera klar             |
| `reject <nr>`                | Avfärda                  |
| `new <titel>`                | Skapa ny todo            |
| `bocka <nr> <datum>`         | Uppföljning gjord        |
| `flytta <nr> <datum> <nytt>` | Skjut upp en uppföljning |

Numret matchas mot filnamnets prefix (`<NN>-...`).

## (tomt) — översikt

Kompakt dashboard. Inga todo-filer öppnas.

1. Kör `todos.py oversikt --projekt <id>` (eller läs `todo.md`).
2. **Räknarrad** överst: `aktiv: X · väntar: Y · blockerad: Z · pausad: W`.
3. **▶ Aktionerbara nu** — de med `aktiv` (`<prefix>-NN Titel — vad som går att göra`).
   Märk de med `Pär:`. Om inga: "(inga — <kort skäl>)".
4. **⏳ Väntande** — `väntar`, sorterat på närmaste uppföljning (skriptet visar
   "nästa YYYY-MM-DD"); sedan `blockerad` med vad de väntar på; `pausad` bara som antal.
5. **📅 Nästa uppföljning** — närmaste rad i Uppföljningar, med "(om N d)".
   Flagga förfallna (datum < `currentDate`) tydligt med ⚠ — då är en check-in glömd.
6. **💡 Nästa** — en mening: den första aktionerbara (jfr `next`), eller
   "inget aktionerbart nu — <närmaste uppföljning>" om alla väntar.

## list

1. Kör `todos.py alla --projekt <id>` (eller läs `todo.md`).
2. Gruppera på statusordet: **Aktionerbara nu** (`aktiv`), **Väntar** (sorterat
   på närmaste uppföljning), **Blockerade**, **Pausade**. Hoppa över tomma grupper.
   Format: `#NN — Titel — Status`.
3. Om "Beroenden" är icke-tom, nämn de relevanta i en mening efter listan.
4. Nämn uppföljningar inom 14 dagar och flagga förfallna med ⚠.

## next

1. Kör `todos.py oversikt --projekt <id>` och läs sektionen **Föreslagen ordning**
   i `todo.md` (`grep -A15 '### Föreslagen ordning' todo.md`).
2. Förfallen uppföljning finns: nämn den först — det är sannolikt vad
   användaren bör göra i stället för en aktiv todo.
3. Ta första `aktiv` i Föreslagen ordning (annars i Aktiva). Motivera kort
   (2–3 meningar): vad det är, varför den är logisk nu, eventuell synergi med
   nyligen klar todo.
4. Inga `aktiv`: säg det och ge närmaste uppföljning per väntande todo.

## bocka <nr> <datum>

Användaren har gjort en uppföljning (eller ber dig göra den och den är klar).

1. `todos.py bocka <id> <nr> <datum> --resultat "<vad den visade, en eller två meningar>"`.
   Datum = datumcellen som den står. Matchar flera rader: lägg till `--text <del av åtgärden>`.
   Rader utan todo-nummer (t.ex. återkommande underhåll) väljs med `<nr>` = `-`.
2. Skriptet flyttar raden till **Avklarade uppföljningar**, lägger in nästa rad om
   den är återkommande, sorterar och kör prettier.
3. Uppdatera todon: nytt läge i statusen (filhuvud + index). Har todon inga
   kommande uppföljningar kvar och var `väntar`: byt till `aktiv`, eller `done`
   om uppföljningen var det sista.
4. Var det en prod-åtgärd: en rad i serverloggen (`Logg - <domän>.md` i Obsidian,
   se `~/Projects/CLAUDE.md`) samma dag.
5. `todos.py kontroll` och `git status`.

Innan du gör en uppföljning vars todo redan är klar (`(klar)` i skriptets utdata):
grep serverloggen och git-loggen efter det åtgärden nämner. Den kan redan vara
gjord och bara inte avbockad.

## flytta <nr> <datum> <nytt>

`todos.py flytta <id> <nr> <datum> <nytt>` och sedan `kontroll`. Skriv varför i
åtgärdstexten om skälet inte är självklart (t.ex. "flyttad, mätperioden förlängd").

## show <nr>

1. Hitta filen: `ls todos/<NN>-*.md todos/done/<NN>-*.md todos/rejected/<NN>-*.md 2>/dev/null`.
2. Read den fil som matchar.
3. Visa innehållet. För långa filer (>200 rader): summera Sammanfattning/Status/Plan, men visa hela bara om användaren ber.

## done <nr>

**Kör `todos.py klar <id> <nr> "<vad som gjordes>"`.** Skriptet gör steg 2–4 nedan (filhuvud, `git mv`,
rättade länkar i filen och i repot, raden till Klara, uppföljningar som pekar på filen) och säger till
om Beroenden eller Föreslagen ordning fortfarande nämner todon. Det som återstår för hand är de två
avsnitten. Stegen nedan beskriver vad som ska hända, om skriptet inte finns på datorn.

Alla steg krävs — det här är hela poängen med skillen.

1. **Hitta filen:** `todos/<NN>-*.md` (måste ligga i aktiv-mappen — om den redan är i `done/` eller `rejected/`, stoppa och säg det).
2. **Uppdatera filhuvudet** (rad 1–2 i todo-filen):
    ```markdown
    **Status:** klar YYYY-MM-DD — <kort sammanfattning vad som gjordes>
    **Senast uppdaterad:** YYYY-MM-DD
    ```
    Datum = `currentDate` från system-context. Sammanfattning = vad användaren just gjort, inte abstrakt.
3. **Flytta filen:** `git mv todos/<NN>-*.md todos/done/` (Bash). Om filen inte är trackad, fall back till `mv`.
   **Rätta sedan relativa länkar i den flyttade filen**, de pekar annars en nivå fel:
   `](NN-...` → `](../NN-...` och `](../server.md` m.fl. → `](../../...`. Kontrollera att
   varje mål finns. Grep också andra todos efter länkar till den gamla sökvägen.
4. **Uppdatera `todo.md`:**
    - Ta bort raden från **Aktiva**-tabellen.
    - Lägg till **högst upp** i **Klara**-tabellen (sorterad nyast först):
        ```
        | <NN>  | <Titel utan #-prefix>                              | YYYY-MM-DD | [todos/done/<fil>.md](todos/done/<fil>.md) |
        ```
    - Ersätt `Senast uppdaterad:`-raden: `Senast uppdaterad: YYYY-MM-DD (#<NN> klar — <kort hint>).`
    - Om todon stod i **Föreslagen ordning** — ta bort den raden och numrera om.
    - Om todon var listad i **Beroenden** som blockerare — uppdatera noten till `*(klar YYYY-MM-DD — beroendet löst, listas tills #M startat.)*`, och byt `blockerad` → `aktiv` på todos den blockerade (filhuvud + index).
    - Kvarvarande rader i Uppföljningar för todon får ligga kvar (de pekar på `done/`-filen) om uppföljningen fortfarande ska göras.
5. **Verifiering:** kör `todos.py kontroll` och `git status` så användaren ser ändrade filer. Commit:a inte utan att användaren ber.

## reject <nr>

Som `done` men:

1. **Filhuvud:** `**Status:** avfärdad YYYY-MM-DD — <skäl>`
2. **Flytta:** `git mv todos/<NN>-*.md todos/rejected/` (och rätta relativa länkar som i `done`).
3. **Index:** lägg till i tabellen **Avfärdade / sammanslagna**:
    ```
    | <NN>  | <Titel>      | Avfärdad YYYY-MM-DD — <kort skäl>      | [todos/rejected/<fil>.md](todos/rejected/<fil>.md) |
    ```
4. Ersätt "Senast uppdaterad"-raden.
5. Om todon var i Föreslagen ordning, blockerade andra eller har rader i Uppföljningar — uppdatera de sektionerna.

Be användaren om skälet om det inte gavs i argumenten ("Vad är skälet?").

## new <titel>

**Kör `todos.py ny <id> "<titel>" [--status "<status>"]`.** Skriptet gör steg 1–4 nedan (nummer,
slug, fil från mallen, rad i Aktiva). Ge `väntar` en uppföljning med
`todos.py uppfoljning <id> <nr> <datum> "<åtgärd>"` (sorteras in automatiskt).

1. **Hitta nästa nummer:** lista alla `todos/`-, `done/`- och `rejected/`-filer, plocka ut prefix-numret (`<NN>`), ta max + 1. Zero-pad till 2 siffror.
2. **Skapa slug** från titeln: lowercase, mellanslag→bindestreck, ta bort/translitera åäö (`å`/`ä`→`a`, `ö`→`o`), strip-a interpunktion. Max ~50 tecken.
3. **Skapa filen** `todos/<NN>-<slug>.md` med mall:

    ```markdown
    **Status:** pausad — otriagerad
    **Senast uppdaterad:** YYYY-MM-DD

    # Todo #<NN> — <Titel>

    ## Sammanfattning

    <fyll i — vad är problemet, vad föreslås>

    ## Bakgrund

    ## Förslag

    ## Risker

    ## Confidence

    <låg | medel | hög> — <motivering>
    ```

4. **Uppdatera `todo.md`:**
    - Lägg till en rad i **Aktiva**-tabellen:
        ```
        | <NN>  | <Titel>     | pausad — otriagerad  | [todos/<NN>-<slug>.md](todos/<NN>-<slug>.md) |
        ```
    - Ersätt raden: `Senast uppdaterad: YYYY-MM-DD (+#<NN> <titel>).`
5. Säg till användaren att filen skapades med en mall och fråga om de vill fylla i Sammanfattning/Bakgrund nu.
   När todon är triagerad: byt till `aktiv` (eller `väntar` + en rad i Uppföljningar).

## Konventioner att respektera

- **Datum** = `currentDate` från system-context (ISO `YYYY-MM-DD`). Aldrig hårdkoda.
- **Filnamn**: `<NN>-<slug>.md`, `<NN>` zero-padded två siffror.
- **Tabellsortering** i `todo.md`:
    - Aktiva: ingen särskild ordning.
    - Uppföljningar: datum, äldst först.
    - Klara: **nyast först** (datum desc) — nya rader läggs **överst**.
    - Avfärdade: ingen särskild ordning.
- **Aldrig radera** todo-filer — alltid flytta till `done/` eller `rejected/`. Historiken är poängen.
- **Aldrig commita** utan att användaren ber. Skillen levererar arbetsträd-ändringar + `git status`.
- **Beroenden-sektionen** är levande — uppdatera när blockerare blir klara.
- Formatera inte om `.md`-filer med prettier; `todos.py` sköter formateringen av `todo.md` själv.
- **Projektprefix i svar:** skriv todos med prefixet under Projektspecifikt
  (t.ex. `TT-34`), inte bara `#NN`, i allt som visas för användaren; numren krockar
  mellan projekten. I `todo.md` och filnamn står numret utan prefix.

## Edge cases

- **Hittar inte todo #N:** lista vad som finns och fråga.
- **Todon är redan i `done/`:** avbryt `done` — säg det. Erbjud `show` istället.
- **Användaren skriver titeln med hashtag** (`/todo done #10`): strip-a `#` innan tolkning.
- **Argument är inte en siffra för `done`/`reject`/`show`:** be om `<nr>`.
- **Redaktionellt skript-fel** (t.ex. `git mv` misslyckas): fall back till `mv` + flagga för användaren.

## Projektspecifikt

- `todos.py`-id: `tt`. Prefix i svar: `TT-34`. Appens backlog (`tt-app`, `TT-APP-01`) ligger i
  `../texttv-nu-smartare/` med samma konvention och egen `/todo`-skill.
