<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Classes\Importer;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\StrayRequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

/**
 * Tester för fromRemote(): hämtningen från SVT.
 *
 * Ett nätverksfel (cURL 18, 28, 35 i laravel.log, ett par om dagen) får ett
 * nytt försök innan sidan ges upp till nästa schemavarv (todo #15).
 */
class FromRemoteTest extends TestCase
{
    private const URL = 'https://www.svt.se/text-tv/100';

    protected function setUp(): void
    {
        parent::setUp();
        Sleep::fake();
    }

    private function page100(): string
    {
        return file_get_contents(__DIR__ . '/../TestPages/100.html');
    }

    public function test_nytt_forsok_efter_natverksfel()
    {
        Http::fakeSequence(self::URL)
            ->pushFailedConnection('cURL error 18: transfer closed with outstanding read data remaining')
            ->push($this->page100());

        $importer = (new Importer(100))->fromRemote();

        $this->assertEquals('100', $importer->pageObject()->props->pageProps->pageNumber);
        Http::assertSentCount(2);
        Sleep::assertSleptTimes(1);
    }

    public function test_ger_upp_efter_tva_natverksfel()
    {
        Http::fakeSequence(self::URL)
            ->pushFailedConnection()
            ->pushFailedConnection();

        try {
            (new Importer(100))->fromRemote();
            $this->fail('ConnectionException kastades inte');
        } catch (ConnectionException $e) {
            // texttv:import fångar den och loggar NOT_IMPORTED_EXCEPTION.
        }

        Http::assertSentCount(2);
    }

    public function test_inget_nytt_forsok_nar_svt_svarar_med_fel()
    {
        Http::fakeSequence(self::URL)->push('', 404);

        // Inget undantag: sidan hoppas över som i dag (successful() är false).
        (new Importer(100))->fromRemote();

        Http::assertSentCount(1);
    }

    public function test_tester_hamtar_aldrig_fran_svt()
    {
        $this->expectException(StrayRequestException::class);

        (new Importer(100))->fromRemote();
    }
}
