<?php

namespace App\Classes;

use Exception;
use Dgoring\DomQuery\DomQuery;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * 
 * $finder = Finder::create()->files()->name('*.php')->in(__DIR__);
 * $texttvpage = (new Importer('100'))->fromRemote()->cleanup()->colorize()->linkify();
 * then $texttvpage->pageAsText();
 * then $texttvpage->updated();
 */
class Importer
{
    protected $pageNum;
    protected $pageObject;
    protected $remoteResponse;
    protected $linkprefix = '/';

    // Om debug är på så skrivs image-idn ut i htmlkoden.
    protected $withdebug = false;

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

    // Ersätt text överst pga alt-texten vi får från SVT verkar ha problem med svenska tecken i översta raden.
    protected function fixDayNamesInHead(array $subPageLines): array
    {
        $subPageLines[0] = str_replace(
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
            $subPageLines[0]
        );

        return $subPageLines;
    }

    protected function colorizeLine($line, $lineIndex, $charsExtractor)
    {
        // Skapa array med alla tecken i raden.
        $lineChars = mb_str_split($line);

        $lineChars = array_map(
            function ($char, $charIndex) use ($line, $lineIndex, $charsExtractor) {
                $charInfo = $charsExtractor->getChar($lineIndex, $charIndex);

                if (!$charInfo) {
                    return $char;
                }

                $strDataImageHash = '';
                if ($this->withdebug) {
                    $strDataImageHash = sprintf(
                        ' data-image-hash="%1$s"',
                        $charInfo['charImageHash']
                    );
                }

                $class = sprintf(
                    '%1$s %2$s',
                    $charInfo['charColors']['backgroundClass'],
                    $charInfo['charColors']['textClass'],
                );

                // Om grafik lägg på klass som säger det så vi kan style'a senare via CSS.
                if ($charInfo['charType']['type'] === 'image') {
                    $class = "$class bgImg";
                }

                $class = trim($class);
                $class = $class ? sprintf(' class="%s"', $class) : '';

                if ($charInfo['charType']['type'] === 'image') {
                    $charInfoHash = $charInfo['charImageHash'];
                    $charFilename = "storage/chars/{$charInfoHash}.gif";
                    
                    // @string $charUrl For example "http://localhost:8000/storage/chars/2335531887.gif".
                    $charUrl = asset($charFilename);

                    // Bild
                    $style = sprintf(
                        'background: url(%1$s) center/cover',
                        $charUrl
                    );
                    $char = sprintf(
                        '<span style="%5$s"%4$s%2$s>%1$s</span>',
                        $char,
                        $class,
                        "", // removed
                        $strDataImageHash, // 4
                        $style // 5
                    );
                } elseif ($charInfo['charType']['type'] === 'text' && $charInfo['charType']['scale'] === 2) {
                    // Rubrik
                    $char = sprintf(
                        '<span%4$s%2$s>%1$s</span>',
                        $char,
                        $class,
                        '', // removed
                        $strDataImageHash // 4
                    );
                } else {
                    // Vanlig text
                    #echo $strDataImageHash;
                    $char = sprintf(
                        '<span%4$s%2$s>%1$s</span>',
                        $char,
                        $class,
                        '', // removed
                        $strDataImageHash // 4
                    );
                }

                return $char;
            },
            $lineChars,
            array_keys($lineChars)
        );

        // Gör en rad till sträng igen från alla tecken i raden.
        $line = implode("", $lineChars);

        return $line;
    }

