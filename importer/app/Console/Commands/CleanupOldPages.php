<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use App\Models\TextTV;

class CleanupOldPages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'texttv:cleanup-old-pages 
        {--limit=10000 : Antal sidor att ta bort per körning}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tar bort gamla text-tv sidor som är äldre än ett år och inte är delade (förutom nyhetssidan 100 och sportsidan 377).';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $limit = (int) $this->option('limit');
        
        $this->line("Tar bort gamla text-tv sidor (max {$limit} st)...");

        // Build the query
        $query = TextTV::where('date_added', '<', Date::now()->subYear())
            ->where('is_shared', 0)
            ->whereNotIn('page_num', [100, 377])
            ->orderBy('date_added', 'asc');

        // Perform the deletion
        $numDeletedRows = $query->limit($limit)->delete();

        $this->line("Antal borttagna sidor: $numDeletedRows");
        $this->line("Klart!");

        return 0;
    }
} 