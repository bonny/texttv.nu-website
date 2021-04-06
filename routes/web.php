<?php

use Illuminate\Support\Facades\Route;
use App\Classes\Importer;

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

// @todo
// Lägg till route för att live-visa sidor från SVT.
// /live/{pageNum}
Route::redirect('/live/', '/live/100');
Route::get('/live/{pageNum}', function ($pageNum) {
    $importer = new Importer($pageNum);
    $importer->fromRemote()->cleanup()->linkprefix('/live/')->decorate();

    return view(
        'live',
        [
            'importer' => $importer
        ]
    );
})->where('pageNum', '[0-9]+');
