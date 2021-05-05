<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Artisan;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Importerar alla sidor inom ett intervall.
     * 
     * @param int $fromPageNumber 
     * @param int $toPageNumber 
     * @return void 
     */
    protected function importRange(int $fromPageNumber, int $toPageNumber)
    {
        for ($pageNumber = $fromPageNumber; $pageNumber <= $toPageNumber; $pageNumber++) {
            Artisan::call('texttv:import', ['pageNumber' => $pageNumber]);
        }
    }

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {

        /**
         * Ofta, som nyheter + börs, sport.
         */
        $schedule->call(function () {
            # nyheter, börs
            #100-245
            $this->importRange(100, 245);
            # sport
            #300-399
            $this->importRange(300, 399);
        })->everyTwoMinutes();

        # ofta pga vad som är på tv just nu, typ varannan minut
        #*/2 * * * * root cd /root/texttv-page-updater/ && php updater.php --pageRange 650-655 > 
        $schedule->call(function () {
            $this->importRange(650, 655);
        })->everyFourMinutes();

        ## nästan fresh, var femte minut eller så
        # Lotto osv, hästar
        #*/5 * * * * root cd /root/texttv-page-updater/ && php updater.php --pageRange 500-599 > 
        $schedule->call(function () {
            $this->importRange(500, 599);
        })->everyTenMinutes();

        # tv-tablå, Rimligt ofta
        #6,12,19,31,42,49,57 * * * * root cd /root/texttv-page-updater/ && php updater.php --pageRange 600-649 > 
        #*/19 * * * * root cd /root/texttv-page-updater/ && php updater.php --pageRange 656-669 > 
        $schedule->call(function () {
            $this->importRange(600, 649);
            $this->importRange(656, 669);
        })->everyFourMinutes();

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
            $this->importRange(440, 499);
            $this->importRange(700, 799);
        })->daily();

        # uppdateras aldrig?
        #7 4 * * * root cd /root/texttv-page-updater/ && php updater.php --pageRange 900-999 > 
        #7 */13 * * * root cd /root/texttv-page-updater/ && php updater.php --pageRange 802-899 > 
        $schedule->call(function () {
            $this->importRange(900, 999);
            $this->importRange(802, 899);
            $this->importRange(700, 799);
        })->weekly();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
