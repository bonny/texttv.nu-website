<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Classes\Importer;

class texttvlive extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'texttv:livepage {pageNumber=100}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Visa sida direkt från svt.se/text-tv.';

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
        $page = new Importer($pageNumber);
        $page->fromRemote()->cleanup()->decorate();
        dump($page->subpages());

        return 0;
    }
}
