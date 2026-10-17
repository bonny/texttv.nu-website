<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Classes\Importer;

/**
 * Tester för hur sidnummer på en rad blir till länkar (linkifySingleLine).
 *
 * Två delar:
 *
 * - test_spec: handplockade rader med exakt vilka länkar de ska ge. Det här är
 *   specifikationen. Ändra bara förväntningen om beteendet ska ändras.
 * - test_corpus: alla unika rader med siffror från 421 sidor i prod
 *   (hämtade 2026-10-17), med de länkar koden gav då. Larmar på varje rad
 *   som ändras. Är ändringen avsiktlig: granska diffen och generera om med
 *
 *       UPDATE_LINKIFY_CORPUS=1 vendor/bin/phpunit --filter test_corpus
 */
class LinkifyTest extends TestCase
{
    private const CORPUS_FILE = __DIR__ . '/../TestPages/linkify-corpus.json';

    /**
     * Importer för en given sida. pageNum() styr t.ex. att börssidorna
     * 203–246 inte länkas.
     */
    private function importer(int $pageNum = 100): Importer
    {
        $importer = new Importer($pageNum);
        $importer->fromFile(__DIR__ . '/../TestPages/100.html');
        $importer->pageObject()->props->pageProps->pageNumber = $pageNum;

        return $importer;
    }

    /**
     * Länktexterna i en länkad rad, t.ex. ['101', '110-114', '123f'].
     */
    private function links(string $line): array
    {
        preg_match_all('|<a [^>]*>([^<]+)</a>|', $line, $matches);

        return $matches[1];
    }

    public function specProvider(): array
    {
        return [
            // Sidnummer som ska länkas.
            ['Inrikes 101 Utrikes 104 Innehåll 700', ['101', '104', '700']],
            ['-------- 136 --------', ['136']],
            ['Franskt knivdåd utreds som terror 134 ', ['134']],
            ['41 nya coronadödsfall i Sverige....106', ['106']],
            ['Situationen förvärras inom vården..108', ['108']],
            ['Fler rubriker 102', ['102']],
            ['TV3,TV4,Kanal5 620-', ['620']],
            ['Mer på 110-114', ['110-114']],
            ['101-102-103', ['101-102', '103']],
            ['Matchfakta NHL................... 328f', ['328f']],
            ['Lokala femdygnsprognoser.....405f-412f', ['405f-412f']],
            ['328/363 * Klipp i SVT Sport-appen', ['328', '363']],
            ['SVT1 i morgon..607f Radiohjälpen...676', ['607f', '676']],

            // Tusental: inget länkas, oavsett antal grupper.
            ['I studien har över 26 000 vaccinerade', []],
            ['Totalt har 14 151 personer som varit', []],
            ['  Minst 2 300 strokefall kan förhindras ', []],
            ['3 485 725 vaccindoser har getts i', []],
            ['Hela 12 485 725 doser', []],
            ['Totalt 1 234 567', []],
            ['Totalt 12 345 678 901', []],
            // "NNN NNN" är tusental. Listor av sidnummer med ett mellanslag
            // ("101 104") fanns inte på någon av 421 prod-sidor 2026-10-17.
            ['Totalt 126 450 personer', []],
            ['dödsfall. Minst 226 000 människor i', []],
            // Tvetydigt, men behandlas som tusental: "Del 2 302" går inte att
            // skilja från "2 302", och sådana rader fanns inte i prod.
            ['Del 2 302', []],

            // Tusental hoppas över, men andra sidnummer på raden länkas.
            ['Minst 2 300 drabbade, se 104', ['104']],
            ['Totalt har 14 151 personer, se 104', ['104']],

            // Belopp, antal och mått.
            ['833 miljoner kronor samma period', []],
            ['Topptipset        527 kr       (2 st)', []],
            ['   12 rätt         8 419 kr     (170 st)', []],
            ['13 rätt       191 472 kr      (13 st)', []],
            ['Vinst 100-200 kr', []],
            ['6r:    250.000 kr (2) 3r:      200 kr', []],
            ['6           2        14     132.650 kr', []],
            ['Pollenhalt 100 gånger högre än i fjol', []],
            ['över 100 personer gripits.', []],
            ['100 meter', []],
            ['400 meter häck', []],

            // Decimaltal och börskurser.
            ['  en 90 procents skydd mot covid-19 men ', []],
            ['OMX STOCKHOLM (SLUT )   908.88  +0.25 ', []],
            ['   SJUKVÅRD               3136.61  -0.09 ', []],
            ['      83.4  83.6 CINT   83.6      180911', []],
            ['28.812,6 (-0,8 procent)', []],

            // Telefonnummer, klockslag, årtal och resultat.
            ['Eller ring växeln (tfn 08-784 00 00).', []],
            ['Tel:  08-525 08 107', []],
            ['Ring 031-123 45 67', []],
            ['Ring 0771-123 456', []],
            ['Resultat 1999-120', []],
            ['1. Smederna     14 11  0  3 698-565 28', []],
            ['6. Vargarna     14  4  0 10 599-624 10', []],
            ['1 IFK Ystad    4  4  0  0  125-95    8', []],
            ['1. M. Verstappen NED/RBR 1:42:00,785', []],
            ['Snabbaste varv: C. Leclerc 1:35.202', []],
            ['6. Sverige           SWE   216,55', []],

            // Placering först på raden, men sidnummer längre in länkas.
            ['132. Jesper Svensson       SWE   246,00', []],
            ['Läs mer på 132. ', ['132']],
        ];
    }

    /**
     * @dataProvider specProvider
     */
    public function test_spec(string $text, array $expectedLinks)
    {
        $this->assertSame($expectedLinks, $this->links($this->importer()->linkifySingleLine($text)));
    }

    public function test_borssidor_lankas_inte()
    {
        $this->assertSame([], $this->links($this->importer(210)->linkifySingleLine('Se 101')));
    }

    public function test_corpus()
    {
        $corpus = json_decode(file_get_contents(self::CORPUS_FILE), true);

        $diffs = [];
        foreach ($corpus as &$row) {
            $links = $this->links($this->importer($row['page'])->linkifySingleLine($row['line']));
            if ($links !== $row['links']) {
                $diffs[] = sprintf(
                    "%d | %s\n      förut: [%s]  nu: [%s]",
                    $row['page'],
                    trim($row['text']),
                    implode(', ', $row['links'] ?? []),
                    implode(', ', $links)
                );
            }
            $row['links'] = $links;
        }
        unset($row);

        if (getenv('UPDATE_LINKIFY_CORPUS')) {
            file_put_contents(
                self::CORPUS_FILE,
                json_encode($corpus, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n"
            );
            $this->markTestSkipped(count($diffs) . ' rader uppdaterade i ' . basename(self::CORPUS_FILE));
        }

        $this->assertSame([], $diffs, count($diffs) . " av " . count($corpus) . " rader länkas annorlunda:\n" . implode("\n", $diffs));
    }
}
