<?php

namespace App\Classes;

use Exception;
use Rct567\DomQuery\DomQuery;
use Illuminate\Support\Facades\Http;

/**
 * 
 * $finder = Finder::create()->files()->name('*.php')->in(__DIR__);
 * $texttvpage = (new Importer('100'))->fromRemote()->cleanup()->decorate();
 * then $texttvpage->pageAsText();
 * then $texttvpage->updated();
 */
class Importer
{
    protected $pageNum;
    protected $pageObject;
    protected $remoteResponse;
    protected $linkprefix = '/';

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
     * @return $this
     */
    public function fromRemote()
    {
        $url = sprintf('https://www.svt.se/text-tv/%d', $this->pageNum);
        $response = Http::get($url);

        if ($response->successful()) {
            $this->remoteResponse = $response;
            $this->parseHTMLToObject($this->remoteResponse->body());
        }

        return $this;
    }

    /**
     * Get page from local file
     * Return page object.
     * 
     * @param string $file Path and name of file
     * @return $this
     */
    public function fromFile($file)
    {
        if (!file_exists($file)) {
            throw new Exception('File not found');
        }

        $this->parseHTMLToObject(file_get_contents($file));

        return $this;
    }

    /**
     * Find element
     * <script id="__NEXT_DATA__" type="application/json">
     * and set internal $jsonObject to that object.
     * 
     * @return $this
     */
    public function parseHTMLToObject($html)
    {
        $dom = new DomQuery($html);
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
    public function decorate()
    {
        $subPages = $this->subPages();

        $subPages->transform(function ($subPage, $subPageIndex) {
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

            // Blåa rader på ekonomi.
            if (in_array($pageNum, [200, 201])) {
                $subPageLines[1] = sprintf('<span class="bgB">%s</span>', $subPageLines[1]);
                $subPageLines[2] = sprintf('<span class="bgB">%s</span>', $subPageLines[2]);
                $subPageLines[3] = sprintf('<span class="bgB">%s</span>', $subPageLines[3]);
            }

            // Gul bakgrund på rubrik på ekonomi.
            if ($pageNum >= 203 && $pageNum <= 244) {
                $subPageLines[2] = sprintf('<span class="bgY DH">%s</span>', $subPageLines[2]);
            }

            // Gul bakgrund på rubrik på ekonomi allt-på-ett-sidan.
            if ($pageNum == 245 && $subPageIndex >= 1) {
                $subPageLines[2] = sprintf('<span class="bgY DH">%s</span>', $subPageLines[2]);
            }

            // Blå rad överst på sport.
            if ($pageNum >= 303 && $pageNum < 399) {
                $subPageLines[1] = sprintf('<span class="bgB">%s</span>', $subPageLines[1]);
            }
            if ($pageNum >= 530 && $pageNum < 549) {
                $subPageLines[1] = sprintf('<span class="bgB">%s</span>', $subPageLines[1]);
            }

            // Blåa rader överst på sport + gul rad längst ner.
            if (in_array($pageNum, [300, 301, 302])) {
                $subPageLines[1] = sprintf('<span class="bgB">%s</span>', $subPageLines[1]);
                $subPageLines[2] = sprintf('<span class="bgB">%s</span>', $subPageLines[2]);
                $subPageLines[3] = sprintf('<span class="bgB">%s</span>', $subPageLines[3]);
                $subPageLines[5] = sprintf('<span class="bgB">%s</span>', $subPageLines[5]);

                $subPageLines[22] = sprintf('<span class="bgY">%s</span>', $subPageLines[22]);
            }

            // Blåa rader överst på väder.
            if ($pageNum == 400) {
                $subPageLines[1] = sprintf('<span class="bgB">%s</span>', $subPageLines[1]);
                $subPageLines[2] = sprintf('<span class="bgB">%s</span>', $subPageLines[2]);
                $subPageLines[3] = sprintf('<span class="bgB">%s</span>', $subPageLines[3]);
                $subPageLines[4] = sprintf('<span class="bgB">%s</span>', $subPageLines[4]);
            }

            // Blåa rader överst på blandat.
            if ($pageNum == 500) {
                $subPageLines[1] = sprintf('<span class="bgB">%s</span>', $subPageLines[1]);
                $subPageLines[2] = sprintf('<span class="bgB">%s</span>', $subPageLines[2]);
                $subPageLines[3] = sprintf('<span class="bgB">%s</span>', $subPageLines[3]);
                $subPageLines[4] = sprintf('<span class="bgB">%s</span>', $subPageLines[4]);
            }

            // Gul stor text om första raden i texten har text = rubrik.
            if ($pageNum >= 106 && $pageNum <= 199) {
                $subPageLines[3] = sprintf('<span class="Y DH">%s</span>', $subPageLines[3]);
            }

            // Gul bakgrund på "Fler rubriker" och "Övriga rubriker" på nyhetsstartsidorna.
            if (in_array($pageNum, [101, 102, 103, 104, 105])) {
                $subPageLines[22] = preg_replace('/  (Fler rubriker|Övriga rubriker) \d{3}  /', '<span class="bgY">$0</span>', $subPageLines[22]);
            }

            // Blåa rader på TV.
            if ($pageNum == 600) {
                $subPageLines[1] = sprintf('<span class="bgB DH">%s</span>', $subPageLines[1]);
                $subPageLines[2] = sprintf('<span class="bgB">%s</span>', $subPageLines[2]);
                $subPageLines[3] = sprintf('<span class="bgB">%s</span>', $subPageLines[3]);
                $subPageLines[4] = sprintf('<span class="bgB">%s</span>', $subPageLines[4]);
                $subPageLines[21] = sprintf('<span class="bgB">%s</span>', $subPageLines[21]);
            }

            if ($pageNum >= 601 && $pageNum <= 619) {
                $subPageLines[1] = sprintf('<span class="bgB DH">%s</span>', $subPageLines[1]);
            }

            if ($pageNum >= 520 && $pageNum <= 622) {
                $subPageLines[1] = sprintf('<span class="bgR DH">%s</span>', $subPageLines[1]);
                $subPageLines[23] = sprintf('<span class="bgR">%s</span>', $subPageLines[23]);
            }

            // Innehåll
            if ($pageNum == 700 || $pageNum == 701) {
                $subPageLines[1] = sprintf('<span class="bgB">%s</span>', $subPageLines[1]);
                $subPageLines[2] = sprintf('<span class="bgB">%s</span>', $subPageLines[2]);
                $subPageLines[3] = sprintf('<span class="bgB">%s</span>', $subPageLines[3]);
                $subPageLines[4] = sprintf('<span class="bgB">%s</span>', $subPageLines[5]);

                $subPageLines[6] = sprintf('<span class="bgB">%s</span>', $subPageLines[6]);
                $subPageLines[7] = sprintf('<span class="bgB">%s</span>', $subPageLines[7]);
            }
            if ($pageNum >= 704 && $pageNum <= 706) {
                $subPageLines[1] = sprintf('<span class="bgB">%s</span>', $subPageLines[1]);
                $subPageLines[2] = sprintf('<span class="bgB">%s</span>', $subPageLines[2]);
                $subPageLines[3] = sprintf('<span class="bgB">%s</span>', $subPageLines[3]);
            }

            $subPageLines = $this->findHeadlines($subPageLines);

            // Skapa ren sträng av allt.
            $subPageText = implode("\n", $subPageLines);

            $subPageText = $this->addLinks($subPageText);

            // Ta bort länken från översta raden för den länkar till sig själv.
            $oldFirstLine = $subPageLines[0];
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

    /**
     * Skapa länkar av alla nummer.
     */
    public function addLinks(string $subPageText): string
    {

        // "203-219" osv.
        $subPageText = preg_replace('/(\d{3}-\d{3})/', '<a href="/$1">$1</a>', $subPageText);
        // " 100 " osv.
        $subPageText = preg_replace('/ (\d{3}) /', ' <a href="/$1">$1</a> ', $subPageText);
        // " 100" osv.
        $subPageText = preg_replace('/ (\d{3})\n/', " <a href=\"/\\1\">\\1</a>\n", $subPageText);
        // "100-" osv.
        $subPageText = preg_replace('/ (\d{3})-/', ' <a href="/$1">$1-</a>', $subPageText);
        // "...100 " osv.
        $subPageText = preg_replace('/\.\.(\d{3})/', '..<a href="/$1">$1</a>', $subPageText);
        // "417f" osv.
        $subPageText = preg_replace('/(\d{3})f/', '<a href="/$1">$1f</a>', $subPageText);
        // "530/" osv.
        $subPageText = preg_replace('/(\d{3})\//', '<a href="/$1">$1</a>/', $subPageText);
        // "Innehåll 700</span>" osv
        $subPageText = preg_replace('/ (\d{3})</', ' <a href="/$1">$1</a><', $subPageText);

        // Ersätt "nästa sida" med länk till nästa sida.
        $subPageText = preg_replace('/ ((N|n)ästa sida) /', ' <a href="/' . ($this->pageNum() + 1) . '">$1</a> ', $subPageText);

        // Länkprefix
        $subPageText = str_replace(' href="/', " href=\"{$this->linkprefix}", $subPageText);

        return $subPageText;
    }

    /**
     * På sidan 100 fixar vi olika färger på rubrikerna
     
     * @param array $subPageLines Alla rader på sidan som en array.
     * @return array Alla rader med <span> tillagd på varje rad med rubrik + efterföljande rubriker.
     */
    public function findHeadlines(array $subPageLines): array
    {
        // Baila om vi inte är på sidan 100.
        if ($this->pageNum() != 100) {
            return $subPageLines;
        }

        // På sidan 100 fixar vi olika färger på rubrikerna
        // Strunta i rad 1-3 och sista raderna som är meta

        // Nyhet 1: Y DH på första raden
        //        : Y på övriga rader
        // Nyhet 2: C på alla rader (kanske borde vara DH också?)

        /*
            Metod:
            Rad där text finns men inget sidnummer = troligtvis rubrik
            Om rad efter är tom
            Och raden därefter har text men inget sidnummer
            Och raden därefter har sidnummer
            Gruppera ihop dom, dvs. ta bort tomma raden

            Exempel på utseende:
            
            ----------

            Nu börjar 70-åringar att vaccineras  

            Gick snabbare än planerat i Stockholm 
            106 
                                                    
                    Biden vidtar åtgärder         
                    mot vapenvåldet i USA         
                            135                  
                                                    
            Novus: Ingen ljusning för Liberalerna 

            Små förändringar i ny opinionsmätning 
            112/160 
                                                    
            Idrottsarenor i fransk covidkamp 130             

            ----------
		
            100 SVT Text fredag 09 apr 2021      
            
                                                    
                                                    
            SMHI-varning för snö och hård vind   

            117 
                                                    
                Produktionsfel hos Janssen -       
                85 proc färre doser till USA       
                            131                  
                                                    
            Böter för Solberg som bröt mot regler 

            Statsministern deltog i större sällskap
            130 
                                                    
            USA: Ökad rysk närvaro vid Ukraina 136 
            
                Inrikes 101 Utrikes 104 Innehåll 700


            ----------

            100 SVT Text fredag 09 apr 2021      
            
                                                    
            Brittiske prinsen Philip har avlidit 

            Drottning Elizabeths make blev 99 år 
            135-136 
                                                    
                    Hiphoplegendaren DMX          
                    är död - blev 50 år          
                            150                   
                                                    
            Flest rapporter om Astra-biverkningar 

            Tros bero på medvetenhet hos gruppen  
            107 
                                                    
                Uefa: Blir publik under EM - 300   
            
                Inrikes 101 Utrikes 104 Innehåll 700

               ----------                         


	

            */
        // Rad att börja leta på. Först på rad 6 pga överst är text tv-loggan.
        $startLine = 6;

        // Antal rader att leta efter rubrik på. Raden längst ner på startsidan är nav så den ska skippas.
        $linesCount = 15;

        // start

        // Array som med rader att slutligen leta efter rubriker i.
        $linesToLookForHeadlinesIn = array_slice($subPageLines, $startLine, $linesCount);

        // Multi dimensionell array med alla hittade rubriker, med stöd för flera rader per rubrik.
        $foundHeadlines = $this->createHeadlinesMultiArray($linesToLookForHeadlinesIn);

        // Lägg till Y eller C.
        $foundHeadlines = array_map(function ($oneFoundHeadline, $index) {
            $color = $index % 2 ? 'C' : 'Y';
            $oneFoundHeadline = array_map(function ($oneFoundHeadlineLine) use ($color) {
                return "<span class='{$color}'>{$oneFoundHeadlineLine}</span>";
            }, $oneFoundHeadline);

            return $oneFoundHeadline;
        }, $foundHeadlines, array_keys($foundHeadlines));

        // Skapa ny lines-array med alla hittade rubriker.
        $emptyLine = str_pad('', 40, ' ');
        $linesWithHeadlines = [];
        foreach ($foundHeadlines as $oneFoundHeadline) {
            array_push($linesWithHeadlines, $emptyLine, ...$oneFoundHeadline);
        }

        // Ta bort första raden pga tom.
        $linesWithHeadlines = array_slice($linesWithHeadlines, 1);

        // Se till att nya arrayen har lika många element som den ursprungliga.
        if (count($linesWithHeadlines) !== $linesCount) {
            $linesWithHeadlines = array_pad($linesWithHeadlines, $linesCount, $emptyLine);
        }

        array_splice($subPageLines, $startLine, $linesCount, $linesWithHeadlines);

        return $subPageLines;
    }

    /**
     * Skapa multidimensional array med hittade rubriker från raderna som skickats in.
     * 
     * @param mixed $linesToLookForHeadlinesIn Array med rader
     * @return array Multidimensional array med rubriker
     */
    public function createHeadlinesMultiArray($linesToLookForHeadlinesIn): array
    {
        // Ta bort tomma rader.
        $linesToLookForHeadlinesIn = array_filter($linesToLookForHeadlinesIn, function ($line) {
            return !empty(trim($line));
        });

        // Indexera om.
        $linesToLookForHeadlinesIn = array_values($linesToLookForHeadlinesIn);

        // Hitta rubrik från första raden och framåt, stoppa när nästa rubrik börjar.
        $currentFoundHeadline = [];
        for ($i = 0; $i < count($linesToLookForHeadlinesIn); $i++) {
            $lineNum = $i;
            $line = $linesToLookForHeadlinesIn[$lineNum];
            $trimmedLine = trim($linesToLookForHeadlinesIn[$lineNum]);

            // Rubriken slutar när:
            // - en rad består av siffror, t.ex. "131" eller "112/160 ", "135-136 "
            // - en rad har siffror sist, t.ex. "Uefa: Blir publik under EM - 300   ", "Idrottsarenor i fransk covidkamp 130"
            // - en rad har siffror sist men har ett minusstreck också, t.ex. 'Ryssland - Kreml varnar 135-',
            // - se upp för rader med siffror som inte är nummer, t.ex. "Drottning Elizabeths make blev 99 år "
            // - se upp för rader som slutar med tre siffror men som är del av nummer, t.ex. '27 nya corona-dödsfall - totalt 13 788'.
            $lineIsSingleNumber = is_numeric($trimmedLine);
            $lineIsNumberRange = (bool) preg_match('/^\d{3}[\/\-]\d{3}$/', $trimmedLine);
            $lineEndsWithNumber = (bool) preg_match('/\d{3}-?$/', $trimmedLine);
            $lineEndsWithNumberThatProbablyNotIsPageNumber = (bool) preg_match('/ \d+ \d{3}/', $trimmedLine);

            $isEndOfHeadline = ($lineIsSingleNumber || $lineIsNumberRange || $lineEndsWithNumber) && !$lineEndsWithNumberThatProbablyNotIsPageNumber;

            $currentFoundHeadline[] = $line;

            if ($isEndOfHeadline) {
                $foundHeadlines[] = $currentFoundHeadline;
                $currentFoundHeadline = [];
            }
        }

        return $foundHeadlines;
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

        // Om vi inte har undersidor är sidan tom, troligtvis "Sidan ej i sändning."
        if ($subPages->isEmpty()) {
            // Lägg till en sida som endast innehåller "Sidan ej i sändning."
            $subPages->push((object) [
                'subPageNumber' => $this->pageNum(),
                'altText' => "{$this->pageNum()} SVT Text   Sidan ej i sändning" . str_repeat("\n", 23)
            ]);
        }

        // Rensa upp varje undersida.
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

    public function subpage($index = 0)
    {
        return $this->subPages->get(0);
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

    public function nextPageNum()
    {
        $nextPage = $this->pageObject()->props->pageProps->nextPage;

        if (!is_numeric($nextPage)) {
            return $this->pageNum();
        }

        return $nextPage;
    }

    public function prevPageNum()
    {
        $prevPageNum = $this->pageObject()->props->pageProps->prevPage;

        if (!is_numeric($prevPageNum)) {
            return  $this->pageNum();
        }

        return $prevPageNum;
    }

    public function linkprefix($prefix)
    {
        $this->linkprefix = $prefix;

        return $this;
    }

    /**
     * Gissa fram en titel på sidan.
     * Används för title och meta och grejs.
     
     * @return string
     */
    public function title()
    {
        $title = '';
        $firstSubPage = $this->subpage(0);
        $pageLines = explode("\n", $firstSubPage['text']);

        // Ta bort rad 0,1 för det är sidnummer och kategori.
        $pageLines = array_slice($pageLines, 2);

        // Hitta första raden som inte är tom.
        foreach ($pageLines as $oneLine) {
            $oneLine = trim(strip_tags($oneLine));
            if ($oneLine) {
                $title = $oneLine;
                break;
            }
        }

        return $title;
    }
}
