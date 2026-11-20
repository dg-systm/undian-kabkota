<?php

use App\Http\Controllers\DownloadController;
use App\Support\UndianSettings;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    // Mode global custom (6 pemenang): hanya ada 1 halaman undian,
    // sehingga halaman undian per kecamatan diarahkan ke Grand Prize.
    if (config('wilayah.draw_mode') === UndianSettings::MODE_GLOBAL6) {
        return redirect('/grand-prize');
    }

    return view('dashboard');
});

Route::get('/grand-prize', function () {
    return view('dashboard');
});

Route::group(['prefix' => 'download'], function () {
    Route::group(['prefix' => 'pdf'], function () {
        Route::get('draw/{id}', [DownloadController::class, 'drawPdf'])->name('download.pdf.draw');
        Route::get('all-winners', [DownloadController::class, 'allWinnersPdf'])->name('download.pdf.all-winners');
    });
});
