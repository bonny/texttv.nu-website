<?php

namespace App\Classes;

use Rct567\DomQuery\DomQuery;
use Illuminate\Support\Facades\Http;

/**
 * 
 * $finder = Finder::create()->files()->name('*.php')->in(__DIR__);
 * $texttvpage = (new Importer('100'))->fromRemote()->cleanup()->decorateCommon()->decorateSpecific();
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

            // Ofast är rad 24 den sista men ibland inte, t.ex. på sidan 100.
            // Beror ev. på om stora tecken/stort typsnitt har använts?
            $lastLine = $subPageLines[sizeof($subPageLines) - 1];
            $lastLineIsEmpty = trim($lastLine) ? false : true;

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
                $indexToAddBgTo = $lastLineIsEmpty ? 22 : 23;
                $subPageLines[$indexToAddBgTo] = sprintf('<span class="bgB">%s</span>', $subPageLines[$indexToAddBgTo]);
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

            // Blåa rader på sidan 100.
            if ($pageNum == 100) {
                $subPageLines[1] = sprintf('<span class="bgB">%s</span>', $subPageLines[1]);
                $subPageLines[2] = sprintf('<span class="bgB">%s</span>', $subPageLines[2]);
                $subPageLines[3] = sprintf('<span class="bgB">%s</span>', $subPageLines[3]);
                $subPageLines[4] = sprintf('<span class="bgB">%s</span>', $subPageLines[4]);
            }

            // Blåa rader på nyheterna 101.
            if (in_array($pageNum, [101, 102, 103, 104, 105])) {
                $subPageLines[1] = sprintf('<span class="bgB">%s</span>', $subPageLines[1]);
                $subPageLines[2] = sprintf('<span class="bgB">%s</span>', $subPageLines[2]);
                $subPageLines[3] = sprintf('<span class="bgB">%s</span>', $subPageLines[3]);
                $subPageLines[5] = sprintf('<span class="bgB">%s</span>', $subPageLines[5]);
            }

            // Blå rad överst på nyheter.
            if ($pageNum >= 106 && $pageNum <= 199) {
                $subPageLines[1] = sprintf('<span class="bgB">%s</span>', $subPageLines[1]);
            }

            // Blå rad överst på sport.
            if ($pageNum >= 303 && $pageNum < 399) {
                $subPageLines[1] = sprintf('<span class="bgB">%s</span>', $subPageLines[1]);
            }

            // Blåa rader överst på sport + gul rad längst ner.
            if (in_array($pageNum, [300, 301])) {
                $subPageLines[1] = sprintf('<span class="bgB">%s</span>', $subPageLines[1]);
                $subPageLines[2] = sprintf('<span class="bgB">%s</span>', $subPageLines[2]);
                $subPageLines[3] = sprintf('<span class="bgB">%s</span>', $subPageLines[3]);
                $subPageLines[5] = sprintf('<span class="bgB">%s</span>', $subPageLines[5]);

                $subPageLines[22] = sprintf('<span class="bgY">%s</span>', $subPageLines[22]);
            }

            // Gul stor text om första raden i texten har text = rubrik.
            if ($pageNum >= 106 && $pageNum <= 199) {
                $subPageLines[3] = sprintf('<span class="Y DH">%s</span>', $subPageLines[3]);
            }

            // Gul bakgrund på "Fler rubriker" och "Övriga rubriker" på nyhetsstartsidorna.
            if (in_array($pageNum, [101, 102, 103, 104, 105])) {
                $subPageLines[22] = preg_replace('/  (Fler rubriker|Övriga rubriker) \d{3}  /', '<span class="bgY">$0</span>', $subPageLines[22]);
            }

            // Skapa ren sträng av allt.
            $subPageText = implode("\n", $subPageLines);

            // Skapa länkar av alla nummer.
            $oldFirstLine = $subPageLines[0];
            // "203-219" osv.
            $subPageText = preg_replace('/(\d{3}-\d{3})/', '<a href="/$1">$1</a>', $subPageText);
            // " 100 " osv.
            $subPageText = preg_replace('/ (\d{3}) /', ' <a href="/$1">$1</a> ', $subPageText);
            // " 100" osv.
            $subPageText = preg_replace('/ (\d{3})\n/', " <a href='/\\1'>\\1</a>\n", $subPageText);
            // "100-" osv.
            $subPageText = preg_replace('/ (\d{3})-/', ' <a href="/$1">$1-</a>', $subPageText);
            // "...100 " osv.
            $subPageText = preg_replace('/\.(\d{3})/', '.<a href="/$1">$1</a>', $subPageText);
            // "417f" osv.
            $subPageText = preg_replace('/(\d{3})f/', '<a href="/$1">$1f</a>', $subPageText);
            // "530/" osv.
            $subPageText = preg_replace('/(\d{3})\//', '<a href="/$1">$1</a>/', $subPageText);

            // @todo
            // Ersätt "nästa sida" med länk till nästa sida.
            $subPageText = preg_replace('/ ((N|n)ästa sida) /', ' <a href="/' . ($pageNum + 1) . '">$1</a> /', $subPageText);

            // Ta bort länken från översta raden för den länkar till sig själv.
            $subPageLines = explode("\n", $subPageText);
            $subPageLines[0] = $oldFirstLine;

            // Skapa ren sträng av allt igen.
            $subPageText = implode("\n", $subPageLines);

            // Alt-texten vi får från SVT verkar ha problem med svenska tecken i översta raden.
            $subPageText = str_replace(
                [
                    ' m ndag ',
                    ' l rdag ',
                    ' s ndag ',
                ],
                [
                    ' måndag ',
                    ' lördag ',
                    ' söndag ',
                ],
                $subPageText
            );

            // Lägg till <div class="root"> runt allt.
            $subPageText = sprintf('<div class="root">%s</div>', $subPageText);

            $subPage['text'] = $subPageText;

            return $subPage;
        });

        $this->subPages = $subPages;

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
                    if ($this->pageNum() == 100) {
                        // På båda sidorna på startsidan för att centrera.
                        if (mb_strlen($line) % 2) {
                            $line = $line . ' ';
                        } else {
                            $line = ' ' . $line;
                        }
                    } else {
                        // Bara i slutet på andra sidorna.
                        $line = $line . ' ';
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