    /**
     * Flytta in text på rader, pga texten vi får från SVT
     * verkar inte alltid ha korrekt antal mellanslag osv. 
     *
     * @param mixed $subPageLines 
     * @param mixed $subPage
     * @return mixed 
     */
    protected function alignLineTexts($subPageLines, $subPage)
    {
        // Flytta "SVT Text" till höger på nyheter och sport
        if (in_array($this->pageNum(), [101, 102, 103, 500])) {
            $subPageLines[3] = str_replace('SVT Text                              ', '                           SVT Text   ', $subPageLines[3]);
        } else if (in_array($this->pageNum(), [104, 105, 200, 201, 400])) {
            $subPageLines[3] = str_replace('SVT Text                               ', '                            SVT Text   ', $subPageLines[3]);
        }

        // Flytta Rubriker 101-103 till höger på 101
        // "   Rubriker 101-103            "
        if (in_array($this->pageNum(), [101, 102, 103])) {
            $subPageLines[22] = str_replace(
                '   Rubriker 101-103            ',
                '            Rubriker 101-103', 
                $subPageLines[22]
            );
        }

        if (in_array($this->pageNum(), [104, 105])) {
            $subPageLines[22] = str_replace(
                '   Rubriker 104-105            ',
                '            Rubriker 104-105', 
                $subPageLines[22]
            );
        }

        // Flytta 1,2 milj/dag till höger
        if (in_array($this->pageNum(), [300, 301, 302])) {
            $subPageLines[1] = str_replace(' 1,2 milj/dag                           ', '                           1,2 milj/dag ', $subPageLines[1]);
        }

        // Lägg på mellanslag först på raden längst ner pga det saknas.
        $subPageLines[23] = str_replace(' Utrikes 104  Sport 300  Innehåll 700   ', '   Utrikes 104  Sport 300  Innehåll 700 ', $subPageLines[23]);

        // På vädret 401 måste många rader skjutas till höger.
        if (in_array($this->pageNum(), [401])) {
            // Rad 1 - 23
            for ($i = 1; $i < 24; $i++) {
                $subPageLines[$i] = trim($subPageLines[$i]);
                // Putta in alla rader n tecken.
                $subPageLines[$i] = str_pad('', 20, ' ') . $subPageLines[$i];
                // Se till att varje rad är 40 tecken.
                $subPageLines[$i] = $this->mb_str_pad($subPageLines[$i], 40);
            }
        }

        // Putta text på UR sid 800 till höger.
        if (in_array($this->pageNum(), [800])) {
            // "KUNDTJÄNST  020-58 58 00"
            // "08-784 42 40"
            $subPageLines[2] = '        ' . $subPageLines[2];
            $subPageLines[2] = mb_substr($subPageLines[2], 0, 40);
            $subPageLines[3] = '        ' . $subPageLines[3];
            $subPageLines[3] = mb_substr($subPageLines[3], 0, 40);
        }

        // Putta text på teknisk provsida 777.
        // Korrigera även text som är fel i deras alt-text.
        if (in_array($this->pageNum(), [777])) {
            // "   SVT  TEKNISKPROVSIDA                 "
            $subPageLines[4] = str_replace("   SVT  TEKNISKPROVSIDA                 ", "        SVT  TEKNISK  PROVSIDA          ", $subPageLines[4]);

            // " Detta är röd text på blå bakgrund      "
            $subPageLines[16] = str_replace(" Detta är röd text på blå bakgrund      ", "    Detta är röd text på blå bakgrund   ", $subPageLines[16]);
        }

        // OS-sida, 444 har används både för OS i Rio och för OS i Tokyo.
        if (in_array($this->pageNum(), [440])) {
            // Fortsätt endast om rad 3 innehåller "23 juli - 9 augusti" pga 
            // antar att sidan kommer sluta vara Tokyo nångång
            if (strpos($subPageLines[2], '23 juli - 8 augusti') !== false) {
                #dd($subPageLines);
                $subPageLines[1] = str_replace(
                    "      2020 / 2021                       ",
                    "                      2020 / 2021       ",
                    $subPageLines[1]
                );

                $subPageLines[2] = str_replace(
                    "   OS  23 juli - 8 augusti              ",
                    "               OS  23 juli - 8 augusti  ",
                    $subPageLines[2]
                );
            }
        }

        // Börskuserna 245. Flersida så jobba bara med första.
        if ($subPage['subPageNumber'] == '245-01') {
            $subPageLines[15] = str_replace(
                "    DU FÖLJER JUST NU BÖRSKURSERNA      ",
                "      DU FÖLJER JUST NU BÖRSKURSERNA    ",
                $subPageLines[15]
            );
            $subPageLines[17] = str_replace(
                "    I SVERIGES TELEVISIONS TEXT-TV      ",
                "      I SVERIGES TELEVISIONS TEXT-TV    ",
                $subPageLines[17]
            );
            $subPageLines[19] = str_replace(
                "    - DÄR DU BLAND ANNAT OCKSÅ FÅR      ",
                "      - DÄR DU BLAND ANNAT OCKSÅ FÅR    ",
                $subPageLines[19]
            );
            $subPageLines[21] = str_replace(
                "    NYHETER,SPORT & TV-INFORMATION      ",
                "      NYHETER,SPORT & TV-INFORMATION    ",
                $subPageLines[21]
            );
        }

        // 202 BÖRSEN      SAMMANFATTNING .
        if (in_array($this->pageNum(), [202])) {
            $subPageLines[2] = preg_replace(
                '|  BÖRSEN      SAMMANFATTNING |',
                '     BÖRSEN      SAMMANFATTNING ',
                $subPageLines[2]
            );
        }

        // 129 landet runt
        if (in_array($this->pageNum(), [129])) {
            $subPageLines[1] = str_replace(
                '    LANDET RUNT',
                '      LANDET RUNT',
                $subPageLines[1]
            );
            $subPageLines[1] = (string) Str::of($subPageLines[1])->rtrim()->padRight(40);

            // Rad 6-22 får ojämn vänstermarginal som vi fixar till.
            // 3 tkn ska det vara till vänster före varje rad.
            $charsToAdd = '   ';
            for ($i = 6; $i <= 22; $i++) {
                $subPageLines[$i] = (string) Str::of($subPageLines[$i])->ltrim()->prepend($charsToAdd)->rtrim();
                $subPageLines[$i] = $this->mb_str_pad($subPageLines[$i], 40);
            }
        }

        return $subPageLines;
    }

