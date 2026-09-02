<?php

use App\Http\Controllers\Api\PanggilanAntreanController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Grup ini khusus dipanggil oleh laptop jembatan (Node.js) yang berjalan
| di jaringan lokal kantor untuk meneruskan perintah panggil antrean ke
| mesin antrean fisik. Dilindungi token statis (VerifikasiTokenJembatan),
| bukan sesi login Admin/CS, karena pemanggilnya bukan browser.
|
*/
Route::middleware('verifikasi.token-jembatan')->prefix('panggilan')->group(function () {
    Route::get('/pending', [PanggilanAntreanController::class, 'pending']);
    Route::post('/{panggilan}/selesai', [PanggilanAntreanController::class, 'tandaiSelesai']);
    Route::post('/{panggilan}/gagal', [PanggilanAntreanController::class, 'tandaiGagal']);
    Route::post('/heartbeat', [PanggilanAntreanController::class, 'heartbeat']);
    Route::post('/counter-mesin', [PanggilanAntreanController::class, 'counterMesin']); // ★ baru
});

