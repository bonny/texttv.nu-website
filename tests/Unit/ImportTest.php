<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Classes\Importer;

class ImportTest extends TestCase
{
    private static $page100html;
    private static $page100expected;
    private static $page188html;
    private static $page300html;
    private static $page377html;

    public static function setUpBeforeClass(): void
    {
        self::$page100html = file_get_contents(__DIR__ . '/../TestPages/100.html');
        self::$page100expected = file_get_contents(__DIR__ . '/../TestPages/100_expected.txt');
        self::$page188html = file_get_contents(__DIR__ . '/../TestPages/188.html');
        self::$page300html = file_get_contents(__DIR__ . '/../TestPages/300.html');
        self::$page377html = file_get_contents(__DIR__ . '/../TestPages/377.html');
    }

    /** @test */
    public function importer_class_exists()
    {
        $this->assertTrue(class_exists('App\Classes\Importer'));
    }

    public function test_html_to_object_parser()
    {
        $pages = [
            self::$page100html,
            self::$page188html,
            self::$page300html,
            self::$page377html,
        ];

        $importer = new Importer;

        foreach ($pages as $page) {
            $parsedHtmlObject = $importer->parseHTMLToObject($page);

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
        }
    }

    public function test_page_plain_text()
    {
        $importer = new Importer;
        $page100object =  $importer->parseHTMLToObject(self::$page100html);
        $this->assertEquals('100', $page100object->props->pageProps->pageNumber);
        $this->assertEquals('100-01', $page100object->props->pageProps->subPages[0]->subPageNumber);
        $this->assertEquals(self::$page100expected, $importer->get_page_plain_text($page100object));
    }
}