    public function colorize()
    {
        $subPages = $this->subPages();

        // För varje sida...
        $subPages->transform(function ($subPage) {
            // Hoppa över sidor som inte har bilddata.
            if (empty($subPage['gifAsBase64'])) {
                return $subPage;
            }

            $charsExtractor = new TeletextCharsExtractor;
            $charsExtractor->imageFromString(base64_decode($subPage['gifAsBase64']))->parseImage();

            // Skapa array med alla rader en undersida.
            $subPageLines = explode("\n", $subPage['text']);

            $subPageLines = $this->fixDayNamesInHead($subPageLines);
            $subPageLines = $this->alignLineTexts($subPageLines, $subPage);

            // Hämta och skapa spans med färg för varje rad, för varje kolumn.
            $subPageLines = array_map(function ($line, $lineIndex) use ($charsExtractor) {
                // Hämta färg för varje tecken på denna rad.
                $currentLineHasHeadlineChars = $this->lineHasHeadlineChars($charsExtractor, $line, $lineIndex);
                $nextLineHasHeadlineChars = $this->lineHasHeadlineChars($charsExtractor, $line, $lineIndex + 1);

                // Gå igenom alla tecken på en rad och lägg till <span> med klasses för färger 
                // och inline styles för rubriker osv.
                $line = $this->colorizeLine($line, $lineIndex, $charsExtractor);

                // @HERE: visa imageid i output så kan skapa bilder
                // av OS-grafiken på 440.
                // if ($this->withdebug) {
                //     dump($line);
                // }

                if ($this->withdebug) {
                    // Kombinera inte element så image-idn blir synliga för varje tecken.
                } else {
                    // Kombinera flera element till ett.
                    // @TODO: lägg in bättre logik/funktion så att funktionen körs 
                    // flera gånger tills inga fler ersättningar görs, dvs. typ två gånger antagligen.
                    $line = $this->combineElementsOnLine($line);
                    // Ja, kör verkligen funktionen två gånger för att kombinera ihop ännu fler ¯\_(ツ)_/¯
                    $line = $this->combineElementsOnLine($line);
                }

                // Läg en span runt varje rad.
                $lineClasses = 'line';

                if ($lineIndex == 0) {
                    $lineClasses .= ' toprow';
                }

                // Om en line innehåller någon rubrik/char med scale: 2 så
                // ska hela raden tolkas som rubrik pga det verkar som det
                // alltid är så.
                $rowStyle = '';
                if ($currentLineHasHeadlineChars && $nextLineHasHeadlineChars) {
                    $rowStyle = ' style="display:inline-block;transform:scaleY(2);transform-origin:top;"';
                    // Om rubrik lägg på klass .DH ("double height", pga det hette det på tidigare SVT Text-sajt).
                    $lineClasses .= ' DH';
                }

                $line = sprintf(
                    '<span%2$s class="%3$s">%1$s</span>',
                    $line,
                    $rowStyle,
                    $lineClasses,
                );

                return $line;
            }, $subPageLines, array_keys($subPageLines));

            // Lägg till <span class="toprow"> på första raden.
            #$subPageLines[0] = sprintf('<span class="toprow">%s</span>', $subPageLines[0]);

            // Skapa ren sträng av hela undersidan igen.
            $subPageText = implode("\n", $subPageLines);

            // Lägg till <div class="root"> runt allt.
            $subPageText = sprintf('<div class="root">%s</div>', $subPageText);

            $subPage['text'] = $subPageText;

            return $subPage;
        });

        $this->subPages = $subPages;

        return $this;
    }

    /**
     * Skapa länkar av siffrorna som ligger mellan spans'en.
     */
    public function linkify()
    {
        $subPages = $this->subPages();
        $subPages->transform(function ($subPage) {
            $subPageLines = explode("\n", $subPage['text']);

            // Agera på varje rad.
            $subPageLines = array_map(function ($line, $lineIndex) {
                // Hoppa över rad 0 = första raden med aktuell sida + tidpunkt.
                if ($lineIndex == 0) {
                    return $line;
                }

                $line = $this->linkifySingleLine($line, $numberReplacements);

                // Gör länkar av saker som inte är sidnummer, t.ex. webbplatser.
                // "Läs mer på svtsport.se"
                // Nästa sida
                // @TODO: fixelifix
                $line = $this->linkifyURLsSingleLine($line, $lineIndex, $numberReplacements);

                return $line;
            }, $subPageLines, array_keys($subPageLines));

            $subPageText = implode("\n", $subPageLines);
            $subPage['text'] = $subPageText;

            return $subPage;
        });

        $this->subPages = $subPages;

        // Ersätt flera <span ...> som följer varande och har samma attribut med endast en span.
        // $subPages = $this->subPages();
        // $subPages->transform(function ($subPage) {
        //     $subPageLines = explode("\n", $subPage['text']);

        //     // Agera på varje rad.
        //     // En rad ser ca ut såhär:
        //     // <span><span class="bgBl"> </span><span class="bgBl"> </span><span class="bgBl"> </span><span class="bgBl Y"> </span><span class="bgBl Y"> </span>...

        //     $subPageLines = array_map(function ($line, $lineIndex) {
        //         return $this->combineElementsOnLine($line);
        //     }, $subPageLines, array_keys($subPageLines));

        //     $subPageText = implode("\n", $subPageLines);
        //     $subPageText = sprintf('<div class="root">%s</div>', $subPageText);
        //     $subPage['text'] = $subPageText;

        //     return $subPage;
        // });

        $this->subPages = $subPages;

        return $this;
    }

