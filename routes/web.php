<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\JadwalController;
use App\Http\Controllers\Admin\KalenderController as AdminKalenderController;
use App\Http\Controllers\Admin\LaporanController;
use App\Http\Controllers\Admin\LayananController;
use App\Http\Controllers\Admin\PengaturanSistemController;
use App\Http\Controllers\Admin\PengumumanController;
use App\Http\Controllers\Admin\PetugasController;
use App\Http\Controllers\Admin\ProfilController as AdminProfilController;
use App\Http\Controllers\Admin\ReservasiController as AdminReservasiController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Cs\DashboardController as CsDashboardController;
use App\Http\Controllers\Cs\KalenderController as CsKalenderController;
use App\Http\Controllers\Cs\PanduanController as CsPanduanController;
use App\Http\Controllers\Cs\ProfilController as CsProfilController;
use App\Http\Controllers\Cs\ReservasiController as CsReservasiController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\ReservasiController;
use App\Http\Controllers\System\ErrorDemoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [LandingController::class, 'index'])->name('landing');

Route::prefix('reservasi')->name('reservasi.')->group(function () {
    Route::get('/create', [ReservasiController::class, 'create'])->name('create');
    Route::get('/jadwal-tersedia', [ReservasiController::class, 'jadwalTersedia'])->name('jadwal-tersedia');
    Route::post('/', [ReservasiController::class, 'store'])->name('store');

    Route::get('/cek-status', [ReservasiController::class, 'cekStatusForm'])->name('cek-status.form');
    Route::post('/cek-status', [ReservasiController::class, 'cekStatusProses'])
        ->middleware('throttle:6,1')
        ->name('cek-status.proses');

    Route::get('/{reservasi}', [ReservasiController::class, 'show'])->name('show');
    Route::get('/{reservasi}/ubah-jadwal', [ReservasiController::class, 'editJadwal'])->name('ubah-jadwal.edit');
    Route::put('/{reservasi}/ubah-jadwal', [ReservasiController::class, 'updateJadwal'])->name('ubah-jadwal.update');
    Route::delete('/{reservasi}/batalkan', [ReservasiController::class, 'batalkan'])->name('batalkan');
    Route::get('/{reservasi}/dokumen/{dokumen}/download', [ReservasiController::class, 'downloadDokumen'])
        ->name('dokumen.download');
    Route::get('/{reservasi}/dokumen/{dokumen}/preview', [ReservasiController::class, 'previewDokumen'])
        ->name('dokumen.preview');
});

/*
|--------------------------------------------------------------------------
| Autentikasi (Admin & Customer Service)
|--------------------------------------------------------------------------
| Pelanggan tidak memiliki akun (BR-10) — grup ini hanya untuk staf
| internal. Satu halaman login, dua guard (admin, petugas), dipilih
| lewat dropdown "Login Sebagai".
*/
Route::middleware('guest:admin,petugas')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.attempt');
    Route::get('/lupa-password', [LoginController::class, 'lupaPassword'])->name('password.lupa');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth:admin,petugas')
    ->name('logout');

Route::prefix('admin')->name('admin.')->middleware('auth:admin')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::get('reservasi/export', [AdminReservasiController::class, 'export'])->name('reservasi.export');
    Route::get('reservasi', [AdminReservasiController::class, 'index'])->name('reservasi.index');
    Route::get('reservasi/{reservasi}', [AdminReservasiController::class, 'show'])->name('reservasi.show');

    Route::get('kalender-jadwal', [AdminKalenderController::class, 'index'])->name('kalender.index');

    Route::get('laporan/export', [LaporanController::class, 'export'])->name('laporan.export');
    Route::get('laporan', [LaporanController::class, 'index'])->name('laporan.index');

    Route::resource('layanan', LayananController::class);
    Route::patch('layanan/{layanan}/toggle-status', [LayananController::class, 'toggleStatus'])
        ->name('layanan.toggle-status');

    Route::get('jadwal/export', [JadwalController::class, 'export'])->name('jadwal.export');
    Route::post('jadwal/berulang', [JadwalController::class, 'storeBerulang'])->name('jadwal.store-berulang');
    Route::resource('jadwal', JadwalController::class);
    Route::patch('jadwal/{jadwal}/toggle-status', [JadwalController::class, 'toggleStatus'])
        ->name('jadwal.toggle-status');

    Route::resource('pengumuman', PengumumanController::class);
    Route::patch('pengumuman/{pengumuman}/toggle-status', [PengumumanController::class, 'toggleStatus'])
        ->name('pengumuman.toggle-status');

    Route::resource('pengguna', PetugasController::class);
    Route::patch('pengguna/{pengguna}/toggle-status', [PetugasController::class, 'toggleStatus'])
        ->name('pengguna.toggle-status');

    Route::get('pengaturan', [PengaturanSistemController::class, 'index'])->name('pengaturan.index');
    Route::put('pengaturan', [PengaturanSistemController::class, 'update'])->name('pengaturan.update');

    Route::get('profil', [AdminProfilController::class, 'index'])->name('profil.index');
});

Route::prefix('cs')->name('cs.')->middleware('auth:petugas')->group(function () {
    Route::get('/dashboard', [CsDashboardController::class, 'index'])->name('dashboard');

    Route::get('reservasi/export', [CsReservasiController::class, 'export'])->name('reservasi.export');
    Route::get('reservasi/belum-dicetak-fisik', [CsReservasiController::class, 'belumDicetakFisik'])->name('reservasi.belum-dicetak-fisik');
    Route::get('reservasi', [CsReservasiController::class, 'index'])->name('reservasi.index');
    Route::get('reservasi/{reservasi}', [CsReservasiController::class, 'show'])->name('reservasi.show');
    Route::put('reservasi/{reservasi}/status', [CsReservasiController::class, 'updateStatus'])->name('reservasi.status.update');
    Route::post('reservasi/{reservasi}/catatan', [CsReservasiController::class, 'storeCatatan'])->name('reservasi.catatan.store');
	Route::post('reservasi/{reservasi}/panggil-ke-loket', [CsReservasiController::class, 'panggilKeLoket'])->name('reservasi.panggil-ke-loket');
	Route::post('reservasi/{reservasi}/tandai-sinkron-fisik', [CsReservasiController::class, 'tandaiSinkronFisik'])
		->name('reservasi.tandai-sinkron-fisik');
	Route::post('reservasi/{reservasi}/cek-sinkron-otomatis', [CsReservasiController::class, 'cekSinkronOtomatis'])
		->name('reservasi.cek-sinkron-otomatis');
	Route::get('notifikasi/cek-reservasi-baru', [\App\Http\Controllers\Cs\NotifikasiController::class, 'cekReservasiBaru'])
		->name('notifikasi.cek-reservasi-baru');

    Route::get('kalender-jadwal', [CsKalenderController::class, 'index'])->name('kalender.index');

    Route::get('panduan', [CsPanduanController::class, 'index'])->name('panduan.index');

    Route::get('profil', [CsProfilController::class, 'index'])->name('profil.index');
    Route::put('profil', [CsProfilController::class, 'update'])->name('profil.update');
});

Route::prefix('system')->name('system.')->group(function () {
    Route::get('/error-demo', [ErrorDemoController::class, 'index'])->name('error-demo');
});

