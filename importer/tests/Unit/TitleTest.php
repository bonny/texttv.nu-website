<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Classes\Importer;

/**
 * Tester för title(): sidans titel, som används i listorna "Senaste
 * sportnyheterna", i <title> och i arkivets permalänkar.
 *
 * Testsidorna i TestPages/titles/ är hämtade från SVT 2026-11-01 och bantade
 * till alt-texten och bildkartan (utan GIF:ar). Facit står i
 * TestPages/snapshots/titles.json och skrivs om med -e UPDATE_SNAPSHOTS=1.
 */
class TitleTest extends TestCase
{
    private function titles(): array
    {
        $titles = [];
        foreach (glob(__DIR__ . '/../TestPages/titles/*.html') as $file) {
            $pageNum = (int) basename($file, '.html');
            $importer = new Importer($pageNum);
            $importer->fromFile($file)->cleanup();
            $titles[$pageNum] = $importer->title();
        }
        ksort($titles);

        return $titles;
    }

    public function test_titlar()
    {
        $snapshot = __DIR__ . '/../TestPages/snapshots/titles.json';
        $titles = $this->titles();

        if (getenv('UPDATE_SNAPSHOTS')) {
            file_put_contents($snapshot, json_encode($titles, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
        }

        $this->assertSame(json_decode(file_get_contents($snapshot), true), $titles);
    }
}
