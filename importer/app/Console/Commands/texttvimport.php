<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Classes\Importer;
use App\Models\TextTV;
use Illuminate\Support\Facades\Log;

class texttvimport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'texttv:import {pageNumber}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hämta sida från svt.se/text-tv och spara i DB om den har ändrats.';

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

        $this->info("Importerar sida {$pageNumber}");

        // Hämta sidan från SVT.
        $page = new Importer($pageNumber);
        $page->fromRemote()->cleanup()->colorize()->linkify();

        // Skapa array med enbart sidornas text; formatet vi lagrar i db.
        $arrSubpagesTexts = $page->subpages()->pluck('text')->toArray();

        // Hämta befintlig sida från db.
        $dbPage = TextTV::where('page_num', $pageNumber)
            ->orderByDesc('date_updated')
            ->limit(1)
            ->first();
        
        if ($dbPage) {
            $uncompressedDbPageContent = $dbPage->pageContentUncompressed();
        } else {
            $uncompressedDbPageContent = [];
        }

        // Jämför befintlig sida med hämtat sida.
        // $this->line("Hämtad sida från SVT:");
        // $this->comment(print_r($arrSubpagesTexts, 1));

        // $this->line("Sida i DB:");
        // $this->comment(print_r($uncompressedDbPageContent, 1));

        $fetchedPageAndDbPageIsEqual = $arrSubpagesTexts === $uncompressedDbPageContent;
        if ($fetchedPageAndDbPageIsEqual) {
            $msg = "{$pageNumber}: Ingen import görs: befintlig och hämtad sida är lika.";
            $this->info($msg);
            echo $msg;
            Log::info($msg);
            return;
        } else {
            $msg = "{$pageNumber}: Befintlig och hämtad sida är inte lika, så sparar sidan till databasen.";
            $this->info($msg);
            echo $msg;
            Log::info($msg);
            $serializedArrSubpagesTexts = serialize($arrSubpagesTexts);
            $compressedSerializedArrSubpagesTexts = "\x1f\x8b\x08\x00" . gzcompress($serializedArrSubpagesTexts);

            $newPage = new TextTV;
            $newPage->fill([
                'page_num' => $page->pageNum(),
                'title' => $page->title(),
                'page_content' => $compressedSerializedArrSubpagesTexts,
                'next_page' => $page->nextPageNum(),
                'prev_page' => $page->prevPageNum(),
            ]);

            if (!$newPage->save()) {
                $this->error("Fel: ett fel uppstod när sidan skulle sparas.");
                return;
            }

            $this->info("Sida sparades till databas med ID {$newPage->id}");
        }
    }
}

// $compressed_data = "\x1f\x8b\x08\x00".gzcompress($uncompressed_data);
// https://stackoverflow.com/questions/24607493/mysql-compress-vs-php-gzcompress
