<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
            echo "Line: " . str_pad($rownum, 2, "0", STR_PAD_LEFT) . " ";

            $arrChars['rows'][$rownum] = [
                'cols' => []
            ];

            for ($colnum = 0; $colnum < SELF::NUM_COLS; $colnum++) {
                $arrChars['rows'][$rownum]['cols'][$colnum] = [];

                // Börja hämta färger i denna ruta.
                $charImage = $this->getCharImage($this->image, $rownum, $colnum);
                $charColors = $this->getCharColors($charImage);
                $arrChars['rows'][$rownum]['cols'][$colnum]['charColors'] = $charColors;
                $arrChars['rows'][$rownum]['cols'][$colnum]['charAsImageResource'] = $charImage;
                $arrChars['rows'][$rownum]['cols'][$colnum]['charAsImgTag'] = $this->gdImgToHTML($charImage, print_r($charColors, true));

                echo $arrChars['rows'][$rownum]['cols'][$colnum]['charAsImgTag'] . " ";
            }

            echo "<hr>";
        }

        $this->arrChars = $arrChars;
        
        return $this;
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

    public function getChars() {
        return $this->arrChars;
    }
}

class PageColors extends Controller
{

    public function index(Request $request)
    {
        $testPagesDir = base_path('tests/TestPages');
        $imagePathAndName = $testPagesDir . '/377.gif';
        #$imagePathAndName = $testPagesDir . '/300.gif';
        #$imagePathAndName = $testPagesDir . '/100.gif';

        $charsExtractor = new TeletextCharsExtractor;
        $charsExtractor->loadImage($imagePathAndName)->parseImage();

        echo "<pre>" . print_r($charsExtractor->getChars(), 1) . "</pre>";
    }
}
