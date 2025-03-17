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
        {--dry-run : Visa vad som skulle tas bort utan att faktiskt ta bort något}
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
        $isDryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');
        
        if ($isDryRun) {
            $this->info("KÖR I TESTLÄGE - Inga sidor kommer att tas bort");
        }
        
        $this->line("Tar bort gamla text-tv sidor (max {$limit} st)...");

        // Build the query
        $query = TextTV::where('date_added', '<', Date::now()->subYear())
            ->where('is_shared', 0)
            ->whereNotIn('page_num', [100, 377]);

        if ($isDryRun) {
            // In dry-run mode, show some statistics and sample pages
            $totalCount = $query->count();
            $samplePages = (clone $query)->select('id', 'page_num', 'date_added', 'title')
                ->limit(5)
                ->get();

            $this->info("\nAntal sidor som skulle tas bort: " . min($totalCount, $limit));
            $this->info("(Totalt antal sidor som uppfyller kriterier: {$totalCount})");
            $this->info("\nExempel på sidor som skulle tas bort:");
            foreach ($samplePages as $page) {
                $this->line("- Sida {$page->page_num} (ID: {$page->id}, titel: {$page->title}, skapad: {$page->date_added}, delad: {$page->is_shared})");
            }
            return 0;
        }

        // Actually perform the deletion
        $numDeletedRows = $query->limit($limit)->delete();

        $this->line("Antal borttagna sidor: $numDeletedRows");
        $this->line("Klart!");

        return 0;
    }
} 