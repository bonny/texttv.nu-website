-- Todo #26: blogginlägget om resultatsidorna som uppdaterar sig själva.
-- Läggs bara in om permalänken inte redan finns, så det går att köra om.
SET NAMES utf8mb4;
INSERT INTO `texttv.nu`.texttv_blogg (date_published, permalink, title, content)
SELECT NOW(), 'resultaten-uppdaterar-sig-sjalva', 'Resultaten uppdaterar sig själva', '<p>En vanlig kväll med Conference League och SHL samtidigt är 377 och 378 de mest besökta sidorna på TextTV.nu, och många har dem uppe medan matcherna pågår. Hittills har det betytt att man fick trycka på "Ladda om" för att se de nya resultaten. Nu sköter sidan det själv.</p>

<h2>Resultaten byts ut medan du tittar</h2>

<p>På målservicesidorna <a href="https://texttv.nu/376">376</a> till 383, på <a href="https://texttv.nu/330">Resultatbörsen 330</a> och på Allsvenskans och SHL:s tabeller och resultat (343, 344, 358 och 359) byts innehållet ut så snart en ny version kommer från SVT. Du behöver inte ladda om, och sidan hoppar inte tillbaka till toppen.</p>

<h2>Det som är nytt lyser upp</h2>

<p>En rad som har ändrats sedan förra versionen lyser gult i några sekunder. Gör ditt lag mål ser du direkt vilken match det gäller.</p>

<h2>Snabbare mellan målservicesidorna</h2>

<p>Överst på målservicesidorna finns nu en rad med sidnumren 376 till 383 och 330. Ett tryck tar dig till den sida du vill, i stället för att bläddra en i taget.</p>

<h2>Och lite till</h2>

<p>Under <strong>Gå till sida</strong> tar "Senast besökta" och "Mest läst nu" dig till nyheten du såg i listan, även om sidnumret har bytt nyhet sedan dess.</p>

<p>Äldre versioner av tabellsidorna heter nu vad de är, till exempel "Allsvenskan tabell, 22 okt 2026", i stället för tabellens första rad. Det syns i sökresultaten och när du delar en sida.</p>

<p>Allt det här gäller TextTV.nu i webbläsaren. Appen är sig lik.</p>

<h2>Säg vad du tycker</h2>

<p>Märker du att något inte uppdateras som det ska, eller saknar du en sida som borde göra det? Skriv till <a href="mailto:kontakt@texttv.nu">kontakt@texttv.nu</a>. Vi läser allt.</p>
'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `texttv.nu`.texttv_blogg WHERE permalink = 'resultaten-uppdaterar-sig-sjalva');
SELECT id, date_published, permalink, title FROM `texttv.nu`.texttv_blogg WHERE permalink = 'resultaten-uppdaterar-sig-sjalva';