    public function combineElementsOnLine($line)
    {
        // Hämta alla element på raden.
        $domLine = new DomQuery($line);

        // Hämta alla element på raden. Är 40 element
        // om inte något blivit länkat, då är det färre pga en a ersätter
        // flera span.
        $lineElements = $domLine->find('*');

        $newLine = '';
        /** @var DOMElement $prevElm */
        $prevElm = null;
        $isElementOpened = false;

        for ($i = 0; $i < count($lineElements); $i++) {
            /** @var DOMElement $currentElm */
            $currentElm = $lineElements[$i]->get(0);

            // Påbörja nytt element om currentElm har class som skiljer sig från föregående element.
            $currentElmClass = $currentElm->getAttribute('class');
            $currentElmStyle = $currentElm->getAttribute('style');
            $currentElmHref = $currentElm->getAttribute('href');
            $currentElmHref = $currentElmHref ? " href={$currentElmHref}" : '';
            $currentElmText = $currentElm->textContent;
            $currentElmNodeName = $currentElm->nodeName;
            $prevElmClass = $prevElm ? $prevElm->getAttribute('class') : null;
            $prevElmStyle = $prevElm ? $prevElm->getAttribute('style') : null;
            $startNewElm = false;

            // Avgör om ett nytt element ska öppnas
            // eller om tidigare öppnat ska fortsätta vara öppen.
            if (!$prevElm) {
                // Öppna nytt element om inget redan är öppet.
                $startNewElm = true;
            } else if (
                // Öppna nytt element om classerna på aktuellt element skiljer sig från föregående element.
                $prevElm && $prevElm->getAttribute('class') !== $currentElmClass
            ) {
                $startNewElm = true;
            } else if ($currentElmStyle) {
                // Öppna nytt elm om elm har style pga bg-bild får inte vara över flera tecken
                // då det blir grafikfel.
                $startNewElm = true;
            }

            // Om aktuellt element inte har någon text (dvs. är ett mellanslag)
            // och dess bgX-färg är samma som föregående element så ska vi fortsätta på föregående.
            $backgroundRegExp = '|bg(\w+)|';
            if (
                preg_match($backgroundRegExp, $currentElmClass, $currentElmBackgroundClassMatches)
                && preg_match($backgroundRegExp, $prevElmClass, $prevElmBackgroundClassMatches)
            ) {
                // Både aktuellt elm och föregående elm har bg-klasser.
                // Fortsätt bara om curentElm inte har fler klasser pga vi får inte skriva över förgrunden.
                if (strpos($currentElmClass, ' ') === false) {
                    if (!$currentElmStyle && !$prevElmStyle) {
                        #dump($currentElmStyle);
                        #dump('currentElmClass', $currentElmClass);
                        // Kolla om de är samma.
                        if ($currentElmBackgroundClassMatches[1] === $prevElmBackgroundClassMatches[1]) {
                            #dump($currentElmBackgroundClassMatches[1], $prevElmBackgroundClassMatches[1]);
                            if ($startNewElm) {
                                #echo "no start";
                                $startNewElm = false;
                            }
                        }
                    }
                }
            }

            // Stäng element om vi ska starta ny elm men elm redan är öppen.
            if ($startNewElm && $isElementOpened) {
                $newLine = sprintf(
                    '%1$s</%2$s>',
                    $newLine, // 1
                    $prevElm->nodeName, //
                );
                $isElementOpened = false;
            }

            if ($startNewElm) {
                // Starta ett nytt element.
                if ($currentElmStyle) {
                    $currentElmStyle = sprintf(' style="%s"', $currentElmStyle);
                }

                $newLine = sprintf(
                    '%1$s<%5$s class="%2$s"%4$s%6$s>%3$s',
                    $newLine, // 1
                    $currentElmClass, // 2
                    $currentElmText, // 3
                    $currentElmStyle, // 4
                    $currentElmNodeName, // 5
                    $currentElmHref, // 6
                );
                $isElementOpened = true;
            } else {
                // Fortsätt med redan öppnat element.
                $newLine = sprintf(
                    '%1$s%2$s',
                    $newLine, // 1
                    $currentElmText, // 2
                );
            }

            $prevElm = $currentElm;
        }

        // Stäng element som eventuellt fortfarande är öppen.
        if ($isElementOpened) {
            $newLine = sprintf(
                '%1$s</%2$s>',
                $newLine, // 1
                $currentElmNodeName // 2
            );
            $isElementOpened = false;
        }

        // $lineElements
        // Lägg på samma klasser
        #$elmsBeforeAndIncludingLine = $dom->not('.line *');
        #foreach ($domLine->children() as $oneLineChild) {
        #echo "<hr>";
        #dump($oneLineChild->get(0)->nodeName, $oneLineChild->get(0)->getAttribute('class'), $oneLineChild->get(0)->getAttribute('style'));
        #}
        #exit;
        #dd($domLine->children()->not('.line *')->getAttribute(('class')));

        // Lägg på radbryt och span runt hela igen.
        #$newLine = "<span class='line'>{$newLine}</span>";
        #dump('längd på rad före och efter', strlen($line), strlen($newLine) );
        #dd('$newLine', $newLine);
        return $newLine;
    }

