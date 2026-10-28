<?php

namespace Tests\Feature;

use App\Models\PageImportsLog;
use App\Models\TextTV;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Debug-rutterna i routes/web.php (todo #15).
 *
 * Fångar ändringar i routing, middleware och vyer vid Laravel-
 * uppgraderingarna. /db och /importstatus läser den lokala databasen,
 * som lokalimporten fyller (make up).
 *
 * /pagecolors/{n} togs bort 2026-10-28 (gav 500).
 */
class DebugRoutesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Grafiken från /live hamnar inte i den riktiga storage.
        $root = sys_get_temp_dir() . '/texttv-routes-test-' . uniqid();
        mkdir($root);
        config(['filesystems.disks.local.root' => $root]);
        $this->beforeApplicationDestroyed(fn () => exec('rm -rf ' . escapeshellarg($root)));

        Http::fake([
            'https://www.svt.se/text-tv/100' => Http::response(file_get_contents(__DIR__ . '/../TestPages/100.html')),
        ]);
    }

    public function test_live()
    {
        $this->get('/live/100')
            ->assertOk()
            ->assertSee('href="/live/300"', false);

        $this->get('/live/')->assertRedirect('/live/100');
    }

    public function test_db()
    {
        $this->kraverLokalDatabas(fn () => TextTV::where('page_num', 100)->exists());

        $this->get('/db/100')
            ->assertOk()
            ->assertSee('class="root"', false);

        $this->get('/db/')->assertRedirect('/db/100');
        $this->get('/db/1')->assertNotFound();
        $this->get('/db/abc')->assertNotFound();
    }

    public function test_importstatus()
    {
        $this->kraverLokalDatabas(fn () => PageImportsLog::exists());

        $this->get('/importstatus')
            ->assertOk()
            ->assertSee('Statusar senaste timmen')
            ->assertSee('<th>Sidnummer</th>', false)
            ->assertSee('<td>', false);
    }

    /**
     * Hoppa över när stacken saknar data (ny make up innan importen kört,
     * eller ingen MariaDB), i stället för att falla på något som inte är koden.
     */
    protected function kraverLokalDatabas(callable $harData): void
    {
        try {
            $finns = $harData();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Ingen lokal databas: ' . $e->getMessage());
        }

        if (!$finns) {
            $this->markTestSkipped('Lokala databasen saknar data, kör make up och vänta på lokalimporten.');
        }
    }
}
