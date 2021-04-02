<?php

namespace App\Classes;

use Rct567\DomQuery\DomQuery;
use Illuminate\Support\Facades\Http;

/**
 * 
 * $finder = Finder::create()->files()->name('*.php')->in(__DIR__);
 * $texttvpage = new Importer('100')->fromRemote()->decorateCommon()->decorateSpecific();
 * then $texttvpage->pageAsText();
 * then $texttvpage->updated();
 */
class Importer
{
    protected $pageNum;
    protected $pageObject;
    protected $remoteResponse;

    public function __construct($pageNum)
    {
        $this->pageNum = $pageNum;
    }

    /**
     * Get page from remote server.
     * Return page object.
     * 
     * @param int $pageNum 
     * @return $this
     */
    public function fromRemote()
    {
        $url = sprintf('https://www.svt.se/text-tv/%d', $this->pageNum);
        $response = Http::get($url);

        if ($response->successful()) {
            $this->remoteResponse = $response;
            $this->parseHTMLToObject();
        }

        return $this;
    }

    /**
     * Find element
     * <script id="__NEXT_DATA__" type="application/json">
     * and set internal $jsonObject to that object.
     * 
     * @return $this
     */
    public function parseHTMLToObject()
    {
        $dom = new DomQuery($this->remoteResponse->body());
        $selector = '#__NEXT_DATA__';
        $element_content = $dom->find($selector)->text();

        $this->pageObject = json_decode($element_content);

        return $this;
    }

    public function decorateCommon()
    {
        return $this;
    }

    public function decorateSpecific()
    {
        return $this;
    }

    public function pageObject()
    {
        return $this->pageObject;
    }

    public function pageAsText()
    {
        return $this->pageObject()->props->pageProps->subPages[0]->altText;
    }

    public function updated()
    {
        return $this->pageObject()->props->pageProps->meta->updated;
    }

    public function pageNum()
    {
        return $this->pageObject()->props->pageProps->pageNumber;
    }
}
