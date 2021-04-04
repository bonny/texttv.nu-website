<?php

namespace App\Classes;

use Rct567\DomQuery\DomQuery;
use Illuminate\Support\Facades\Http;

/**
 * 
 * $finder = Finder::create()->files()->name('*.php')->in(__DIR__);
 * $texttvpage = new Importer('100')->fromRemote()->cleanup()->decorateCommon()->decorateSpecific();
 * then $texttvpage->pageAsText();
 * then $texttvpage->updated();
 */
class Importer
{
    protected $pageNum;
    protected $pageObject;
    protected $remoteResponse;

    // Illuminate\Support\Collection
    protected $subPages;

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

    /**
     * Applicera HTML som är gemensam för alla sidor,
     * t.ex. sidhuvud och sidfot osv.
     *
     * @return $this 
     */
    public function decorateCommon()
    {
        $subPages = $this->subPages();
        $subPages->transform(function ($subPage) {
            $subPageLines = explode("\n", $subPage['text']);
            $pageNum = $this->pageNum();

            // Gör "SVT Text" gul på första raden.
            $subPageLines[0] = str_replace('SVT Text', '<span class="Y">SVT Text</span>', $subPageLines[0]);

            // Lägg till <span class="toprow"> på första raden.
            $subPageLines[0] = sprintf('<span class="toprow">%s</span>', $subPageLines[0]);

            // Lägg till blå bakgrund på nedersta raden på många sidor.
            if (
                // Nyheter 100 - 198, 
                ($pageNum >= 100 && $pageNum <= 198)
                // Sport 300 - 399
                || ($pageNum >= 300 && $pageNum <= 399)
                // Vädret 400, 402 - 419
                || ($pageNum == 400)
                || ($pageNum >= 402 && $pageNum <= 419)
                // Snörapport mm 421 - 599
                || ($pageNum >= 421 && $pageNum <= 599)
                // TV 600 - 619, 623 - 669
                || ($pageNum >= 600 && $pageNum <= 619)
                || ($pageNum >= 623 && $pageNum <= 669)
                // SVT Text Info 700, 704-708
                || ($pageNum == 700)
                || ($pageNum >= 704 && $pageNum <= 708)
            ) {
                $subPageLines[23] = sprintf('<span class="bgB">%s</span>', $subPageLines[23]);
            }

            // Lägg till gul rad längst ner.
            if (
                // Ekonomi
                ($pageNum == 202)
                // Boräntor
                || ($pageNum == 231)
                || ($pageNum == 233)
            ) {
                $subPageLines[23] = sprintf('<span class="bgY">%s</span>', $subPageLines[23]);
            }

            // Lägg till <div class="root"> runt allt.
            $subPage = implode("\n", $subPageLines);
            $subPage = sprintf('<div class="root">%s</div>', $subPage);
            dd($subPage);
        });


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

    /**
     * Formattera alla undersidor så de är
     * 40 tecken breda ↔ och 24 rader höga ↕
     * 
     * @return $this 
     */
    public function cleanup()
    {
        $subPagesCleaned = collect();
        $subPages = collect($this->pageObject()->props->pageProps->subPages);

        foreach ($subPages as $subpage) {
            $pageAsText = $subpage->altText;

            // Ta bort "\n\n\t\t" som verkar vara överst på varje sida.
            $pageAsText = str_replace("\n\n\t\t", '', $pageAsText);

            // Ta bort "\n\n\n\t" som verkar vara sist på varje sida.
            // $pageAsText = str_replace("\n\n\n\t", '', $pageAsText);

            // Skapa collection med alla rader.
            $pageLines = collect(explode("\n", $pageAsText));

            // Behåll endast de första 24 raderna.
            $pageLines->splice(24);

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

            $subPagesCleaned->push([
                'subPageNumber' => $subpage->subPageNumber,
                'text' => $pageLines->join("\n")
            ]);
        }

        $this->subPages = $subPagesCleaned;

        return $this;
    }

    /**
     * Return all subpages.
     * 
     * @return Illuminate\Support\Collection
     */
    public function subpages()
    {
        return $this->subPages;
    }

    public function pageAsText()
    {
        return $this->pageObject()->props->pageProps->subPages[0]->altText;
    }

    // @todo
    public function pagesAsText()
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