    public function linkifySingleLineWithSpanSupport($line, &$numberReplacements = null)
    {
        $regexSpanStart = '<span\b[^>]*>';
        $regexSpanEnd = '</span>';
        $regexSingleNumber0 = '(0)';
        $regexSingleNumber1to9 = '([1-9])';
        $regexSingleNumber0to9 = '([0-9])';

        // Matchar
        $regexSpanAndThreeNumberLargerThan100 =
            $regexSpanStart . $regexSingleNumber1to9 . $regexSpanEnd .
            $regexSpanStart . $regexSingleNumber0to9 . $regexSpanEnd .
            $regexSpanStart . $regexSingleNumber0to9 . $regexSpanEnd;

        $regexSpanAndThreeNumberLessThan100 =
            $regexSpanStart . $regexSingleNumber0 . $regexSpanEnd .
            $regexSpanStart . $regexSingleNumber0to9 . $regexSpanEnd .
            $regexSpanStart . $regexSingleNumber0to9 . $regexSpanEnd;

        // Matchar tre nummer och en punkt, så t.ex. "908." borde fastna, som t.ex.
        // ekonomisidor har för kurser osv. "OMX STOCKHOLM (SLUT )   908.88  +0.25".
        $regexSpanAndThreeNumberLargerThan100AndADot =
            $regexSpanAndThreeNumberLargerThan100 .
            $regexSpanStart . '\.' . $regexSpanEnd;

        // Baila om vi matchar "908." osv.
        $numMatches = preg_match_all('|' . $regexSpanAndThreeNumberLargerThan100AndADot . '|', $line);
        if ($numMatches) {
            return $line;
        }

        // Baila om vi matchar börskurser som t.ex. "83.4  83.6 CINT   83.6      180911"
        // där "180911" är två nummer på varandra.
        $regexSpanAndThreeNumberLargerThan100AndOneMoreNumber =
            $regexSpanAndThreeNumberLargerThan100 .
            $regexSpanStart . $regexSingleNumber0to9 . $regexSpanEnd;

        $numMatches = preg_match_all('|' . $regexSpanAndThreeNumberLargerThan100AndOneMoreNumber . '|', $line);
        if ($numMatches) {
            return $line;
        }

        // Baila om sidnummer har en siffra innan, t.ex. "Minst 2 300 strokefall kan förhindras".
        $regexSpanAndThreeNumberLargerThan100AndASpaceAndNumberBefore =
            $regexSpanStart . $regexSingleNumber0to9 . $regexSpanEnd .
            $regexSpanStart . '\ ' . $regexSpanEnd . // spar char
            $regexSpanAndThreeNumberLargerThan100;

        $numMatches = preg_match_all('|' . $regexSpanAndThreeNumberLargerThan100AndASpaceAndNumberBefore . '|', $line);
        if ($numMatches) {
            return $line;
        }

        // Baila om sidnummer har siffror efter som inte är giltigt nummer,
        // t.ex. "Arbetslöshet I mars var 549 000".
        $regexSpanAndThreeNumberLargerThan100AndNotValidPageRangeAfter =
            $regexSpanAndThreeNumberLargerThan100 .
            $regexSpanStart . '\ ' . $regexSpanEnd . // spar char
            $regexSpanAndThreeNumberLessThan100;

        $numMatches = preg_match_all('|' . $regexSpanAndThreeNumberLargerThan100AndNotValidPageRangeAfter . '|', $line);
        if ($numMatches) {
            return $line;
        }

        #dump('$regexSpanAndThreeNumberLargerThan100AndADot', $numMatches, strip_tags($line));

        $line = preg_replace_callback('|' . $regexSpanAndThreeNumberLargerThan100 . '|', function ($matches) {
            // $matches[0] = complete match, dvs. <span>1</span><span>0</span><span>0</span>
            // $matches[1] = first subpattern, dvs. siffra ett
            // $matches[2] = second subpattern, dvs. siffra två
            // $matches[3] = third subpattern, dvs. siffra tre
            $pageNum = $matches[1] . $matches[2] . $matches[3];
            $completeMatch = $matches[0];

            // Gamla Android-appen som är flera år gammal
            // använder FastClick som inte fungerar om markup är
            // <a href="/123"><span>1</span><span>2</span><span>3</span></a>
            // så ta ersatt allt med en enda länk dock måste vi överföra 
            // alla attribut (class, data-*) till länken.
            $dom = new \DOMDocument();
            $dom->loadHTML($completeMatch);
            $spans = $dom->getElementsByTagName('span');
            $classes = [];
            $dataImageHashes = [];
            foreach ($spans as $oneSpan) {
                // class, data-image-hash
                $classes[] = $oneSpan->getAttribute('class');
                $dataImageHashes[] = $oneSpan->getAttribute('data-image-hash');
            }

            $classes = array_unique($classes);
            $dataImageHashes = array_unique($dataImageHashes);

            // Länk runt allt.
            $replacementString = sprintf(
                '<a href="%5$s%2$s" class="%3$s"%4$s>%2$s</a>',
                $completeMatch,
                $pageNum,
                implode(' ', $classes),
                $this->withdebug ? sprintf(' data-image-hashes="%1$s"', implode(' ', $dataImageHashes)) : '', // 4
                $this->linkprefix // 5
            );

            return $replacementString;
        }, $line, -1, $numberReplacements);

        return $line;
    }

    public function linkifyURLsSingleLine($line, $lineIndex, &$numberReplacements = null)
    {
        // Länkar är oftast på sista raden, den med blå bakgrund,
        // så ta det säkra före det osäkra och länka endast saker på den raden.
        // if ($lineIndex !== 23) {
        //     return $line;
        // }

        // Länka "Läs mer på svtsport.se"
        // $line = preg_replace('|(Läs mer på svtsport\.se)|', '<a href="https://svtsport.se/" rel="noopener" target="_blank">\1</a>', $line);

        // Länka "nästa sida", t.ex. "Mer om pandemin på nästa sida".
        // "   "Där man hittar fienderna" nästa sida"
        $linkprefix = $this->linkprefix;
        $nextPagePageNum = $this->pageNum() + 1;
        $nextPageURL = $linkprefix . $nextPagePageNum;
        
        $line = str_replace(' nästa sida', ' <a rel="noopener" target="_blank" href="' . $nextPageURL . '">nästa sida</a>', $line);
        //$line = preg_replace('|nästa sida|', '<a href="' . $linkprefix . $nextPagePageNum . '" rel="noopener">\1</a>', $line);

        // Länka svtsport.se
        $line = str_replace('svtsport.se', '<a rel="noopener" target="_blank" href="https://svtsport.se/">svtsport.se</a>', $line);

        return $line;
    }

