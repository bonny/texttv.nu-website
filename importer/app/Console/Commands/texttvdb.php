<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TextTV;

class texttvdb extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'texttv:db {pageNumber=100}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Visa sida från db.';

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
        $pageNumber = $this->argument('pageNumber');

        $page = TextTV::where('page_num', $pageNumber)
        ->orderByDesc('date_updated')
        ->limit(1)
        ->firstOrFail();
        
        $uncompressedPageContent = unserialize(gzuncompress(substr($page->page_content, 4)));
        
        $this->info('Visar sida ' . $pageNumber);
        $this->newLine();
        $this->line(print_r($uncompressedPageContent, true));

        #dump($uncompressedPageContent);

        return 0;
    }
}
