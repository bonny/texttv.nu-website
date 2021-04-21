<?php

namespace App\Http\Controllers;

use App\Classes\TeletextCharsExtractor;
use Illuminate\Http\Request;

class PageColors extends Controller
{

    public function index(int $pageNum)
    {
        $testPagesDir = base_path('tests/TestPages');
        $imagePathAndName = "{$testPagesDir}/{$pageNum}.gif";

        $charsExtractor = new TeletextCharsExtractor;
        $charsExtractor->loadImage($imagePathAndName)->parseImage();

        echo "<br><code>getChar(0, 3)</code>: " . $charsExtractor->getChar(0, 3)['charAsImgTag'];
        echo "<br><code>getChar(0, 7)</code>: " . $charsExtractor->getChar(0, 7)['charAsImgTag'];
        echo "<hr>";
        echo $charsExtractor->getImageDebugHtml();
        // echo "<pre>" . print_r($charsExtractor->getChars(), 1) . "</pre>";

?>
        <script>
            document.addEventListener('click', (evt) => {
                let target = evt.target;

                if (!target.nodeName == 'IMG') {
                    return;
                }

                let title = target.getAttribute("title");
                console.info('image title', title);
            });
        </script>
<?php

    }
}
