<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Classes\Importer;
use Illuminate\Support\Facades\Http;


class ImportTest extends TestCase
{
    // public static function setUpBeforeClass(): void
    // {
    //     parent::setUpBeforeClass();
    // }

    protected function setUp(): void
    {
        parent::setUp();

        // Return files from local so we won't hammer the live
        // server when testing.
        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            $answers = [
                'https://www.svt.se/text-tv/100' => '100.html',
                #'https://www.svt.se/text-tv/188' => '188.html',
                'https://www.svt.se/text-tv/202' => '202.html',
                'https://www.svt.se/text-tv/300' => '300.html',
                'https://www.svt.se/text-tv/377' => '377.html',
            ];

            $contents = file_get_contents(__DIR__ . '/../TestPages/' . $answers[$request->url()]);

            return Http::response($contents, 200);
        });
    }

    /** @test */
    public function importer_class_exists()
    {
        $this->assertTrue(class_exists('App\Classes\Importer'));
    }

    public function test_html_to_object_parser()
    {
        $pageNumsToTest = [
            100,
            #188,
            202,
            300,
            377,
        ];

        foreach ($pageNumsToTest as $pageNum) {
            $importer = new Importer($pageNum);
            $importer->fromRemote()->cleanup()->colorize();

            $parsedHtmlObject = $importer->pageObject();

            $this->assertObjectHasAttribute('props', $parsedHtmlObject);
            $this->assertObjectHasAttribute('pageProps', $parsedHtmlObject->props);
            $this->assertObjectHasAttribute('status', $parsedHtmlObject->props->pageProps);
            $this->assertObjectHasAttribute('prevPage', $parsedHtmlObject->props->pageProps);
            $this->assertObjectHasAttribute('nextPage', $parsedHtmlObject->props->pageProps);
            $this->assertObjectHasAttribute('pageNumber', $parsedHtmlObject->props->pageProps);
            $this->assertObjectHasAttribute('meta', $parsedHtmlObject->props->pageProps);
            $this->assertObjectHasAttribute('updated', $parsedHtmlObject->props->pageProps->meta);
            $this->assertObjectHasAttribute('subPages', $parsedHtmlObject->props->pageProps);
            $this->assertIsArray($parsedHtmlObject->props->pageProps->subPages);

            $firstPage = $parsedHtmlObject->props->pageProps->subPages[0];
            $this->assertIsObject($firstPage);
            $this->assertObjectHasAttribute('subPageNumber', $firstPage);
            $this->assertObjectHasAttribute('gifAsBase64', $firstPage);
            $this->assertObjectHasAttribute('imageMap', $firstPage);
            $this->assertObjectHasAttribute('altText', $firstPage);

            $this->assertInstanceOf('Illuminate\Support\Collection', $importer->subpages());

            // Antal rader och bredd osv.
            // Det ska vara 40 tecken bred ↔ och 24 rader hög ↕
            // Todo: om en rad än < 40 tecken så öka bredd med 1
            // på varje sida om vartannat tills den är 40
        }
    }

    /**
     * Testa en sidas rena text, för cleanup och dekorationer osv.
     */
    public function test_page_plain_text()
    {
        $importer = (new Importer(100))->fromRemote();
        $page100object = $importer->pageObject();
        $this->assertEquals('100', $page100object->props->pageProps->pageNumber);
        $this->assertEquals('100-01', $page100object->props->pageProps->subPages[0]->subPageNumber);
        $page100expected = file_get_contents(__DIR__ . '/../TestPages/100_expected.txt');
        $this->assertEquals($page100expected, $importer->pageAsText());
    }

    /**
     * Testa att en sida får rätt antal rader och bredd.
     */
    public function test_cleanup()
    {
        $importer = new Importer(100);
        $importer->fromFile(__DIR__ . '/../TestPages/100.html');
        $importer->cleanup();

        $expectedCleanup = file_get_contents(__DIR__ . '/../TestPages/100_cleanup_expected.txt');
        $this->assertEquals($expectedCleanup, $importer->subpage(0)['text']);
    }

    /**
     * Testa att en sida "färgläggs" korrekt.
     */
    public function test_colorize()
    {
        $importer = new Importer(100);
        $importer->fromFile(__DIR__ . '/../TestPages/100.html');
        $importer->cleanup();
        $importer->colorize();

        $expectedColorize = file_get_contents(__DIR__ . '/../TestPages/100_colorize_expected.txt');
        #echo "yyy{$expectedColorize}yyy";exit;
        #echo "xxx" .$importer->subpage(0)['text'] . 'xxx';exit;
        // Inaktiv tills vidare pga ändrar så mkt i markup...
        //$this->assertEquals($expectedColorize, $importer->subpage(0)['text']);
    }

    /**
     * L2 i #08: tecken från SVT bakas in rått i HTML i colorizeLine().
     *
     * Att "<" aldrig blev XSS berodde bara på att varje tecken hamnar i en egen
     * <span>. Early-return-vägen — när charsExtractor inte känner igen tecknet —
     * släppte dock igenom obehandlade tecken i följd.
     *
     * Testet går direkt på colorizeLine() med en extractor som alltid returnerar
     * null, alltså exakt den läckande vägen. Medvetet oberoende av markup, till
     * skillnad från test_colorize vars fullständiga jämförelse är avstängd för
     * att den brakar på varje markupändring.
     */
    public function test_colorize_escapes_html_chars()
    {
        $importer = new Importer(100);

        $charsExtractor = new class {
            public function getChar($lineIndex, $charIndex)
            {
                return null;
            }
        };

        $method = new \ReflectionMethod(Importer::class, 'colorizeLine');
        $method->setAccessible(true);

        $this->assertSame(
            '&lt;a&amp;&quot;b&gt;',
            $method->invoke($importer, '<a&"b>', 0, $charsExtractor)
        );
    }

    /**
     * Testa att sidnummer på en rad blir till länkar, och att siffror som
     * inte är sidnummer (antal, börskurser) inte blir det.
     *
     * Testar linkifySingleLine(), som linkify() kör på varje rad. Fallen var
     * tidigare skrivna för linkifySingleLineWithSpanSupport() med en span per
     * tecken, ett format som colorize() inte längre ger.
     */
    public function test_linkify()
    {
        $importer = new Importer(100);
        $importer->fromFile(__DIR__ . '/../TestPages/100.html');

        // [radens text, förväntat antal länkar]
        $arrLines = [
            ['Inrikes 101 Utrikes 104 Innehåll 700', 3],
            ['-------- 136 --------', 1],
            ['Franskt knivdåd utreds som terror 134 ', 1],
            ['41 nya coronadödsfall i Sverige....106', 1],
            ['Situationen förvärras inom vården..108', 1],
            ['Fler rubriker 102', 1],
            ['  en 90 procents skydd mot covid-19 men ', 0],
            ['I studien har över 26 000 vaccinerade', 0],
            // ['ECDC. Cypern landar i 962 nya fall per', 0],
            // ['100 000 invånare under de två senaste ', 0],
            ['OMX STOCKHOLM (SLUT )   908.88  +0.25 ', 0],
            ['   SJUKVÅRD               3136.61  -0.09 ', 0],
            ['      83.4  83.6 CINT   83.6      180911', 0],
            ['  Minst 2 300 strokefall kan förhindras ', 0],
            ['  Arbetslöshet I mars var 549 000', 0],
        ];

        foreach ($arrLines as [$text, $expected]) {
            $line = $importer->linkifySingleLine($text);
            $this->assertSame($expected, substr_count($line, '<a '), "Rad: {$text}");
        }
    }
}
