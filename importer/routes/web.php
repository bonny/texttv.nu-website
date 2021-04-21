<?php

use Illuminate\Support\Facades\Route;
use App\Classes\Importer;
use App\Models\TextTV;
use App\Http\Controllers\PageColors;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Route för att live-visa sidor från SVT.
Route::redirect('/live/', '/live/100');
Route::get('/live/{pageNum}', function ($pageNum) {
    $importer = new Importer($pageNum);

    #$importer->fromRemote()->cleanup()->linkprefix('/live/')->decorate();
    $importer->fromRemote()->cleanup()->linkprefix('/live/')->colorize();

    return view(
        'live',
        [
            'importer' => $importer,
            'title' => $importer->title(),
        ]
    );
})->where('pageNum', '[0-9]+');

// Route för att live-visa sidor från SVT.
Route::redirect('/db/', '/db/100');
Route::get('/db/{pageNum}', function ($pageNum) {
    $page = TextTV::where('page_num', $pageNum)
        ->orderByDesc('date_updated')
        ->limit(1)
        ->firstOrFail();

    $uncompressedPageContent = unserialize(gzuncompress(substr($page->page_content, 4)));

    return view(
        'db',
        [
            'pageNum' => $page->page_num,
            'pageContent' => $uncompressedPageContent,
            'date_added' => $page->date_added,
            'next_page' => $page->next_page,
            'prev_page' => $page->prev_page,
            'title' => $page->title,
        ]
    );
})->where('pageNum', '[0-9]+');

Route::get('/pagecolors/{pageNum}', [PageColors::class, 'index'])->where('pageNum', '[0-9\-]+');
