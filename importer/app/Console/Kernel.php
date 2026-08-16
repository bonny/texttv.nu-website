<?php

namespace App\Console;

use App\Console\Commands\texttvimport;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Artisan;

class Kernel extends ConsoleKernel {
    /**
     * Importerar alla sidor inom ett intervall.
     * 
     * @param int $fromPageNumber 
     * @param int $toPageNumber 
     * @return void 
     */
    protected function importRange(int $fromPageNumber, int $toPageNumber) {
        Artisan::call('texttv:import', ['pageNumber' => "$fromPageNumber-$toPageNumber"]);
    }

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule) {
        // Startsidan, nyheter inrikes & utrikes.
        $schedule->command(texttvimport::class, ['100-105'])
            ->everyTwoMinutes()
            ->runInBackground();

        // Nyhetsartiklarna
        $schedule->command(texttvimport::class, ['106-199'])
            ->everyTwoMinutes()
            ->runInBackground();

        // Sport
        $schedule->command(texttvimport::class, ['300-399'])
            ->everyTwoMinutes()
            ->runInBackground();

        // OS-sidorna
        $schedule->call(function () {
            $this->importRange(440, 499);
        })->everyFourMinutes();

        # ofta pga vad som är på tv just nu, typ varannan minut
        #*/2 * * * * root cd /root/texttv-page-updater/ && php updater.php --pageRange 650-655 > 
        $schedule->call(function () {
            $this->importRange(650, 655);
        })->everyFourMinutes();

        # tv-tablå, Rimligt ofta
        #6,12,19,31,42,49,57 * * * * root cd /root/texttv-page-updater/ && php updater.php --pageRange 600-649 > 
        #*/19 * * * * root cd /root/texttv-page-updater/ && php updater.php --pageRange 656-669 > 
        $schedule->call(function () {
            $this->importRange(600, 649);
            $this->importRange(656, 669);
        })->everyFourMinutes();

        ## nästan fresh, var femte minut eller så
        # Lotto osv, hästar
        #*/5 * * * * root cd /root/texttv-page-updater/ && php updater.php --pageRange 500-599 > 
        // 2026-08-15 (todo #12): utspridd från everyTenMinutes till minut
        // 1,11,21,... Tidigare startade det här jobbet samtidigt som
        // importRange(730,750) och båda cleanup-jobben, ovanpå
        // everyTwoMinutes-jobben som upptar alla JÄMNA minuter. Resultatet
        // var en mätbar spik var tionde minut: p99 för hela sajten gick från
        // 0,027 s till 1,18 s under de minuterna (kl 18-23, 2026-08-14).
        // Udda minuter valda med flit så vi inte krockar med tvåminutersjobben.
        $schedule->call(function () {
            $this->importRange(500, 599);
        })->cron('1-59/10 * * * *');

        # 670 - infosidor för tv
        #3,18,28,39,47,58 * * * * root cd /root/texttv-page-updater/ && php updater.php --pageRange 670-699 > 
        $schedule->call(function () {
            $this->importRange(670, 699);
        })->hourly();

        # UR osv
        #16,34,45,56 * * * * root cd /root/texttv-page-updater/ && php updater.php --pageRange 800-801 > 
        $schedule->call(function () {
            $this->importRange(800, 801);
        })->everySixHours();

        ## Halvofta, typ en gång i halvtimmen, väder..

        # väder osv
        #*/26 * * * * root sleep `numrandom /25..60/`s ; cd /root/texttv-page-updater/ && php updater.php --pageRange 400-439 > 
        #12,26,39,42,59 * * * * root cd /root/texttv-page-updater/ && php updater.php --pageRange 400-439 > 
        $schedule->call(function () {
            $this->importRange(400, 439);
        })->everyThirtyMinutes();

        ## Sällan, en gång per dag-ish

        # Dövinfo, slingan, teckenförklarings för börs, osv
        #9 */2 * * * root cd /root/texttv-page-updater/ && php updater.php --pageRange 245-299 > 

        # Gamla sport-rio-sidorna
        #1 */2 * * * root cd /root/texttv-page-updater/ && php updater.php --pageRange 440-499 > 

        # Inte så ofta/ändras sällan
        #7 */2 * * * root cd /root/texttv-page-updater/ && php updater.php --pageRange 700-799 > 

        $schedule->call(function () {
            $this->importRange(245, 299);
            $this->importRange(700, 729);
            $this->importRange(751, 799);
        })->daily();

        // 730-750 verkar ha någon form av sportresultat numera.
        // 2026-08-15 (todo #12): utspridd till minut 5,15,25,... se kommentar
        // vid importRange(500,599).
        $schedule->call(function () {
            $this->importRange(730, 750);
        })->cron('5-59/10 * * * *');

        # uppdateras aldrig?
        #7 4 * * * root cd /root/texttv-page-updater/ && php updater.php --pageRange 900-999 > 
        #7 */13 * * * root cd /root/texttv-page-updater/ && php updater.php --pageRange 802-899 > 
        $schedule->call(function () {
            $this->importRange(900, 999);
            $this->importRange(802, 899);
            $this->importRange(700, 799);

            // Börsen har lagts ner.
            $this->importRange(200, 245);
        })->weekly();

        $schedule->command('import-status:remove-old')->daily();

        // Run cleanup with default limit (100000) during the day
        $schedule->command('texttv:cleanup-page-actions')
            ->everyFifteenMinutes()
            ->unlessBetween('01:30', '05:30');

        // Run cleanup with increased limit during night
        // 2026-08-15 (todo #12): utspridd till minut 3,13,23,... Dagvarianten
        // (var 15:e min) är mätt oskyldig — minuterna :15 och :45 hade p99
        // 0,031 s. Nattvarianten flyttas ändå av samma anledning som övriga:
        // den låg på tiominuterstickan tillsammans med allt annat.
        $schedule->command('texttv:cleanup-page-actions --limit=1000000')
            ->cron('3-59/10 * * * *')
            ->between('01:30', '05:30');

        // Cleanup old pages — EN gång per dygn, 04:07.
        //
        // 2026-08-16 (todo #12): flyttad hit från var tionde minut. Mätning
        // 2026-08-15 visade att det här jobbet ensamt bar hela den
        // tiominutersspik som gjorde p99 för hela sajten 43x sämre under
        // ~20 % av tiden (1,97–2,51 s på jobbets minuter mot 0,03 s ovrigt).
        // Spiken varade ~10 s per körning, 144 gånger per dygn.
        //
        // Och den gjorde ingen nytta: villkoret undantar sidorna 100 och 377,
        // som står för 221 715 av de 221 729 gamla raderna med is_shared=0.
        // Kvar att faktiskt radera: 13 rader. Det var kostnaden för att
        // scanna indexintervallet i en 1,5 GB-tabell, inte för raderingen.
        //
        // Undantaget för 100/377 är medvetet (arkivsidorna rankar, se todo
        // #09/#10/#11) och ska inte ändras här.
        //
        // Limit satt högt så att en ev. framtida backlog — t.ex. om
        // retention-policyn ändras — kan betas av i en enda nattkörning.
        $schedule->command('texttv:cleanup-old-pages --limit=300000')
            ->dailyAt('04:07');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands() {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
