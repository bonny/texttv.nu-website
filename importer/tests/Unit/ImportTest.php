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
            $importer->fromRemote()->cleanup()->decorate();

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

    public function test_page_100_headlines_finder()
    {
        // Importera en fil eftersom vi behöver metadata för sidnummer.
        // Själva raderna matar vi in manuellt i funktionen sen hur som helst.
        $importer = new Importer(100);
        $importer->fromFile(__DIR__ . '/../TestPages/100.html');

        // Test 1
        $lines = $lines = array_map('trim', explode("\n", '
           Norge förlänger paus med Astras vaccin
         
           Inväntar utredning som kommer den 10/5
          131 
                                                 
                USA inför nya sanktioner mot     
                Ryssland - Kreml varnar 135-     
                                                 
                                                 
           FHM om covidläget: Otroligt allvarligt
         
           Alla ska ha fått en dos den 15 augusti
          108 
                                                 
           Tre avlidna i salmonella i Danmark 134
        '));

        $expected = [
            [
                'Norge förlänger paus med Astras vaccin',
                'Inväntar utredning som kommer den 10/5',
                '131',
            ],
            [
                'USA inför nya sanktioner mot',
                'Ryssland - Kreml varnar 135-',
            ],
            [
                'FHM om covidläget: Otroligt allvarligt',
                'Alla ska ha fått en dos den 15 augusti',
                '108',
            ],
            [
                'Tre avlidna i salmonella i Danmark 134'
            ]
        ];

        $this->assertEquals($expected, $importer->createHeadlinesMultiArray($lines));

        // Test 2
        $lines = array_map('trim', explode("\n", '
        Tegnell skeptisk till vaccinmål     

        Tvivlar på samordnarens uppgifter    
      107 
                                             
             Floyd-rättegången i USA -       
             "Jag bevittnade ett mord"       
                       137                   
                                             
       Pollenhalt 100 gånger högre än i fjol 
     
      115 
                                             
      Villa Lidköping klart för SM-final 300
      '));

        $expected = [
            [
                'Tegnell skeptisk till vaccinmål',
                'Tvivlar på samordnarens uppgifter',
                '107'
            ],
            [
                'Floyd-rättegången i USA -',
                '"Jag bevittnade ett mord"',
                '137'
            ],
            [
                'Pollenhalt 100 gånger högre än i fjol',
                '115'
            ],
            [
                'Villa Lidköping klart för SM-final 300'
            ]
        ];

        $this->assertEquals($expected, $importer->createHeadlinesMultiArray($lines));

        // Test 3 med lurigt nummer.
        $lines = $lines = array_map('trim', explode("\n", '
            27 nya corona-dödsfall - totalt 13 788

            Totalt över 900 000 bekräftat smittade
            106 
                                                
            Krogar i Finland     Kina vill stärka 
            öppnar på måndag     klimatsamarbetet 
                133                 136        
                                                
            Storsatsning på Sveriges infrastruktur
        
            Regeringen vill lägga 799 miljarder kr
            114 
                                                
            Internationell polisinsats i Skåne 117
        '));

        $expected = [
            [
                '27 nya corona-dödsfall - totalt 13 788',
                'Totalt över 900 000 bekräftat smittade',
                '106'
            ],
            [
                'Krogar i Finland     Kina vill stärka',
                'öppnar på måndag     klimatsamarbetet',
                '133                 136'
            ],
            [
                'Storsatsning på Sveriges infrastruktur',
                'Regeringen vill lägga 799 miljarder kr',
                '114'

            ],
            [
                'Internationell polisinsats i Skåne 117'
            ]
        ];

        $this->assertEquals($expected, $importer->createHeadlinesMultiArray($lines));
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
        #echo $importer->subpage(0)['text'];exit;
        $this->assertEquals($expectedColorize, $importer->subpage(0)['text']);
    }

    /**
     * Testa att sidnummer på en sida blir till länkar.
     */
    public function test_linkify()
    {
        $importer = new Importer(100);
        $importer->fromFile(__DIR__ . '/../TestPages/100.html');
        $importer->cleanup();
        $importer->colorize();
        $importer->linkify();

        $arrLines = [
            [
                'text' => 'Inrikes 101 Utrikes 104 Innehåll 700',
                'line' => '<span class="row" style="display:inline-block;transform:scaleY(2);transform-origin:top;"><span class="black " data-image-hash="791626951"> </span><span class="bgB " data-image-hash="2939094043"> </span><span class="bgB " data-image-hash="2939094043"> </span><span class="bgB " data-image-hash="2939094043"> </span><span class="bgB Y" data-image-hash="4108141168">I</span><span class="bgB Y" data-image-hash="4279042345">n</span><span class="bgB Y" data-image-hash="3343009095">r</span><span class="bgB Y" data-image-hash="207979164">i</span><span class="bgB Y" data-image-hash="684526819">k</span><span class="bgB Y" data-image-hash="421373412">e</span><span class="bgB Y" data-image-hash="3440866457">s</span><span class="bgB " data-image-hash="2939094043"> </span><span class="bgB Y" data-image-hash="2503059413">1</span><span class="bgB Y" data-image-hash="3078616209">0</span><span class="bgB Y" data-image-hash="2503059413">1</span><span class="bgB " data-image-hash="2939094043"> </span><span class="bgB Y" data-image-hash="1637741094">U</span><span class="bgB Y" data-image-hash="1945931269">t</span><span class="bgB Y" data-image-hash="3343009095">r</span><span class="bgB Y" data-image-hash="207979164">i</span><span class="bgB Y" data-image-hash="684526819">k</span><span class="bgB Y" data-image-hash="421373412">e</span><span class="bgB Y" data-image-hash="3440866457">s</span><span class="bgB " data-image-hash="2939094043"> </span><a href="104"><span class="bgB Y" data-image-hash="2503059413">1</span><span class="bgB Y" data-image-hash="3078616209">0</span><span class="bgB Y" data-image-hash="43320099">4</span></a><span class="bgB " data-image-hash="2939094043"> </span><span class="bgB Y" data-image-hash="4108141168">I</span><span class="bgB Y" data-image-hash="4279042345">n</span><span class="bgB Y" data-image-hash="4279042345">n</span><span class="bgB Y" data-image-hash="421373412">e</span><span class="bgB Y" data-image-hash="272190017">h</span><span class="bgB Y" data-image-hash="2391218961">å</span><span class="bgB Y" data-image-hash="2791228587">l</span><span class="bgB Y" data-image-hash="2791228587">l</span><span class="bgB " data-image-hash="2939094043"> </span><a href="700"><span class="bgB Y" data-image-hash="2469085609">7</span><span class="bgB Y" data-image-hash="3078616209">0</span><span class="bgB Y" data-image-hash="3078616209">0</span></a></span>',
                'expected' => 3
            ],
            [
                'text' => '-------- 136 --------',
                'line' => '<span class="row" style=""><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black Y" data-image-hash="2218724507" style="background: url(http://127.0.0.1:8000/storage/chars/2218724507.gif) center/cover"> </span><span class="black Y" data-image-hash="2218724507" style="background: url(http://127.0.0.1:8000/storage/chars/2218724507.gif) center/cover"> </span><span class="black Y" data-image-hash="2218724507" style="background: url(http://127.0.0.1:8000/storage/chars/2218724507.gif) center/cover"> </span><span class="black Y" data-image-hash="2218724507" style="background: url(http://127.0.0.1:8000/storage/chars/2218724507.gif) center/cover"> </span><span class="black Y" data-image-hash="2218724507" style="background: url(http://127.0.0.1:8000/storage/chars/2218724507.gif) center/cover"> </span><span class="black Y" data-image-hash="2218724507" style="background: url(http://127.0.0.1:8000/storage/chars/2218724507.gif) center/cover"> </span><span class="black Y" data-image-hash="2218724507" style="background: url(http://127.0.0.1:8000/storage/chars/2218724507.gif) center/cover"> </span><span class="black Y" data-image-hash="2218724507" style="background: url(http://127.0.0.1:8000/storage/chars/2218724507.gif) center/cover"> </span><span class="black Y" data-image-hash="2218724507" style="background: url(http://127.0.0.1:8000/storage/chars/2218724507.gif) center/cover"> </span><span class="black Y" data-image-hash="2218724507" style="background: url(http://127.0.0.1:8000/storage/chars/2218724507.gif) center/cover"> </span><span class="black Y" data-image-hash="2218724507" style="background: url(http://127.0.0.1:8000/storage/chars/2218724507.gif) center/cover"> </span><span class="black Y" data-image-hash="2218724507" style="background: url(http://127.0.0.1:8000/storage/chars/2218724507.gif) center/cover"> </span><span class="black Y" data-image-hash="2218724507" style="background: url(http://127.0.0.1:8000/storage/chars/2218724507.gif) center/cover"> </span><span class="black Y" data-image-hash="2218724507" style="background: url(http://127.0.0.1:8000/storage/chars/2218724507.gif) center/cover"> </span><span class="black Y" data-image-hash="2218724507" style="background: url(http://127.0.0.1:8000/storage/chars/2218724507.gif) center/cover"> </span><span class="black Y" data-image-hash="2218724507" style="background: url(http://127.0.0.1:8000/storage/chars/2218724507.gif) center/cover"> </span><span class="black " data-image-hash="791626951">1</span><span class="black Y" data-image-hash="448905409">3</span><span class="black Y" data-image-hash="1010736730">6</span><span class="black Y" data-image-hash="3006503129"> </span><span class="black " data-image-hash="791626951"> </span><span class="black Y" data-image-hash="2218724507" style="background: url(http://127.0.0.1:8000/storage/chars/2218724507.gif) center/cover"> </span><span class="black Y" data-image-hash="2218724507" style="background: url(http://127.0.0.1:8000/storage/chars/2218724507.gif) center/cover"> </span><span class="black Y" data-image-hash="2218724507" style="background: url(http://127.0.0.1:8000/storage/chars/2218724507.gif) center/cover"> </span><span class="black Y" data-image-hash="2218724507" style="background: url(http://127.0.0.1:8000/storage/chars/2218724507.gif) center/cover"> </span><span class="black Y" data-image-hash="2218724507" style="background: url(http://127.0.0.1:8000/storage/chars/2218724507.gif) center/cover"> </span><span class="black Y" data-image-hash="2218724507" style="background: url(http://127.0.0.1:8000/storage/chars/2218724507.gif) center/cover"> </span><span class="black Y" data-image-hash="2218724507" style="background: url(http://127.0.0.1:8000/storage/chars/2218724507.gif) center/cover"> </span><span class="black Y" data-image-hash="2218724507" style="background: url(http://127.0.0.1:8000/storage/chars/2218724507.gif) center/cover"> </span><span class="black Y" data-image-hash="2218724507" style="background: url(http://127.0.0.1:8000/storage/chars/2218724507.gif) center/cover"> </span><span class="black Y" data-image-hash="2218724507" style="background: url(http://127.0.0.1:8000/storage/chars/2218724507.gif) center/cover"> </span><span class="black Y" data-image-hash="2218724507" style="background: url(http://127.0.0.1:8000/storage/chars/2218724507.gif) center/cover"> </span><span class="black Y" data-image-hash="2218724507" style="background: url(http://127.0.0.1:8000/storage/chars/2218724507.gif) center/cover"> </span><span class="black Y" data-image-hash="2218724507" style="background: url(http://127.0.0.1:8000/storage/chars/2218724507.gif) center/cover"> </span><span class="black Y" data-image-hash="2218724507" style="background: url(http://127.0.0.1:8000/storage/chars/2218724507.gif) center/cover"> </span><span class="black Y" data-image-hash="2218724507" style="background: url(http://127.0.0.1:8000/storage/chars/2218724507.gif) center/cover"> </span><span class="black Y" data-image-hash="2218724507" style="background: url(http://127.0.0.1:8000/storage/chars/2218724507.gif) center/cover"> </span><span class="black " data-image-hash="791626951"> </span></span>',
                'expected' => 1

            ],
            [
                'text' => 'Franskt knivdåd utreds som terror 134 ',
                'line' => '<span class="row" style=""><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black C" data-image-hash="1196328021">F</span><span class="black C" data-image-hash="1734602574">r</span><span class="black C" data-image-hash="2883402741">a</span><span class="black C" data-image-hash="3222192940">n</span><span class="black C" data-image-hash="954025775">s</span><span class="black C" data-image-hash="2449141077">k</span><span class="black C" data-image-hash="4238424141">t</span><span class="black " data-image-hash="791626951"> </span><span class="black C" data-image-hash="2449141077">k</span><span class="black C" data-image-hash="3222192940">n</span><span class="black C" data-image-hash="1142174022">i</span><span class="black C" data-image-hash="720004127">v</span><span class="black C" data-image-hash="2418175840">d</span><span class="black C" data-image-hash="3061473050">å</span><span class="black C" data-image-hash="2418175840">d</span><span class="black " data-image-hash="791626951"> </span><span class="black C" data-image-hash="3218777965">u</span><span class="black C" data-image-hash="4238424141">t</span><span class="black C" data-image-hash="1734602574">r</span><span class="black C" data-image-hash="1784997008">e</span><span class="black C" data-image-hash="2418175840">d</span><span class="black C" data-image-hash="954025775">s</span><span class="black " data-image-hash="791626951"> </span><span class="black C" data-image-hash="954025775">s</span><span class="black C" data-image-hash="393652814">o</span><span class="black C" data-image-hash="199377842">m</span><span class="black " data-image-hash="791626951"> </span><span class="black C" data-image-hash="4238424141">t</span><span class="black C" data-image-hash="1784997008">e</span><span class="black C" data-image-hash="1734602574">r</span><span class="black C" data-image-hash="1734602574">r</span><span class="black C" data-image-hash="393652814">o</span><span class="black C" data-image-hash="1734602574">r</span><span class="black " data-image-hash="791626951"> </span><span class="black C" data-image-hash="3079385194">1</span><span class="black C" data-image-hash="566765326">3</span><span class="black C" data-image-hash="1021949789">4</span><span class="black " data-image-hash="791626951"> </span></span>',
                'expected' => 1
            ],
            [
                'text' => '41 nya coronadödsfall i Sverige....106',
                'line' => '<span class="row" style=""><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black W" data-image-hash="4100452725">4</span><span class="black W" data-image-hash="4083851532">1</span><span class="black " data-image-hash="791626951"> </span><span class="black W" data-image-hash="1687938640">n</span><span class="black W" data-image-hash="3963796173">y</span><span class="black W" data-image-hash="1115052600">a</span><span class="black " data-image-hash="791626951"> </span><span class="black W" data-image-hash="2821194774">c</span><span class="black W" data-image-hash="1402452776">o</span><span class="black W" data-image-hash="1959422332">r</span><span class="black W" data-image-hash="1402452776">o</span><span class="black W" data-image-hash="1687938640">n</span><span class="black W" data-image-hash="1115052600">a</span><span class="black W" data-image-hash="2774485468">d</span><span class="black W" data-image-hash="2217247227">ö</span><span class="black W" data-image-hash="2774485468">d</span><span class="black W" data-image-hash="3514181346">s</span><span class="black W" data-image-hash="4104323541">f</span><span class="black W" data-image-hash="1115052600">a</span><span class="black W" data-image-hash="3835147448">l</span><span class="black W" data-image-hash="3835147448">l</span><span class="black " data-image-hash="791626951"> </span><span class="black W" data-image-hash="2362493248">i</span><span class="black " data-image-hash="791626951"> </span><span class="black W" data-image-hash="3051795176">S</span><span class="black W" data-image-hash="3486937459">v</span><span class="black W" data-image-hash="2404971004">e</span><span class="black W" data-image-hash="1959422332">r</span><span class="black W" data-image-hash="2362493248">i</span><span class="black W" data-image-hash="3009099113">g</span><span class="black W" data-image-hash="2404971004">e</span><span class="black W" data-image-hash="1428543966">.</span><span class="black W" data-image-hash="1428543966">.</span><span class="black W" data-image-hash="1428543966">.</span><span class="black W" data-image-hash="1428543966">.</span><span class="black W" data-image-hash="4083851532">1</span><span class="black W" data-image-hash="202542670">0</span><span class="black W" data-image-hash="4157770175">6</span></span>',
                'expected' => 1
            ],
            [
                'text' => 'Situationen förvärras inom vården..108',
                'line' => '<span class="row" style=""><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black W" data-image-hash="3051795176">S</span><span class="black W" data-image-hash="2362493248">i</span><span class="black W" data-image-hash="429731105">t</span><span class="black W" data-image-hash="3872198495">u</span><span class="black W" data-image-hash="1115052600">a</span><span class="black W" data-image-hash="429731105">t</span><span class="black W" data-image-hash="2362493248">i</span><span class="black W" data-image-hash="1402452776">o</span><span class="black W" data-image-hash="1687938640">n</span><span class="black W" data-image-hash="2404971004">e</span><span class="black W" data-image-hash="1687938640">n</span><span class="black " data-image-hash="791626951"> </span><span class="black W" data-image-hash="4104323541">f</span><span class="black W" data-image-hash="2217247227">ö</span><span class="black W" data-image-hash="1959422332">r</span><span class="black W" data-image-hash="3486937459">v</span><span class="black W" data-image-hash="452990152">ä</span><span class="black W" data-image-hash="1959422332">r</span><span class="black W" data-image-hash="1959422332">r</span><span class="black W" data-image-hash="1115052600">a</span><span class="black W" data-image-hash="3514181346">s</span><span class="black " data-image-hash="791626951"> </span><span class="black W" data-image-hash="2362493248">i</span><span class="black W" data-image-hash="1687938640">n</span><span class="black W" data-image-hash="1402452776">o</span><span class="black W" data-image-hash="1391848320">m</span><span class="black " data-image-hash="791626951"> </span><span class="black W" data-image-hash="3486937459">v</span><span class="black W" data-image-hash="4016961320">å</span><span class="black W" data-image-hash="1959422332">r</span><span class="black W" data-image-hash="2774485468">d</span><span class="black W" data-image-hash="2404971004">e</span><span class="black W" data-image-hash="1687938640">n</span><span class="black W" data-image-hash="1428543966">.</span><span class="black W" data-image-hash="1428543966">.</span><span class="black W" data-image-hash="4083851532">1</span><span class="black W" data-image-hash="202542670">0</span><span class="black W" data-image-hash="901371022">8</span></span>',
                'expected' => 1
            ],
            [
                'text' => 'Fler rubriker 102',
                'line' => '<span class="row" style=""><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="bgY " data-image-hash="3221500607"> </span><span class="bgY " data-image-hash="3221500607"> </span><span class="bgY B" data-image-hash="3764862946">F</span><span class="bgY B" data-image-hash="3210651893">l</span><span class="bgY B" data-image-hash="3130253732">e</span><span class="bgY B" data-image-hash="448258208">r</span><span class="bgY " data-image-hash="3221500607"> </span><span class="bgY B" data-image-hash="448258208">r</span><span class="bgY B" data-image-hash="662045523">u</span><span class="bgY B" data-image-hash="1216993060">b</span><span class="bgY B" data-image-hash="448258208">r</span><span class="bgY B" data-image-hash="1867353521">i</span><span class="bgY B" data-image-hash="2946495849">k</span><span class="bgY B" data-image-hash="3130253732">e</span><span class="bgY B" data-image-hash="448258208">r</span><span class="bgY " data-image-hash="3221500607"> </span><span class="bgY B" data-image-hash="1317398266">1</span><span class="bgY B" data-image-hash="4146909398">0</span><span class="bgY B" data-image-hash="4103858412">2</span><span class="bgY " data-image-hash="3221500607"> </span><span class="bgY " data-image-hash="3221500607"> </span><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span></span>',
                'expected' => 1
            ],
            [
                'text' => '  en 90 procents skydd mot covid-19 men ',
                'line' => '<span class="row" style=""><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black W" data-image-hash="2404971004">e</span><span class="black W" data-image-hash="1687938640">n</span><span class="black " data-image-hash="791626951"> </span><span class="black W" data-image-hash="869978322">9</span><span class="black W" data-image-hash="202542670">0</span><span class="black " data-image-hash="791626951"> </span><span class="black W" data-image-hash="3662113739">p</span><span class="black W" data-image-hash="1959422332">r</span><span class="black W" data-image-hash="1402452776">o</span><span class="black W" data-image-hash="2821194774">c</span><span class="black W" data-image-hash="2404971004">e</span><span class="black W" data-image-hash="1687938640">n</span><span class="black W" data-image-hash="429731105">t</span><span class="black W" data-image-hash="3514181346">s</span><span class="black " data-image-hash="791626951"> </span><span class="black W" data-image-hash="3514181346">s</span><span class="black W" data-image-hash="3370992999">k</span><span class="black W" data-image-hash="3963796173">y</span><span class="black W" data-image-hash="2774485468">d</span><span class="black W" data-image-hash="2774485468">d</span><span class="black " data-image-hash="791626951"> </span><span class="black W" data-image-hash="1391848320">m</span><span class="black W" data-image-hash="1402452776">o</span><span class="black W" data-image-hash="429731105">t</span><span class="black " data-image-hash="791626951"> </span><span class="black W" data-image-hash="2821194774">c</span><span class="black W" data-image-hash="1402452776">o</span><span class="black W" data-image-hash="3486937459">v</span><span class="black W" data-image-hash="2362493248">i</span><span class="black W" data-image-hash="2774485468">d</span><span class="black W" data-image-hash="3110617345">-</span><span class="black W" data-image-hash="4083851532">1</span><span class="black W" data-image-hash="869978322">9</span><span class="black " data-image-hash="791626951"> </span><span class="black W" data-image-hash="1391848320">m</span><span class="black W" data-image-hash="2404971004">e</span><span class="black W" data-image-hash="1687938640">n</span><span class="black " data-image-hash="791626951"> </span></span>',
                'expected' => 0
            ],
            [
                'text' => 'I studien har över 26 000 vaccinerade',
                'line' => '<span class="row" style=""><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black Y" data-image-hash="2898533229">I</span><span class="black " data-image-hash="791626951"> </span><span class="black Y" data-image-hash="3269435600">s</span><span class="black Y" data-image-hash="3512325415">t</span><span class="black Y" data-image-hash="2720800313">u</span><span class="black Y" data-image-hash="4232634862">d</span><span class="black Y" data-image-hash="1238281560">i</span><span class="black Y" data-image-hash="1201408506">e</span><span class="black Y" data-image-hash="2175267644">n</span><span class="black " data-image-hash="791626951"> </span><span class="black Y" data-image-hash="3529361449">h</span><span class="black Y" data-image-hash="1373625354">a</span><span class="black Y" data-image-hash="3499478980">r</span><span class="black " data-image-hash="791626951"> </span><span class="black Y" data-image-hash="549299335">ö</span><span class="black Y" data-image-hash="118655349">v</span><span class="black Y" data-image-hash="1201408506">e</span><span class="black Y" data-image-hash="3499478980">r</span><span class="black " data-image-hash="791626951"> </span><span class="black Y" data-image-hash="575506833">2</span><span class="black Y" data-image-hash="3006503129">6</span><span class="black " data-image-hash="791626951"> </span><span class="black Y" data-image-hash="1426400892">0</span><span class="black Y" data-image-hash="1426400892">0</span><span class="black Y" data-image-hash="1426400892">0</span><span class="black " data-image-hash="791626951"> </span><span class="black Y" data-image-hash="118655349">v</span><span class="black Y" data-image-hash="1373625354">a</span><span class="black Y" data-image-hash="209167022">c</span><span class="black Y" data-image-hash="209167022">c</span><span class="black Y" data-image-hash="1238281560">i</span><span class="black Y" data-image-hash="2175267644">n</span><span class="black Y" data-image-hash="1201408506">e</span><span class="black Y" data-image-hash="3499478980">r</span><span class="black Y" data-image-hash="1373625354">a</span><span class="black Y" data-image-hash="4232634862">d</span><span class="black Y" data-image-hash="1201408506">e</span><span class="black " data-image-hash="791626951"> </span></span>',
                'expected' => 0
            ],
            // [
            //     'text' => 'ECDC. Cypern landar i 962 nya fall per',
            //     'line' => '<span class="row" style=""><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black Y" data-image-hash="761554024">E</span><span class="black Y" data-image-hash="1073426942">C</span><span class="black Y" data-image-hash="2206998799">D</span><span class="black Y" data-image-hash="1073426942">C</span><span class="black Y" data-image-hash="3576011010">.</span><span class="black " data-image-hash="791626951"> </span><span class="black Y" data-image-hash="1073426942">C</span><span class="black Y" data-image-hash="617413861">y</span><span class="black Y" data-image-hash="2127711927">p</span><span class="black Y" data-image-hash="1201408506">e</span><span class="black Y" data-image-hash="3499478980">r</span><span class="black Y" data-image-hash="2175267644">n</span><span class="black " data-image-hash="791626951"> </span><span class="black Y" data-image-hash="743656638">l</span><span class="black Y" data-image-hash="1373625354">a</span><span class="black Y" data-image-hash="2175267644">n</span><span class="black Y" data-image-hash="4232634862">d</span><span class="black Y" data-image-hash="1373625354">a</span><span class="black Y" data-image-hash="3499478980">r</span><span class="black " data-image-hash="791626951"> </span><span class="black Y" data-image-hash="1238281560">i</span><span class="black " data-image-hash="791626951"> </span><span class="black Y" data-image-hash="2538599854">9</span><span class="black Y" data-image-hash="3006503129">6</span><span class="black Y" data-image-hash="575506833">2</span><span class="black " data-image-hash="791626951"> </span><span class="black Y" data-image-hash="2175267644">n</span><span class="black Y" data-image-hash="617413861">y</span><span class="black Y" data-image-hash="1373625354">a</span><span class="black " data-image-hash="791626951"> </span><span class="black Y" data-image-hash="487088152">f</span><span class="black Y" data-image-hash="1373625354">a</span><span class="black Y" data-image-hash="743656638">l</span><span class="black Y" data-image-hash="743656638">l</span><span class="black " data-image-hash="791626951"> </span><span class="black Y" data-image-hash="2127711927">p</span><span class="black Y" data-image-hash="1201408506">e</span><span class="black Y" data-image-hash="3499478980">r</span></span>',
            //     'expected' => 0
            // ],
            // [
            //     'text' => '100 000 invånare under de två senaste ',
            //     'line' => '<span class="row" style=""><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black Y" data-image-hash="448905409">1</span><span class="black Y" data-image-hash="1426400892">0</span><span class="black Y" data-image-hash="1426400892">0</span><span class="black " data-image-hash="791626951"> </span><span class="black Y" data-image-hash="1426400892">0</span><span class="black Y" data-image-hash="1426400892">0</span><span class="black Y" data-image-hash="1426400892">0</span><span class="black " data-image-hash="791626951"> </span><span class="black Y" data-image-hash="1238281560">i</span><span class="black Y" data-image-hash="2175267644">n</span><span class="black Y" data-image-hash="118655349">v</span><span class="black Y" data-image-hash="2878137934">å</span><span class="black Y" data-image-hash="2175267644">n</span><span class="black Y" data-image-hash="1373625354">a</span><span class="black Y" data-image-hash="3499478980">r</span><span class="black Y" data-image-hash="1201408506">e</span><span class="black " data-image-hash="791626951"> </span><span class="black Y" data-image-hash="2720800313">u</span><span class="black Y" data-image-hash="2175267644">n</span><span class="black Y" data-image-hash="4232634862">d</span><span class="black Y" data-image-hash="1201408506">e</span><span class="black Y" data-image-hash="3499478980">r</span><span class="black " data-image-hash="791626951"> </span><span class="black Y" data-image-hash="4232634862">d</span><span class="black Y" data-image-hash="1201408506">e</span><span class="black " data-image-hash="791626951"> </span><span class="black Y" data-image-hash="3512325415">t</span><span class="black Y" data-image-hash="118655349">v</span><span class="black Y" data-image-hash="2878137934">å</span><span class="black " data-image-hash="791626951"> </span><span class="black Y" data-image-hash="3269435600">s</span><span class="black Y" data-image-hash="1201408506">e</span><span class="black Y" data-image-hash="2175267644">n</span><span class="black Y" data-image-hash="1373625354">a</span><span class="black Y" data-image-hash="3269435600">s</span><span class="black Y" data-image-hash="3512325415">t</span><span class="black Y" data-image-hash="1201408506">e</span><span class="black " data-image-hash="791626951"> </span></span>',
            //     'expected' => 0
            // ],
            [
                'text' => 'OMX STOCKHOLM (SLUT )   908.88  +0.25 ',
                'line' => '<span class="row" style="display:inline-block;transform:scaleY(2);transform-origin:top;"><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black Y" data-image-hash="283466393">O</span><span class="black Y" data-image-hash="1482993587">M</span><span class="black Y" data-image-hash="1813878989">X</span><span class="black " data-image-hash="791626951"> </span><span class="black Y" data-image-hash="569589551">S</span><span class="black Y" data-image-hash="723198051">T</span><span class="black Y" data-image-hash="283466393">O</span><span class="black Y" data-image-hash="237149484">C</span><span class="black Y" data-image-hash="4276515350">K</span><span class="black Y" data-image-hash="2316665486">H</span><span class="black Y" data-image-hash="283466393">O</span><span class="black Y" data-image-hash="3850717857">L</span><span class="black Y" data-image-hash="1482993587">M</span><span class="black " data-image-hash="791626951"> </span><span class="black Y" data-image-hash="2236949894">(</span><span class="black Y" data-image-hash="569589551">S</span><span class="black Y" data-image-hash="3850717857">L</span><span class="black Y" data-image-hash="2743038119">U</span><span class="black Y" data-image-hash="723198051">T</span><span class="black " data-image-hash="791626951"> </span><span class="black Y" data-image-hash="497179875">)</span><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black Y" data-image-hash="2226156176">9</span><span class="black Y" data-image-hash="272235641">0</span><span class="black Y" data-image-hash="2141324460">8</span><span class="black " data-image-hash="791626951">.</span><span class="black Y" data-image-hash="2141324460">8</span><span class="black Y" data-image-hash="2141324460">8</span><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black Y" data-image-hash="3090948402">+</span><span class="black Y" data-image-hash="272235641">0</span><span class="black " data-image-hash="791626951">.</span><span class="black Y" data-image-hash="2186440670">2</span><span class="black Y" data-image-hash="2568391651">5</span><span class="black " data-image-hash="791626951"> </span></span>',
                'expected' => 0
            ],
            [
                'text' => '   SJUKVÅRD               3136.61  -0.09 ',
                'line' => '<span class="row" style=""><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black C" data-image-hash="3975224026">S</span><span class="black C" data-image-hash="3840452956">J</span><span class="black C" data-image-hash="4183581425">U</span><span class="black C" data-image-hash="700433280">K</span><span class="black C" data-image-hash="2396007579">V</span><span class="black C" data-image-hash="1874581346">Å</span><span class="black C" data-image-hash="4080190399">R</span><span class="black C" data-image-hash="2313217446">D</span><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black C" data-image-hash="566765326">3</span><span class="black C" data-image-hash="3079385194">1</span><span class="black C" data-image-hash="566765326">3</span><span class="black C" data-image-hash="2932154765">6</span><span class="black C" data-image-hash="242520669">.</span><span class="black C" data-image-hash="2932154765">6</span><span class="black C" data-image-hash="3079385194">1</span><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black C" data-image-hash="1221085632">-</span><span class="black C" data-image-hash="963619058">0</span><span class="black C" data-image-hash="242520669">.</span><span class="black C" data-image-hash="963619058">0</span><span class="black C" data-image-hash="4216624890">9</span><span class="black " data-image-hash="791626951"> </span></span>',
                'expected' => 0
            ],
            [
                'text' => '      83.4  83.6 CINT   83.6      180911',
                'line' => '<span class="row" style=""><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black W" data-image-hash="901371022">8</span><span class="black W" data-image-hash="2027917116">3</span><span class="black W" data-image-hash="1428543966">.</span><span class="black W" data-image-hash="4100452725">4</span><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black W" data-image-hash="901371022">8</span><span class="black W" data-image-hash="2027917116">3</span><span class="black W" data-image-hash="1428543966">.</span><span class="black W" data-image-hash="4157770175">6</span><span class="black " data-image-hash="791626951"> </span><span class="black W" data-image-hash="2065309848">C</span><span class="black W" data-image-hash="1677779819">I</span><span class="black W" data-image-hash="834112813">N</span><span class="black W" data-image-hash="3865802413">T</span><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black W" data-image-hash="901371022">8</span><span class="black W" data-image-hash="2027917116">3</span><span class="black W" data-image-hash="1428543966">.</span><span class="black W" data-image-hash="4157770175">6</span><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black W" data-image-hash="4083851532">1</span><span class="black W" data-image-hash="901371022">8</span><span class="black W" data-image-hash="202542670">0</span><span class="black W" data-image-hash="869978322">9</span><span class="black W" data-image-hash="4083851532">1</span><span class="black W" data-image-hash="4083851532">1</span></span>',
                'expected' => 0
            ],
            [
                'text' => "  Minst 2 300 strokefall kan förhindras ",
                'line' => '<span style="display:inline-block;transform:scaleY(2);transform-origin:top;"><span class="black " data-image-hash="791626951"> </span><span class="black " data-image-hash="791626951"> </span><span class="black Y" data-image-hash="1482993587">M</span><span class="black Y" data-image-hash="3298917018">i</span><span class="black Y" data-image-hash="935983407">n</span><span class="black Y" data-image-hash="3737055915">s</span><span class="black Y" data-image-hash="720053815">t</span><span class="black " data-image-hash="791626951"> </span><span class="black Y" data-image-hash="2186440670">2</span><span class="black " data-image-hash="791626951"> </span><span class="black Y" data-image-hash="407997093">3</span><span class="black Y" data-image-hash="272235641">0</span><span class="black Y" data-image-hash="272235641">0</span><span class="black " data-image-hash="791626951"> </span><span class="black Y" data-image-hash="3737055915">s</span><span class="black Y" data-image-hash="720053815">t</span><span class="black Y" data-image-hash="787031178">r</span><span class="black Y" data-image-hash="3768925934">o</span><span class="black Y" data-image-hash="3762506443">k</span><span class="black Y" data-image-hash="3520683490">e</span><span class="black Y" data-image-hash="3831125202">f</span><span class="black Y" data-image-hash="3764076653">a</span><span class="black Y" data-image-hash="1341498726">l</span><span class="black Y" data-image-hash="1341498726">l</span><span class="black " data-image-hash="791626951"> </span><span class="black Y" data-image-hash="3762506443">k</span><span class="black Y" data-image-hash="3764076653">a</span><span class="black Y" data-image-hash="935983407">n</span><span class="black " data-image-hash="791626951"> </span><span class="black Y" data-image-hash="3831125202">f</span><span class="black Y" data-image-hash="2312578866">ö</span><span class="black Y" data-image-hash="787031178">r</span><span class="black Y" data-image-hash="3635874921">h</span><span class="black Y" data-image-hash="3298917018">i</span><span class="black Y" data-image-hash="935983407">n</span><span class="black Y" data-image-hash="762988838">d</span><span class="black Y" data-image-hash="787031178">r</span><span class="black Y" data-image-hash="3764076653">a</span><span class="black Y" data-image-hash="3737055915">s</span><span class="black " data-image-hash="791626951"> </span></span>',
                'expected' => 0
            ]
        ];

        foreach ($arrLines as $oneLine) {
            // Aktivera nedan echo för att se vilken rad som inte passerade testerna.
            // echo "\nText: {$oneLine['text']}, expected: {$oneLine['expected']}";
            $importer->linkifySingleLine($oneLine['line'], $numberReplacementsDone);
            $this->assertEquals($oneLine['expected'], $numberReplacementsDone);
        }
    }
}
