<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Classes\Importer;

/**
 * Tester för cleanup(): alt-texten från SVT blir 24 rader à 40 tecken.
 *
 * SVT:s alt-text saknar blockgrafiken, så korta rader måste fyllas ut. På
 * startsidan 100 ska sidnumren under rubrikerna hamna i samma kolumn som på
 * SVT. Facit är SVT:s bildkarta (imageMap): varje sidnummer har en rektangel
 * i pixlar, 13 px per kolumn och 16 px per rad.
 */
class CleanupTest extends TestCase
{
    private function cleanedLines(string $file): array
    {
        $importer = new Importer(100);
        $importer->fromFile(__DIR__ . "/../TestPages/{$file}");
        $importer->cleanup();

        return explode("\n", $importer->subPages()->first()['text']);
    }

    /**
     * Sidnumren i bildkartan: [rad, kolumn, sidnummer].
     */
    private function imageMapLinks(string $file): array
    {
        $importer = new Importer(100);
        $importer->fromFile(__DIR__ . "/../TestPages/{$file}");
        $imageMap = $importer->pageObject()->props->pageProps->subPages[0]->imageMap;

        preg_match_all('/COORDS="(\d+),(\d+),\d+,\d+" HREF="(\d+)"/', $imageMap, $matches, PREG_SET_ORDER);

        return array_map(fn ($m) => [intdiv((int) $m[2], 16), intdiv((int) $m[1], 13), $m[3]], $matches);
    }

    public static function fileProvider(): array
    {
        return [
            ['100.html'],
            ['100-2026-10-28.html'],
        ];
    }

    /**
     * Rader med bara sidnummer ("106", "130-133") står där SVT har dem.
     *
     * @dataProvider fileProvider
     */
    public function test_sidnummer_pa_startsidan_star_som_hos_svt(string $file)
    {
        $lines = $this->cleanedLines($file);

        $checked = 0;
        foreach ($this->imageMapLinks($file) as [$row, $column, $pageNum]) {
            $line = $lines[$row];
            if (!preg_match('/^[\d\s,\-]+$/', $line)) {
                continue;
            }
            $this->assertSame($column, mb_strpos($line, $pageNum), "Rad {$row}: \"{$line}\", {$pageNum} ska stå i kolumn {$column}");
            $checked++;
        }

        $this->assertGreaterThan(0, $checked, 'Inga rader med bara sidnummer hittades');
    }

    /**
     * @dataProvider fileProvider
     */
    public function test_alla_rader_ar_40_tecken(string $file)
    {
        $lines = $this->cleanedLines($file);

        $this->assertCount(24, $lines);
        foreach ($lines as $index => $line) {
            $this->assertSame(40, mb_strlen($line), "Rad {$index}: \"{$line}\"");
        }
    }
}
