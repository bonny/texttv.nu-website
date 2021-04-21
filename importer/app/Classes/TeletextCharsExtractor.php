<?php

namespace App\Classes;

use Illuminate\Support\Facades\Storage;

class TeletextCharsExtractor
{
    const NUM_PIXELS_X_FOR_ONE_CHAR = 13;
    const NUM_PIXELS_Y_FOR_ONE_CHAR = 16;

    // Antal kolumner med tecken.
    const NUM_COLS = 40;

    // Antal rader med tecken.
    // 25 rader pga en tom svart rad verkar allid vara sist.
    const NUM_ROWS = 25;

    // Image resource.
    protected $image;

    // Array med alla tecken och dess färger.
    protected $arrChars = [];

    public function imageFromString(string $imageString): object
    {
        $this->image = imagecreatefromstring($imageString);
        return $this;
    }

    public function loadImage(string $imagePathAndName): object
    {
        $this->image = imagecreatefromgif($imagePathAndName);

        // $imageWidth = imagesx($this->image);
        // $imageHeight = imagesy($this->image);

        // $numPixelsXForOneChar = $imageWidth / SELF::NUM_COLS;
        // $numPixelsYForOneChar = $imageHeight / SELF::NUM_ROWS;

        #echo $this->gdImgToHTML($image);
        #echo "<br>{$imagePathAndName}<br>";

        #echo "<br>Bredd: {$imageWidth} px";
        #echo "<br>Höjd: {$imageHeight} px";
        #echo "<br>Ett tecken: bredd {$numPixelsXForOneChar} px, höjd {$numPixelsYForOneChar} px";
        #echo "<br>";

        return $this;
    }

    public function parseImage()
    {
        // Array med alla tecken på alla rader och kolumner och dess färger.
        $arrChars = [
            'rows' => []
        ];

        for ($rownum = 0; $rownum < SELF::NUM_ROWS; $rownum++) {
            $arrChars['rows'][$rownum] = [
                'cols' => []
            ];

            for ($colnum = 0; $colnum < SELF::NUM_COLS; $colnum++) {
                $arrChars['rows'][$rownum]['cols'][$colnum] = [];

                // Börja hämta färger i denna ruta.
                $charImage = $this->getCharImage($this->image, $rownum, $colnum);
                $charColors = $this->getCharColors($charImage);
                $charImageHash = $this->getCharImageHash($charImage);

                $this->saveCharImageToDisk($charImage);

                $charType = $this->getCharType($charImage);

                $inlineImageTitle = array_merge(
                    [
                        'hash' => $charImageHash,
                        'charType' => $charType
                    ],
                    $charColors,
                );
                $arrChars['rows'][$rownum]['cols'][$colnum]['charColors'] = $charColors;
                $arrChars['rows'][$rownum]['cols'][$colnum]['charAsImageResource'] = $charImage;
                $arrChars['rows'][$rownum]['cols'][$colnum]['charAsImgTag'] = $this->gdImgToHTML($charImage, print_r($inlineImageTitle, true));
            }
        }

        $this->arrChars = $arrChars;

        return $this;
    }

