<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

class CleanupPageActions extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'texttv:cleanup-page-actions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tar bort gamla saker från page actions-tabellen.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {
        $this->line("Tar bort gamla rader från page actions-tabellen...");

        $numDeletedRows = DB::connection('mysql_stats_db')->table('page_actions')
            ->where('created_at', '<', Date::now()->subDays(100))
            ->limit(50000)
            ->delete();

        $this->line("Rader borttagna: $numDeletedRows");

        $this->line("Klart!");

        return 0;
    }
}
