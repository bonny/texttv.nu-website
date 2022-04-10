<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\ImportstatusController;

class ClearOldImportStatuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import-status:remove-old';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove import statuses older than 24 hours';

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
        $this->info("Tar bort gamla importstatusar...");
        $numDeleted = ImportstatusController::removeOldStatuses();
        $this->info("{$numDeleted} statusar togs bort");
        return 1;
    }
}