    protected function getCharType($charImage)
    {
        $charType = [
            'type' => "text", // "text" | "image"
            'scale' => 1, // 1 for normal text, 2 for headlines that are double height
        ];

        // Array med alla hash på alla tecken som är bilder/grafik.
        $arrCharImagesHashes = [
            2335531887,
            2413702233,
            693852549,
            2754943555,
            2327991958,
            2030688620,
            2862847544,
            167497510,
            4249453864,
            282174899,
            1760051201,
            2681114375,
            3987931972,
            2308811616,
            3298983629,
            3771534768,
            2218724507,
            1254105466,
            3288266310,
            4166044020,
            4166044020,
            3188198897,
            3618463797,
            4166044020,
            2881270998,
            1739010369,
            3221500607,
            2790421332,
            925899746,
            3771534768,
            2140796170,
            3785335171,
            999369151,
            3965831124,
            3838981461,
            610948841,
            1118560998,
            3772511681,
            2509998914,
            739691859,
            1091112751,
            1840924899,
            207576990,
            2296503594,
            1028566380,
            207576990,
            299620102,
            3896730824,
            4082209591,
            1074033251,
            2353048447,

        ];

        $imageHash = $this->getCharImageHash($charImage);

        if (in_array($imageHash, $arrCharImagesHashes)) {
            $charType['type'] = 'image';
        }

        // Array med alla hash för tecken som är rubriker, dvs. på två rader.
        $arrCharHeadlineHashes = [
            723198051,
            3520683490,
            582561295,
            935983407,
            3520683490,
            1341498726,
            3737055915,
            3762506443,
            3520683490,
            935983407,
            720053815,
            3298917018,
            3737055915,
            3762506443,
            720053815,
            3298917018,
            1341498726,
            1785992115,
            3764076653,
            3737055915,
            3298917018,
            935983407,
            2344711873,
            3616584483,
            1341498726,
            1122726988,
            1726347625,
            430848581,
            937565482,
            1122726988,
            2666759697,
            1726347625,
            2775073587,
            1122726988,
            2283837502,
            2710017871,
            1290552089,
            1122726988,
            2283837502,
            3876640985,
            3079503600,
            2710017871,
            1122726988,
            3079503600,
            937565482,
            3079503600,
            3165898618,
            3768925934,
            1341498726,
            3520683490,
            935983407,
            3635874921,
            3764076653,
            1341498726,
            720053815,
            3520116403,
            272235641,
            582561295,
            3616584483,
            935983407,
            582561295,
            3520683490,
            787031178,
            3635874921,
            2312578866,
            582561295,
            787031178,
            3520683490,
            849669545,
            935983407,
            3298917018,
            3831125202,
            2730324097,
            3768925934,
            1341498726,
            1681709246,
            1122726988,
            3896799484,
            1429588631,
            1122726988,
            937565482,
            1726347625,
            3079503600,
            7738894,
            3752530561,
            1681709246,
            2126217295,
            3079503600,
            7738894,
            3752530561,
            937565482,
            1726347625,
            3752530561,
            890717644,
            1662056332,
            2283837502,
            1122726988,
            1726347625,
            2126217295,
            3079503600,
            1122726988,
            1681709246,
            254293829,
        ];

        if (in_array($imageHash, $arrCharHeadlineHashes)) {
            $charType['scale'] = 2;
        }

        return $charType;
    }

    protected function getCharImageHash($charImage)
    {
        $charString = $this->getCharImageString($charImage);
        $charHash = crc32($charString);
        return $charHash;
    }

    protected function getCharImageString($charImage)
    {
        ob_start();
        imagegif($charImage);
        $charString = ob_get_clean();
        return $charString;
    }

    protected function saveCharImageToDisk($charImage)
    {
        $charHash = $this->getCharImageHash($charImage);
        $charString = $this->getCharImageString($charImage);

        // Spara ner bild om den inte finns.
        $charFilename = "public/chars/{$charHash}.gif";
        if (!Storage::disk('local')->exists($charFilename)) {
            Storage::disk('local')->put($charFilename, $charString);
        }
    }

    public function getImageDebugHtml(): string
    {
        ob_start();

        foreach ($this->arrChars['rows'] as $rowNum => $row) {
            echo "Row " . str_pad($rowNum, 2, "0", STR_PAD_LEFT) . ": ";

            foreach ($row['cols'] as $colIndex => $col) {
                echo $col['charAsImgTag'] . " ";
            }

            echo "<hr>";
        }

        return ob_get_clean();
    }

    // Creates an HTML Img Tag with Base64 Image Data
    // https://stackoverflow.com/questions/22266402/how-to-encode-an-image-resource-to-base64
    public function gdImgToHTML($gdImg, string $tooltip = '')
    {
        ob_start();
        imagegif($gdImg);
        $image_data = ob_get_clean();

        return "<img style='image-rendering:pixelated' src='data:image/gif;base64," . base64_encode($image_data) . "' title='" . htmlentities($tooltip) . "'>";
    }

