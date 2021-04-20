<?php

namespace App\Http\Controllers;

use App\Classes\TeletextCharsExtractor;
use Illuminate\Http\Request;

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
