<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Skapa ett logg-entry:
 * PageImportsLog::create(['page_num' => 100, 'import_result' => 'IMPORT_SUCCESS']);
 * 
 */
class PageImportsLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'page_num',
        'import_result',
    ];

    /**
     * Räkna antalet gånger en status förekommer löpande efter varandra, dvs.
     * utan någon annan status emellan.
     * 
     * @param string $statusToCheckFor 
     * @param mixed $pageNumber 
     * @return int 
     */
    public static function countSubsequentStatuses(string $statusToCheckFor, int $pageNumber): int
    {
        $previousPageImports = PageImportsLog::where('page_num', $pageNumber)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $statusSubsequentCount = 0;
        $previousPageImports->each(function ($logRow) use (&$statusSubsequentCount, $statusToCheckFor) {
            if ($logRow->import_result === $statusToCheckFor) {
                $statusSubsequentCount++;
            } else {
                return;
            }
        });

        return $statusSubsequentCount;
    }
}
