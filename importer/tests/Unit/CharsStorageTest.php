<?php

namespace Tests\Unit;

use App\Classes\Importer;
use Tests\TestCase;

/**
 * Blockgrafiken i storage/chars (todo #15).
 *
 * colorize() sparar varje grafiktecken som {hash}.gif och sidan pekar på
 * filen. Ögonblicksbilderna i ImportTest ersätter URL:en med {chars}, så
 * där syns det inte om filen saknas.
 *
 * Laravel 9 bytte till Flysystem 3, där put() returnerar false vid
 * skrivfel i stället för att kasta. Då sparas sidan med grafik som ger
 * 404 utan att något loggas (jfr #33). test_skrivfel_kastar ska därför
 * gå igenom även efter uppgraderingen, med 'throw' => true på disken.
 */
class CharsStorageTest extends TestCase
{
    protected string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = sys_get_temp_dir() . '/texttv-chars-test-' . uniqid();
        mkdir($this->root);
        config(['filesystems.disks.local.root' => $this->root]);
    }

    protected function tearDown(): void
    {
        chmod($this->root, 0755);
        exec('rm -rf ' . escapeshellarg($this->root));

        parent::tearDown();
    }

    public function test_varje_grafikfil_som_sidan_pekar_pa_finns()
    {
        foreach (['100', '188', '300'] as $pageNum) {
            $importer = new Importer($pageNum);
            $importer->fromFile(__DIR__ . "/../TestPages/{$pageNum}.html");
            $importer->cleanup()->colorize();

            $text = $importer->subPages()->pluck('text')->implode("\n");
            preg_match_all('#storage/chars/(\d+)\.gif#', $text, $matches);
            $hashes = array_unique($matches[1]);

            $this->assertNotEmpty($hashes, "Sida {$pageNum} har ingen grafik");

            foreach ($hashes as $hash) {
                $this->assertFileExists("{$this->root}/public/chars/{$hash}.gif", "Sida {$pageNum}");
            }
        }
    }

    public function test_skrivfel_kastar()
    {
        if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
            $this->markTestSkipped('root kan skriva i en skrivskyddad katalog.');
        }

        chmod($this->root, 0555);

        $importer = new Importer('100');
        $importer->fromFile(__DIR__ . '/../TestPages/100.html');
        $importer->cleanup();

        // Felet ska komma från Flysystem (League\Flysystem\Exception i
        // version 1, UnableToWriteFile m.fl. i version 3), inte från något
        // annat i colorize().
        try {
            $importer->colorize();
        } catch (\Throwable $e) {
            $this->assertStringStartsWith('League\\Flysystem\\', get_class($e), $e->getMessage());
            return;
        }

        $this->fail('colorize() kastade inget fel trots att storage inte är skrivbar.');
    }
}
