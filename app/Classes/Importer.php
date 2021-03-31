<?php

namespace App\Classes;

use Rct567\DomQuery\DomQuery;
use Illuminate\Support\Facades\Http;

class Importer {
    public function __construct() {
    }

    /**
     * Return page object.
     * 
     * @param int $pageNum 
     * @return object
     */
    public function getPage($pageNum) {
        $url = sprintf('https://www.svt.se/text-tv/%d', $pageNum);
        $response = Http::get($url);
        // dump($response->successful());
        // dd($response->body());
        $pageObject = $this->parseHTMLToObject($response->body());
        dd($this->get_page_plain_text($pageObject));
    }

    /**
     * Find element
     * <script id="__NEXT_DATA__" type="application/json">
     * and return that as JSON object.
     * 
     * @return object
     */
    public function parseHTMLToObject($html) {
        $dom = new DomQuery($html);
        $selector = '#__NEXT_DATA__';
        $element_content = $dom->find($selector)->text();
        return json_decode($element_content);
    }

    public function get_page_plain_text($pageObject) {
        return $pageObject->props->pageProps->subPages[0]->altText;
    }
}