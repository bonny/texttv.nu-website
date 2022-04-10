<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PageImportsLog;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ImportstatusController extends Controller
{
    public function index()
    {
        $latestPageImportsResults = PageImportsLog::orderByDesc('created_at')
            ->limit(500)
            ->get();

        $statusCountsByMinutes = collect([
            1 => $this->getStatusCount(1),
            5 => $this->getStatusCount(5),
            10 => $this->getStatusCount(10),
            15 => $this->getStatusCount(15),
            30 => $this->getStatusCount(30),
            60 => $this->getStatusCount(60),
        ]);

        return view(
            'importresults',
            [
                'latestPageImportsResults' => $latestPageImportsResults,
                'statusCountsByMinutes' => $statusCountsByMinutes
            ]
        );
    }

    /**
     * Hämta vilka statusar sidor givit under ett visst antal minuter.
     * 
     * @param int $minutes 
     * @return array 
     */
    protected function getStatusCount(int $minutes): array
    {
        $sqlQuery = "
            SELECT
                import_result, count(import_result) as import_result_count
            FROM
                page_imports_logs
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
            GROUP BY import_result
        ";
        $statuses = DB::select($sqlQuery, [$minutes]);
        return $statuses;
    }

    /**
     * Ta bort importstatusrader som är äldre än 24 timmar.
     * 
     * @return int Antal borttagna rader.
     */
    public static function removeOldStatuses()
    {
        $deleted = PageImportsLog::where(
            'created_at',
            '<',
            now()->startOfDay()
        )->delete();

        return $deleted;
    }
}
