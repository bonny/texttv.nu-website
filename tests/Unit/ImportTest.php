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
                'https://www.svt.se/text-tv/188' => '188.html',
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
            188,
            300,
            377,
        ];


        foreach ($pageNumsToTest as $pageNum) {
            $importer = new Importer($pageNum);
            $importer->fromRemote();

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


            // Antal rader och bredd osv.
            // Det ska vara 40 tecken bred ↔ och 24 rader hög ↕
            // Todo: om en rad än < 40 tecken så öka bredd med 1
            // på varje sida om vartannat tills den är 40
            echo "\n-----------\npage: {$importer->pageNum()}";
            $pageAsText = $importer->pageAsText();
            
            // Ta bort "\n\n\t\t" som verkar vara överst på varje sida.
            $pageAsText = str_replace("\n\n\t\t", '', $pageAsText);

            // Ta bort "\n\n\n\t" som verkar vara sist på varje sida.
            $pageAsText = str_replace("\n\n\n\t", '', $pageAsText);

            // Skapa collection med alla rader.
            $pageLines = collect(explode("\n", $pageAsText));
            echo "\nNum lines: {$pageLines->count()}";

            // Se till att för korta rader blir 40 rader
            // genom att lägga till mellanslag
            // före och efter omvartannat.
            $pageLines->transform(function ($line, $key) {
                while (mb_strlen($line) < 40) {
                    if (mb_strlen($line) % 2) {
                        $line = $line . ' ';
                    } else {
                        $line = ' ' . $line;
                    }
                }

                return $line;
            });

            echo "\npageAsText:\n";
            echo $pageLines->join("\n");
            echo "\n";
        }
    }


    public function test_page_plain_text()
    {
        $importer = (new Importer(100))->fromRemote();
        $page100object = $importer->pageObject();
        $this->assertEquals('100', $page100object->props->pageProps->pageNumber);
        $this->assertEquals('100-01', $page100object->props->pageProps->subPages[0]->subPageNumber);
        $page100expected = file_get_contents(__DIR__ . '/../TestPages/100_expected.txt');
        $this->assertEquals($page100expected, $importer->pageAsText());
    }
}
