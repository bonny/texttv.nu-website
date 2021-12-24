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

    // Array som fungerar som cache för alla charImageStrings
    protected $charImageStrings = [];
    protected $charImageHashes = [];

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

                $arrChars['rows'][$rownum]['cols'][$colnum]['charColors'] = $charColors;
                $arrChars['rows'][$rownum]['cols'][$colnum]['charAsImageResource'] = $charImage;

                // $inlineImageTitle = array_merge(
                //     [
                //         'hash' => $charImageHash,
                //         'charType' => $charType
                //     ],
                //     $charColors,
                // );
                // $arrChars['rows'][$rownum]['cols'][$colnum]['charAsImgTag'] = $this->gdImgToHTML($charImage, print_r($inlineImageTitle, true));

                $arrChars['rows'][$rownum]['cols'][$colnum]['charType'] = $charType;
                $arrChars['rows'][$rownum]['cols'][$colnum]['charImageHash'] = $charImageHash;
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
        $arrCharImagesHashes = $this->getCharImagesHashes();
        $imageHash = $this->getCharImageHash($charImage);

        if (in_array($imageHash, $arrCharImagesHashes)) {
            $charType['type'] = 'image';
        }

        // Array med alla hash för tecken som är rubriker, dvs. på två rader.
        $arrCharHeadlineHashes = $this->getCharHeadlineHashes();

        if (in_array($imageHash, $arrCharHeadlineHashes)) {
            $charType['scale'] = 2;
        }

        return $charType;
    }

    /**
     * Image-idn som är bilder, dvs. tecken som ska renderas med bild istället för text.
     * 
     * @return array IDn.
     */
    protected function getCharImagesHashes(): array
    {
        return [
            3319954521,
            738808167,
            2989908589,
            2713208341,
            1836416647,
            1831812200,
            1647481452,
            2786643627,
            2660334971,
            3566168026,
            129924956,
            1667839835,
            527607552,
            1269537316,
            1019538401,
            993997339,
            2687285255,
            2775843888,
            3224119740,
            2961924677,
            3080494193,
            2088812280,
            188354112,
            108941892,
            2395577122,
            1177666582,
            2502790502,
            3834148794,
            266539630,
            673123783,
            3070853333,
            3607886079,
            2384946088,
            257608800,
            // 2939094043,
            1756192437,
            1815114552,
            1460540445,
            251408512,
            2642197907,
            2156528839,
            3352595016,
            2190446388,
            2913233310,
            3215696164,
            3387636925,
            1994053858,
            3806973766,
            3826504151,
            1250598021,
            1339760422,
            4050100045,
            1665957495,
            2185071352,
            1685294852,
            4098534857,
            3138777730,
            1954418500,
            2537420265,
            3585010416,
            3287848953,
            1326555685,
            2287478073,
            1625865678,
            2934086162,
            1219799629,
            2693613557,
            3037313580,
            3150678580,
            3609107780,
            1164105659,
            2796428508,
            880409429,
            3547727352,
            1559180511,
            3931275958,
            872158518,
            1087885570,
            872158518,
            3748546328,
            1083020269,
            225196657,
            2042617672,
            4244846807,
            1270603014,
            3147580979,
            15963642,
            2015754887,
            750680978,
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
            3016921583,
            1616593087,
            221339736,
            2358923843,
            2057975240,
            3528249918,
            805032547,
            2008313116,
            2629896177,
            2134843665,
            2744419629,
            4225448530,
            3528249918,
            805032547,
            2008313116,
            2629896177,
            2134843665,
            2744419629,
            1821055520,
            3716099765,
            2743050115,
            2543321479,
            2277613462,
            1975942794,
            1933655036,
            2603084336,
            1147315705,
            1797582585,
            1624186756,
            1884194565,
            1194524717,
            1320042503,
            3204126491,
            960280798,
            2798274294,
            2730368976,
            1736705319,
            2675030319,
            4160041504,
            1542200632,
            1930392114,
            2394446855,
            3081856825,
            2193421014,
            2787425916,
            1920688978,
            3449995952,
            2926439270,
            3935762314,
            2701660337,
            3761439747,
            3725622489,
            403659021,
            1447212811,
            2943087594,
            1236924557,
            1221581925,
            2290507151,
            2783216268,
            3659674396,
            1930330539,
            233848696,
            2970395065,
            3358919276,
            718202404,
            3761332792,
            1023672962,
            882459304,
            1105030704,
            2615209433,
            1790337676,
            380498647,
            2989999960,
            2612703030,
            654911908,
            3375711243,
            1401072790,
            2464970422,
            1429748213,
            600227064,
            644889415,
            1056054768,
            2643387802,
            945926046,
            945926046,
            3270854724,
            3625981591,
            2144172243,
            2964044975,
            1460303617,
            2594562150,
            2762748738,
            2267014944,
            2201328430,
            1227236920,
            3713433556,
            723504262,
            294742777,
            100967844,
            3446440713,
            692512409,
            2778777683,
            4163818496,
            3297818940,
            1467273300,
            3853147288,
            2149140494,
            134726465,
            140345774,
            663900403,
            3004441414,
            1141697903,
            2362982173,
            3858684646,
            3782488817,
            1386252573,
            1094073264,
            3355185703,
            1481867985,
            2282957060,
            21344552,
            4082487737
        ];
    }

    protected function getCharHeadlineHashes(): array
    {
        return [
            1992962736,
            1053170101,
            4015340740,
            2731520461,
            3112015649,
            3809356127,
            3051525322,
            2782071755,
            3888479418,
            392443663,
            2238730591,
            407997093,
            2141324460,
            2186440670,
            2446539012,
            780983942,
            324321704,
            1806620080,
            1805582585,
            3971441540,
            4174807032,
            3388974340,
            713735889,
            3341996026,
            1918947735,
            1997801134,
            1111197315,
            762988838,
            569589551,
            1482993587,
            2316665486,
            1346922252,
            4108141168,
            4279042345,
            3343009095,
            207979164,
            684526819,
            421373412,
            3440866457,
            2503059413,
            3078616209,
            2503059413,
            1637741094,
            1945931269,
            3343009095,
            207979164,
            684526819,
            421373412,
            3440866457,
            2503059413,
            3078616209,
            43320099,
            4108141168,
            4279042345,
            4279042345,
            421373412,
            272190017,
            2391218961,
            2469085609,
            3078616209,
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
            439669078,
            2266911817,
            114641366,
            325157899,
            2311859109,
            3650759472,
            1014515434,
            126705307,
            3021747017,
            2405914258,
            442579546,
            1729492089,
            3942864794,
            2489444580,
            785344771,
            2202951568,
            3694158977,
            2416430922,
            442579546,
            1729492089,
            881481121,
            2953430550,
            2299989217,
            2796899421,
            2001334168,
            3999097545,
            1723919978,
            2100568880,
            1073508324,
            4235018692,
            859426903,
            4015340740,
            259538547,
            1565275160,
            3770063991,
            2271126315,
            201319298,
            1011249928,
            2628887726,
            4259143373,
            483877535,
            2542956617,
            3927753545,
            3739044171,
            3119628988,
            1127615366,
            2087264792,
            887345456,
            2432844055,
            1694747849,
            2929510255,
            3953160183,
            4142105936,
            2731331050,
            2642708586,
            1886353382,
            2785681130,
            3244059249,
            1717059433,
            3470866127,
            185906176,
            1910324931,
            3049556877,
            469556350,
            2219664598,
            3543671928,
            1058814832,
            4205450727,
            2244135246,
            3712528637,
            2296814053,
            4211757947,
            1617862300,
            3035734731,
            1390478854,
            384558345,
            4211757947,
            2813912065,
            3781463408,
            199417364,
            3031077332,
            3601935473,
            2813912065,
            3639813933,
            2916478386,
            3191046883,
            3931723043,
            2931572307,
            849966330,
            2097281845,
            1327428324,
            952318677,
            4192958451,
            2563987060,
            1416977576,
            3547554095,
            1874895914,
            3521102940,
            895346607,
            2915513551,
            2935869999,
            4294164655,
            1904182420,
            438412212,
            1194829474,
            1265085479,
            1600522987,
            160993911,
            3398362643,
            1042775382,
            2995647847,
            4280137432,
            1358149415,
            2159652145,
            1595788568,
            2415091364,
            735912867,
            3804222925,
            947168657,
            1292251464,
            2747121109,
            551043498,
            2547594526,
            3659542044,
            74088618,
            3975783157,
            281360322,
            1736719418,
            4247701582,
            2729083265,
            3841850033,
            3533693828,
            2916360360,
            1508477399,
            3635540608,
            1736986778,
            2697837920,
            2916360360,
            1291613169,
            3441749559,
            2567524257,
            2132405100,
            2055302023,
            1291613169,
            2255209959,
            4159339037,
            1367731437,
            1967841568,
            653549981,
            3013412373,
            794592520,
            1224417699,
            64403396,
            260488513,
            2118681359,
            3970205467,
            1694026321,
            3676006458,
            2476964995,
            3395326052,
            3406974914,
            2403862239,
            2791228587,
            4270168933,
            449117189,
            101344554,
            2632024301,
            2822382628,
            2529205097,
            3897952130,
            3469647148,
            2125794144,
            2788806745,
            1029032354,
            4094718878,
            2573137052,
            2983149499,
            3369041186,
            2692732120,
            2118042592,
            265864467,
            1474507444,
            3041921931,
            2052158641,
            3917597520,
            265864467,
            1098561911,
            1818323368,
            3538842457,
            889116564,
            3390332676,
            1098561911,
            1364710489,
            858720062,
            2973737569,
            756928611,
            2979475826,
            3443148690,
            3811168706,
            3235514878,
            4216421493,
            433603144,
            62876047,
            2092734790,
            2009827948,
            2686490562,
            3910174467,
            1661597233,
            2220625002,
            629237240,
            2295711755,
            1096084389,
            1889562627,
            2833538194,
            551286420,
            2501298109,
            2307920803,
            2465528718,
            1663546433,
            1682071952,
            2968609528,
            1023496740,
            3906508681,
            1817860190,
            1238676757,
            2917722969,
            2843676080,
            1991366113,
            2426499372,
            791169640,
            2917722969,
            987746950,
            1262081141,
            1654526984,
            1559980614,
            2674745821,
            4196945250,
            1262081141,
            364299583,
            4130431782,
            1409529613,
            2307454239,
            2035534196,
            2312853236,
            1270543829,
            2083980450,
            2799733720,
            2162270483,
            4099270132,
            2880412564,
            3098138097,
            3226484935,
            12314318,
            2251678557,
            1844932007,
            78638108,
            3181964983,
            95812291,
            2510054767,
            1612752020,
            2043436710,
            830726849,
            3446735557,
            2009657058,
            2883800135,
            1363101484,
            3924216522,
            782816278,
            3718623541,
            2765364854,
            221619315,
            596262463,
            1639708086,
            1696093139,
            2344447764,
            596262463,
            1674082996,
            305036359,
            4175643964,
            404388640,
            1006126241,
            327363759,
            305036359,
            1285647629,
            1056283424,
            4036399729,
            1090958135,
            2624576536,
            3503095494,
            2968500534,
            718309917,
            342422768,
            1726651076,
            1321797939,
            2375129059,
            326527754,
            3097366496,
            4008922588,
            3439367648,
            2537288443,
            2922275522,
            2898206215,
            3410672076,
            463537378,
            3783894994,
            822689811,
            2233250296,
            1286699034,
            4178420969,
            2489756407,
            3546139550,
            1862084703,
            136900382,
            2902973356,
            2331472558,
            2226264327,
            4274954,
            3835610558,
            3474484241,
            2762314158,
            3242718571,
            660033958,
            1853979256,
            3474484241,
            657026002,
            1456434465,
            3449709440,
            4054915821,
            3737407949,
            2796189,
            1456434465,
            138443883,
            4226547512,
            363497245,
            3851654731,
            1420502046,
            2486038432,
            1049455696,
            964234287,
            4177176286,
            3266657404,
            2249060661,
            3102519647,
            1469545068,
            1570904716,
            736963012,
            136245752,
            3459013321,
            175228026,
            2579642555,
            1863655284,
            1119268048,
            79016638,
            1863071906,
            2026912831,
            1956857682,
            934548729,
            2454720260,
            235082693,
            100003435,
            2801580005,
            3876640985,
            1122726988,
            1681709246,
            1290552089,
            1122726988,
            937565482,
            2775073587,
            1122726988,
            1122726988,
            3752530561,
            2801580005,
            2126217295,
            1681709246,
            2283837502,
            1122726988,
            1122726988,
            254293829,
            1726347625,
            2710017871,
            1122726988,
            1290552089,
            2283837502,
            1726347625,
            937565482,
            2841405797,
            1861596440,
            3367169544,
            101344554,
            1762251111,
            320363404,
            3900270181,
            2251088608,
            6972272,
            628053854,
            1830587730,
            3367169544,
            101344554,
            1762251111,
            320363404,
            3900270181,
            2251088608,
            6972272,
            4030834450,
            2841405797,
            1861596440,
            320363404,
            3056977511,
            2926296897,
            101344554,
            3536692702,
            6972272,
            283466393,
            1813878989,
            283466393,
            237149484,
            4276515350,
            283466393,
            3850717857,
            1482993587,
            2236949894,
            3850717857,
            2743038119,
            497179875,
            2226156176,
            3090948402,
            75011793,
            2568391651,
            2534921316,
            4278528287,
            703457787,
            3694158977,
            3021747017,
            3942864794,
            3694158977,
            3093626195,
            3186616847,
            3093626195,
            3942864794,
            3021747017,
            3093626195,
            126705307,
            3942864794,
            2202951568,
            3942864794,
            733877165,
            71633062,
            1591010565,
            1591010565,
            2599460530,
            3287536794,
            3404973854,
            177376859,
            2271126315,
            2796899421,
            2227069989,
            3195074528,
            912168999,
            2271126315,
            881481121,
            1897092238,
            283306430,
            232888882,
            2796899421,
            2271126315,
            1960566529,
            2271126315,
            177376859,
            259538547,
            2556107740,
            3897381343,
            2556107740,
            2796899421,
            1073508324,
            3195074528,
            2796899421,
            979359785,
        ];
    }

    protected function getCharImageHash($charImage)
    {
        $charImageId = get_resource_id($charImage);
        if (isset($this->charImageHashes[$charImageId])) {
            $charHash = $this->charImageHashes[$charImageId];
        } else {
            $charString = $this->getCharImageString($charImage);
            $charHash = crc32($charString);
            $this->charImageHashes[$charImageId] = $charHash;
        }

        return $charHash;
    }

    protected function getCharImageString($charImage)
    {
        $charImageId = get_resource_id($charImage);
        if (isset($this->charImageStrings[$charImageId])) {
            $charString = $this->charImageStrings[$charImageId];
        } else {
            ob_start();
            imagegif($charImage);
            $charString = ob_get_clean();
            $this->charImageStrings[$charImageId] = $charString;
        }

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
     * @param mixed $imageSingleChar Image resource som innehåller ett tecken, 13x16px.
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
            'bgBl' => ['r' => 0, 'g' => 0, 'b' => 0], // Svart
            'bgR' => ['r' => 255, 'g' => 0, 'b' => 0], // Röd
            'bgM' => ['r' => 255, 'g' => 0, 'b' => 255], // Magenta (lila)
            'bgB' => ['r' => 0, 'g' => 0, 'b' => 255], // Blå (mörkblå)
            'bgC' => ['r' => 0, 'g' => 255, 'b' => 255], // Cyan (ljusblå)
            'bgG' => ['r' => 0, 'g' => 255, 'b' => 0], // Grön
            'bgY' => ['r' => 255, 'g' => 255, 'b' => 0], // Gul
            'bgW' => ['r' => 255, 'g' => 255, 'b' => 255], // Vit
        ];

        $texts = [
            'bl' => ['r' => 0, 'g' => 0, 'b' => 0], // Svart
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
