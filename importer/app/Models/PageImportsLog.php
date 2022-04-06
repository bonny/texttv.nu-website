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
}
