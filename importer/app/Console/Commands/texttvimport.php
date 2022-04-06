<?php

namespace App\Console\Commands;

use App\Classes\Importer;
use App\Models\TextTV;
use App\Models\PageImportsLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
    protected $description = 'Hämta sida eller sidor från svt.se/text-tv och spara i DB om den har ändrats.';

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
        // Exempel på format:
        // 100
        // 100-110
        // 110,120-130
        // 110,120-130,101,102-105...
        $pageNumber = $this->argument('pageNumber');

        // Expand ranges in pageNumbers. So 100-102 is expaned to 100,101,102.
        // Source: https://stackoverflow.com/a/7698869
        $pageNumbers = preg_replace_callback('/(\d+)-(\d+)/', function($m) {
            return implode(',', range($m[1], $m[2]));
        }, $pageNumber);

        foreach (explode(',', $pageNumbers) as $onePageNumber) {
            $this->importPage($onePageNumber);
        }
    }
    
    public function importPage($pageNumber) {
        $this->info("Importerar sida {$pageNumber}");
        $page = new Importer($pageNumber);

        // Hämta sidan från SVT.
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
            Log::info($msg);
            
            PageImportsLog::create([
                'page_num' => $pageNumber,
                'import_result' => 'NOT_IMPORTED_PAGE_NOT_CHANGED'
            ]);
            
            return;
        } else {

            // Om sidan vi ska importera har status
            // pageIsNotBroadcasted = true
            // så ska den bara importeras om det redan gjorts x antal försök redan.
            if ($page->subpages()->count() === 1 && $page->subpage(0)['pageIsBroadcasted'] === false) {
                // Sidan är inte i sändning.
                // Kolla sidans senaste x antal importer och om alla har status NOT_IMPORTED_REMOTE_NOT_BROADCASTED
                // så uppdaterar vi vår sida, annars låter vi den vara i tidigare skick, som förhoppningsvis har innehåll,
                // för vi kommer bara hit om sidan har fått nytt innehåll, dvs. går från t.ex. "Innehåll" -> "Inget innehåll".
                $this->info('Sidans status är "Inte i sändning"');
                
                PageImportsLog::create([
                    'page_num' => $pageNumber,
                    'import_result' => 'NOT_IMPORTED_REMOTE_NOT_BROADCASTED'
                ]);
            }

            $msg = "{$pageNumber}: Befintlig och hämtad sida är inte lika, så sparar sidan till databasen.";
            $this->info($msg);
            Log::info($msg);

            $serializedArrSubpagesTexts = serialize($arrSubpagesTexts);

            $newPage = new TextTV;
            $newPage->fill([
                'page_num' => $page->pageNum(),
                'title' => $page->title(),
                'page_content' => '',
                'next_page' => $page->nextPageNum(),
                'prev_page' => $page->prevPageNum(),
            ]);

            if (!$newPage->save()) {
                $this->error("Fel: ett fel uppstod när sidan skulle sparas.");
                return;
            }

            // Kör rå update för att fixa compress av data.
            try {
                $affected = DB::update(
                    'update texttv set page_content = COMPRESS(?) where id = ?',
                    [
                        $serializedArrSubpagesTexts,
                        $newPage->id
                    ]
                );
            } catch(\Illuminate\Database\QueryException $ex){ 
                echo "Fel när page_content ska läggas in komprimerat. (QueryException)";
                exit;                
            } catch (\Exception $e) {
                echo "Fel när page_content ska läggas in komprimerat. (Exception)";
                exit;
            }

            if ($affected !== 1) {
                $this->error("Fel: ett fel uppstod när sidans innehåll skulle sparas komprimerat.");
                return;
            }

            $this->info("Sida sparades till databas med ID {$newPage->id}");

            PageImportsLog::create([
                'page_num' => $pageNumber,
                'import_result' => 'IMPORT_SUCCESS'
            ]);

        }
    }
}

// $compressed_data = "\x1f\x8b\x08\x00".gzcompress($uncompressed_data);
// https://stackoverflow.com/questions/24607493/mysql-compress-vs-php-gzcompress