    /**
     * Hämta bakgrundsfärg och textfärg för ett tecken.
     * 
     * @param mixed $imageSingleChar Image resource som innehåller ett tecken.
     * @return array
     */
    protected function getCharColors($imageSingleChar): array
    {
        // Array med hittade färger
        $colors = [
            'background' => null,
            'text' => null
        ];

        // Leta upp färger i den utklippta bilden som är ett tecken stor.
        // Första pixeln x=0 y=0 är bör vara bakgrundsfärgen.
        $firstPixelColor = imagecolorat($imageSingleChar, 0, 0);
        $firstPixelRed = ($firstPixelColor >> 16) & 0xFF;
        $firstPixelGreen = ($firstPixelColor >> 8) & 0xFF;
        $firstPixelBlue = $firstPixelColor & 0xFF;

        $colors['background'] = [
            'r' => $firstPixelRed,
            'g' => $firstPixelGreen,
            'b' => $firstPixelBlue
        ];

        // Andra hittade färgen bör vara textens färg.
        for ($pixelY = 0; $pixelY < SELF::NUM_PIXELS_Y_FOR_ONE_CHAR; $pixelY++) {
            for ($pixelX = 0; $pixelX < SELF::NUM_PIXELS_X_FOR_ONE_CHAR; $pixelX++) {
                $loopPixelColor = imagecolorat($imageSingleChar, $pixelX, $pixelY);

                // Om färg på loopad pixel är annan än bakgrundsfärg = lägg till i array och avsluta loopar.
                if ($firstPixelColor !== $loopPixelColor) {
                    $loopPixelRed = ($loopPixelColor >> 16) & 0xFF;
                    $loopPixelGreen = ($loopPixelColor >> 8) & 0xFF;
                    $loopPixelBlue = $loopPixelColor & 0xFF;

                    $colors['text'] = [
                        'r' => $loopPixelRed,
                        'g' => $loopPixelGreen,
                        'b' => $loopPixelBlue
                    ];

                    // Hoppa ut ur båda looparna nu när vi hittat andra färgen.
                    break 2;
                }
            }
        }

        // Översätt färger till classnamn för frontend/html.
        // SVT Text har 8 färger.
        $backgrounds = [
            'black' => ['r' => 0, 'g' => 0, 'b' => 0], // Svart
            'bgR' => ['r' => 255, 'g' => 0, 'b' => 0], // Röd
            'bgM' => ['r' => 255, 'g' => 0, 'b' => 255], // Magenta (lila)
            'bgB' => ['r' => 0, 'g' => 0, 'b' => 255], // Blå (mörkblå)
            'bgC' => ['r' => 0, 'g' => 255, 'b' => 255], // Cyan (ljusblå)
            'bgG' => ['r' => 0, 'g' => 255, 'b' => 0], // Grön
            'bgY' => ['r' => 255, 'g' => 255, 'b' => 0], // Gul
            'bgW' => ['r' => 255, 'g' => 255, 'b' => 255], // Vit
        ];

        $texts = [
            'black' => ['r' => 0, 'g' => 0, 'b' => 0], // Svart
            'R' => ['r' => 255, 'g' => 0, 'b' => 0], // Röd
            'M' => ['r' => 255, 'g' => 0, 'b' => 255], // Magenta (lila)
            'B' => ['r' => 0, 'g' => 0, 'b' => 255], // Blå (mörkblå)
            'C' => ['r' => 0, 'g' => 255, 'b' => 255], // Cyan (ljusblå)
            'G' => ['r' => 0, 'g' => 255, 'b' => 0], // Grön
            'Y' => ['r' => 255, 'g' => 255, 'b' => 0], // Gul
            'W' => ['r' => 255, 'g' => 255, 'b' => 255], // Vit
        ];

        $foundBackgroundCSS = null;
        $foundTextCSS = null;

        foreach ($backgrounds as $backgroundClass => $backgroundRGB) {
            if ($backgroundRGB == $colors['background']) {
                $foundBackgroundCSS = $backgroundClass;
                break;
            }
        }

        if (empty($colors['text'])) {
            $foundTextCSS = '';
        } else {
            foreach ($texts as $textClass => $textRGB) {
                if ($textRGB == $colors['text']) {
                    $foundTextCSS = $textClass;
                    break;
                }
            }
        }

        // @TODO: catch these somehow.
        if (is_null($foundBackgroundCSS)) {
            dump('Not found backgroundCSS', $colors);
        }

        if (is_null($foundTextCSS)) {
            dump('Not found foundTextCSS', $colors);
        }

        $colors['backgroundClass'] = $foundBackgroundCSS;
        $colors['textClass'] = $foundTextCSS;

        return $colors;
    }

    /**
     * Klipp ut bokstaven på plats $line, $row
     * 
     * @param mixed $srcImage 
     * @param mixed $line 
     * @param mixed $row 
     * @return resource|false 
     */
    protected function getCharImage($srcImage, $line, $col)
    {
        $imageCropped = imagecreatetruecolor(SELF::NUM_PIXELS_X_FOR_ONE_CHAR, SELF::NUM_PIXELS_Y_FOR_ONE_CHAR);
        $charStartY = $line * SELF::NUM_PIXELS_Y_FOR_ONE_CHAR;
        $charStartX = $col * SELF::NUM_PIXELS_X_FOR_ONE_CHAR;
        imagecopy($imageCropped, $srcImage, 0, 0, $charStartX, $charStartY, SELF::NUM_PIXELS_X_FOR_ONE_CHAR, SELF::NUM_PIXELS_Y_FOR_ONE_CHAR);
        return $imageCropped;
    }

    public function getChars()
    {
        return $this->arrChars;
    }

    /**
     * Ger en char på en viss plats.
     * 
     * @param mixed $row 
     * @param mixed $col 
     * @return array
     */
    public function getChar($row, $col): ?array
    {
        if (!isset($this->getChars()['rows'][$row]['cols'][$col])) {
            return null;
        }

        return $this->getChars()['rows'][$row]['cols'][$col];
    }
}