    /**
     * @param mixed $line 
     * @param mixed|null $numberReplacements 
     * @return mixed 
     */
    public function linkifySingleLine($line, &$numberReplacements = null)
    {
        $regexSingleNumber0 = '(0)';
        $regexSingleNumber1to9 = '([1-9])';
        $regexSingleNumber0to9 = '([0-9])';
        $regexPageNumber = $regexSingleNumber1to9 . $regexSingleNumber0to9 . $regexSingleNumber0to9;
        $linkprefix = $this->linkprefix;
        $replacement = '<a href="' . $linkprefix . '\1">\1</a>';

        // Lägg inte till länkar på börskurserna, 203-246.
        if ($this->pageNum() >= 203 && $this->pageNum() <= 246) {
            return $line;
        }
        
        // Länka inte "3 485 725 vaccindoser har getts i"
        $regex = '\b\d \d{3} \d{3} \b';
        if (preg_match('|' . $regex . '|', $line, $matches)) {
            return $line;
        }

        // Länka inte "Totalt har 14 151 personer som varit"
        $regex = '\b\d{2} [1-9][0-9]{2}\b';
        if (preg_match('|' . $regex . '|', $line, $matches)) {
            return $line;
        }

        // Länka inte sidnummer följt av siffror som inte är sidnummer,
        // t.ex. "dödsfall. Minst 226 000 människor i"
        $regexNumberRange = '\b[1-9][0-9]{2} 000\b';
        if (preg_match('|' . $regexNumberRange . '|', $line, $matches)) {
            return $line;
        }

        // Länka inte telefonnummer t.ex. på sid 800 UR "08-784 42 40"
        $regexNumberRange = '\b\d{2}-[1-9][0-9]{2}\b';
        if (preg_match('|' . $regexNumberRange . '|', $line, $matches)) {
            return $line;
        }

        // Länka inte "833 miljoner kronor samma period"
        $regexNumberRange = '\b[1-9][0-9]{2} miljoner\b';
        if (preg_match('|' . $regexNumberRange . '|', $line, $matches)) {
            return $line;
        }

        // Länka inte text som t.ex. börskurs   TELEKOMMUNIKATION       988.51  -0.10
        // Dvs tre siffror och sen en punkt och siffror igen.
        $regexNumberRange = '\b([1-9][0-9]{2}\.[0-9]{2})\b';
        if (preg_match('|' . $regexNumberRange . '|', $line, $matches)) {
            return $line;
        }

        // Länka inte ekonomi i korthet-börsresultat
        // t.ex. "28.812,6 (-0,8 procent)"
        $regexNumberRange = '\b([1-9][0-9]{2},\d)\b';
        if (preg_match('|' . $regexNumberRange . '|', $line, $matches)) {
            return $line;
        }

        // Intervall 110-114 _eller_ enkelt nummer 123
        // Matchar rader som innehåller både enkelt nummer och intervall, 
        // t.ex på sidan 330.
        $regexNumberRange = '(\b[1-9][0-9]{2}(?:-[1-9][0-9]{2})?\b)';
        $line = preg_replace('|' . $regexNumberRange . '|', $replacement, $line, -1, $count);
        if ($count) {
            return $line;
        }

        // Flersida 123f-234f
        $regexNumberRange = '\b([1-9][0-9]{2})f-([1-9][0-9]{2})f\b';
        $line = preg_replace('|' . $regexNumberRange . '|', '<a href="' . $linkprefix . '\1-\2">\1f-\2f</a>', $line, -1, $count);
        if ($count) return $line;

        // Flersida 123f
        $regexPageNumber = '\b([1-9][0-9]{2})f\b';
        $line = preg_replace('|' . $regexPageNumber . '|', '<a href="' . $linkprefix . '\1">\1f</a>', $line, -1, $count);
        if ($count) return $line;

        return $line;
    }

    protected function lineHasHeadlineChars($charsExtractor, $line, $lineIndex): bool
    {
        $currentLineHasHeadlineChars = false;

        $lineChars = mb_str_split($line);

        foreach ($lineChars as $charIndex => $char) {
            $charInfo = $charsExtractor->getChar($lineIndex, $charIndex);
            $isHeadlineChar = $charInfo['charType']['type'] === 'text' && $charInfo['charType']['scale'] === 2;
            if ($isHeadlineChar) {
                $currentLineHasHeadlineChars = true;
                break;
            }
        }

        return $currentLineHasHeadlineChars;
    }

    /**
     * Skapa länkar av alla nummer.
     */
    // public function addLinks(string $subPageText): string
    // {
    //     $addLinks = true;

    //     // Lägg inte till länkar på börskurserna, 203-246.
    //     if ($this->pageNum() >= 203 && $this->pageNum() <= 246) {
    //         $addLinks = false;
    //     }

    //     if (!$addLinks) {
    //         return $subPageText;
    //     }

    //     // Regexp som matchar 1-9 och sedan två valfria siffror, 
    //     // så den tar inte med 000 till och med 099 men 100 och framåt.
    //     $regexpNumerLargerThan99 = '[1-9]\d{2}';

    //     // "203-219" osv.
    //     $subPageText = preg_replace('/(\d{3}-\d{3})/', '<a href="/$1">$1</a>', $subPageText);
    //     // " 100 " osv.
    //     $subPageText = preg_replace('/ (' . $regexpNumerLargerThan99 . ') /', ' <a href="/$1">$1</a> ', $subPageText);
    //     // " 100" osv.
    //     $subPageText = preg_replace('/ (' . $regexpNumerLargerThan99 . ')\n/', " <a href=\"/\\1\">\\1</a>\n", $subPageText);
    //     // "100-" osv.
    //     $subPageText = preg_replace('/ (' . $regexpNumerLargerThan99 . ')-/', ' <a href="/$1">$1-</a>', $subPageText);
    //     // "...100 " osv.
    //     $subPageText = preg_replace('/\.\.(' . $regexpNumerLargerThan99 . ')/', '..<a href="/$1">$1</a>', $subPageText);
    //     // "417f" osv.
    //     $subPageText = preg_replace('/(' . $regexpNumerLargerThan99 . ')f/', '<a href="/$1">$1f</a>', $subPageText);
    //     // "530/" osv.
    //     $subPageText = preg_replace('/(' . $regexpNumerLargerThan99 . ')\//', '<a href="/$1">$1</a>/', $subPageText);
    //     // "Innehåll 700</span>" osv
    //     $subPageText = preg_replace('/ (' . $regexpNumerLargerThan99 . ')</', ' <a href="/$1">$1</a><', $subPageText);
    //     // "143,150" osv.
    //     $subPageText = preg_replace('/ (' . $regexpNumerLargerThan99 . ',' . $regexpNumerLargerThan99 . ') /', ' <a href="/$1">$1</a> ', $subPageText);

    //     // Ersätt "nästa sida" med länk till nästa sida.
    //     $subPageText = preg_replace('/ ((N|n)ästa sida) /', ' <a href="/' . ($this->pageNum() + 1) . '">$1</a> ', $subPageText);

    //     // Länkprefix
    //     $subPageText = str_replace(' href="/', " href=\"{$this->linkprefix}", $subPageText);

    //     return $subPageText;
    // }

    /**
     * På sidan 100 fixar vi olika färger på rubrikerna
     
     * @param array $subPageLines Alla rader på sidan som en array.
     * @return array Alla rader med <span> tillagd på varje rad med rubrik + efterföljande rubriker.
     */
    // public function findHeadlines(array $subPageLines): array
    // {
    //     // Baila om vi inte är på sidan 100.
    //     if ($this->pageNum() != 100) {
    //         return $subPageLines;
    //     }

    //     // På sidan 100 fixar vi olika färger på rubrikerna
    //     // Strunta i rad 1-3 och sista raderna som är meta

    //     // Nyhet 1: Y DH på första raden
    //     //        : Y på övriga rader
    //     // Nyhet 2: C på alla rader (kanske borde vara DH också?)

    //     /*
    //         Metod:
    //         Rad där text finns men inget sidnummer = troligtvis rubrik
    //         Om rad efter är tom
    //         Och raden därefter har text men inget sidnummer
    //         Och raden därefter har sidnummer
    //         Gruppera ihop dom, dvs. ta bort tomma raden

    //         Exempel på utseende:

    //         ----------

    //         Nu börjar 70-åringar att vaccineras  

    //         Gick snabbare än planerat i Stockholm 
    //         106 

    //                 Biden vidtar åtgärder         
    //                 mot vapenvåldet i USA         
    //                         135                  

    //         Novus: Ingen ljusning för Liberalerna 

    //         Små förändringar i ny opinionsmätning 
    //         112/160 

    //         Idrottsarenor i fransk covidkamp 130             

    //         ----------

    //         100 SVT Text fredag 09 apr 2021      



    //         SMHI-varning för snö och hård vind   

    //         117 

    //             Produktionsfel hos Janssen -       
    //             85 proc färre doser till USA       
    //                         131                  

    //         Böter för Solberg som bröt mot regler 

    //         Statsministern deltog i större sällskap
    //         130 

    //         USA: Ökad rysk närvaro vid Ukraina 136 

    //             Inrikes 101 Utrikes 104 Innehåll 700


    //         ----------

    //         100 SVT Text fredag 09 apr 2021      


    //         Brittiske prinsen Philip har avlidit 

    //         Drottning Elizabeths make blev 99 år 
    //         135-136 

    //                 Hiphoplegendaren DMX          
    //                 är död - blev 50 år          
    //                         150                   

    //         Flest rapporter om Astra-biverkningar 

    //         Tros bero på medvetenhet hos gruppen  
    //         107 

    //             Uefa: Blir publik under EM - 300   

    //             Inrikes 101 Utrikes 104 Innehåll 700

    //            ----------                         




    //         */
    //     // Rad att börja leta på. Först på rad 6 pga överst är text tv-loggan.
    //     $startLine = 6;

    //     // Antal rader att leta efter rubrik på. Raden längst ner på startsidan är nav så den ska skippas.
    //     $linesCount = 15;

    //     // start

    //     // Array som med rader att slutligen leta efter rubriker i.
    //     $linesToLookForHeadlinesIn = array_slice($subPageLines, $startLine, $linesCount);

    //     // Multi dimensionell array med alla hittade rubriker, med stöd för flera rader per rubrik.
    //     $foundHeadlines = $this->createHeadlinesMultiArray($linesToLookForHeadlinesIn);

    //     // Lägg till Y eller C.
    //     $foundHeadlines = array_map(function ($oneFoundHeadline, $index) {
    //         $color = $index % 2 ? 'C' : 'Y';
    //         $oneFoundHeadline = array_map(function ($oneFoundHeadlineLine) use ($color) {
    //             return "<span class='{$color}'>{$oneFoundHeadlineLine}</span>";
    //         }, $oneFoundHeadline);

    //         return $oneFoundHeadline;
    //     }, $foundHeadlines, array_keys($foundHeadlines));

    //     // Skapa ny lines-array med alla hittade rubriker.
    //     $emptyLine = str_pad('', 40, ' ');
    //     $linesWithHeadlines = [];
    //     foreach ($foundHeadlines as $oneFoundHeadline) {
    //         array_push($linesWithHeadlines, $emptyLine, ...$oneFoundHeadline);
    //     }

    //     // Ta bort första raden pga tom.
    //     $linesWithHeadlines = array_slice($linesWithHeadlines, 1);

    //     // Se till att nya arrayen har lika många element som den ursprungliga.
    //     if (count($linesWithHeadlines) !== $linesCount) {
    //         $linesWithHeadlines = array_pad($linesWithHeadlines, $linesCount, $emptyLine);
    //     }

    //     array_splice($subPageLines, $startLine, $linesCount, $linesWithHeadlines);

    //     return $subPageLines;
    // }

    /**
     * Skapa multidimensional array med hittade rubriker från raderna som skickats in.
     * 
     * @param mixed $linesToLookForHeadlinesIn Array med rader
     * @return array Multidimensional array med rubriker
     */
    // public function createHeadlinesMultiArray($linesToLookForHeadlinesIn): array
    // {
    //     // Ta bort tomma rader.
    //     $linesToLookForHeadlinesIn = array_filter($linesToLookForHeadlinesIn, function ($line) {
    //         return !empty(trim($line));
    //     });

    //     // Indexera om.
    //     $linesToLookForHeadlinesIn = array_values($linesToLookForHeadlinesIn);

    //     // Hitta rubrik från första raden och framåt, stoppa när nästa rubrik börjar.
    //     $currentFoundHeadline = [];
    //     for ($i = 0; $i < count($linesToLookForHeadlinesIn); $i++) {
    //         $lineNum = $i;
    //         $line = $linesToLookForHeadlinesIn[$lineNum];
    //         $trimmedLine = trim($linesToLookForHeadlinesIn[$lineNum]);

    //         // Rubriken slutar när:
    //         // - en rad består av siffror, t.ex. "131" eller "112/160 ", "135-136 "
    //         // - en rad har siffror sist, t.ex. "Uefa: Blir publik under EM - 300   ", "Idrottsarenor i fransk covidkamp 130"
    //         // - en rad har siffror sist men har ett minusstreck också, t.ex. 'Ryssland - Kreml varnar 135-',
    //         // - se upp för rader med siffror som inte är nummer, t.ex. "Drottning Elizabeths make blev 99 år "
    //         // - se upp för rader som slutar med tre siffror men som är del av nummer, t.ex. '27 nya corona-dödsfall - totalt 13 788'.
    //         $lineIsSingleNumber = is_numeric($trimmedLine);
    //         $lineIsNumberRange = (bool) preg_match('/^\d{3}[\/\-]\d{3}$/', $trimmedLine);
    //         $lineEndsWithNumber = (bool) preg_match('/\d{3}-?$/', $trimmedLine);
    //         $lineEndsWithNumberThatProbablyNotIsPageNumber = (bool) preg_match('/ \d+ \d{3}/', $trimmedLine);

    //         $isEndOfHeadline = ($lineIsSingleNumber || $lineIsNumberRange || $lineEndsWithNumber) && !$lineEndsWithNumberThatProbablyNotIsPageNumber;

    //         $currentFoundHeadline[] = $line;

    //         if ($isEndOfHeadline) {
    //             $foundHeadlines[] = $currentFoundHeadline;
    //             $currentFoundHeadline = [];
    //         }
    //     }

    //     return $foundHeadlines;
    // }

    public function pageObject(): object
    {
        return $this->pageObject;
    }

    /**
     * Formattera alla undersidor så de är
     * 40 tecken breda ↔ och 24 rader höga ↕
     * och ta bort lite skräp, typ random tabs som är i början.
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
                'pageIsBroadcasted' => false,
                'altText' => "{$this->pageNum()} SVT Text   Sidan ej i sändning" . str_repeat("\n", 23)
            ]);
        }

        // Rensa upp varje undersida.
        foreach ($subPages as $subpage) {
            $pageAsText = $subpage->altText;

            // var_dump($pageAsText);
            // 7 juni 2021 är denna
            // string(655)

            // Ta bort "\n\n\t\t" som verkar vara överst på varje sida.
            // $pageAsText = str_replace("\n\n\t\t", '', $pageAsText);
            // Sedan 7 juni 2021 verkar det som att tabbarna inte är kvar
            $pageAsText = preg_replace('|^\n\n|', "", $pageAsText);

            // Skapa collection med alla rader.
            $pageLines = collect(explode("\n", $pageAsText));

            // Behåll endast de första 24 raderna.
            $pageLines->splice(24);

            // Se till att för korta rader blir 40 rader
            // genom att lägga till mellanslag
            // före och efter omvartannat.
            $pageLines->transform(function ($line, $key) {

                if ($this->pageNum() == 100 && mb_strlen($line) < 40) {
                    // Sedan sommar 2023 så är inte sifforna under rubrikerna på startsidan centerade,
                    // så vi lägger till ett extra mellanslag i början av raden.
                    $line = ' ' . $line;
                }

                while (mb_strlen($line) < 40) {
                    if ($this->pageNum() == 100) {
                        // På båda sidorna på startsidan för att centrera.
                        if (mb_strlen($line) % 2) {
                            $line = ' ' . $line;
                        } else {
                            $line = $line . ' ';
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
                'text' => $pageLines->join("\n"),
                'gifAsBase64' => $subpage->gifAsBase64 ?? null,
                'pageIsBroadcasted' => $subpage->pageIsBroadcasted ?? true
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

    public function withdebug(bool $bool = true)
    {
        $this->withdebug = $bool;

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

        // "I korthet"-sidorna har under-sidnummer "1/3" osv uppe i högra hörnet
        // så skippa den raden.
        if (in_array($this->pageNum(), [127, 128])) {
            $pageLines = array_slice($pageLines, 1);
        }

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

    protected function mb_str_pad($str, $pad_len, $pad_str = ' ', $dir = STR_PAD_RIGHT, $encoding = NULL)
    {
        $encoding = $encoding === NULL ? mb_internal_encoding() : $encoding;
        $padBefore = $dir === STR_PAD_BOTH || $dir === STR_PAD_LEFT;
        $padAfter = $dir === STR_PAD_BOTH || $dir === STR_PAD_RIGHT;
        $pad_len -= mb_strlen($str, $encoding);
        $targetLen = $padBefore && $padAfter ? $pad_len / 2 : $pad_len;
        $strToRepeatLen = mb_strlen($pad_str, $encoding);
        $repeatTimes = ceil($targetLen / $strToRepeatLen);
        $repeatedString = str_repeat($pad_str, max(0, $repeatTimes)); // safe if used with valid unicode sequences (any charset)
        $before = $padBefore ? mb_substr($repeatedString, 0, floor($targetLen), $encoding) : '';
        $after = $padAfter ? mb_substr($repeatedString, 0, ceil($targetLen), $encoding) : '';
        return $before . $str . $after;
    }
}
