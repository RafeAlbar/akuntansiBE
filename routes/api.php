<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

use App\Http\Controllers\Api\BukuBesarApi;
use App\Http\Controllers\Api\LaporanKeuanganApi;
use App\Http\Controllers\Api\TransaksiApi;
use App\Http\Controllers\Api\BepApi;
use App\Http\Controllers\Api\FakturApi;

Route::get('/mst-akun', [BukuBesarApi::class, 'listMstAkun']);
Route::post('/saldo-awal', [BukuBesarApi::class, 'storeSaldoAwal']);

Route::get('/laporan/laba-rugi', [LaporanKeuanganApi::class, 'getLabaRugi']);
Route::get('/laporan/neraca', [LaporanKeuanganApi::class, 'getNeraca']);

Route::post('/buku_besar/storetransaksi', [BukuBesarApi::class, 'storetransaksi'])->name('transaksi.store');

Route::get('buku_besar/list_pemasok', [BukuBesarApi::class, 'listPemasok'])->name('listPemasok');
Route::get('buku_besar/list_pelanggan', [BukuBesarApi::class, 'listPelanggan'])->name('listPelanggan');
Route::get('/barang-by-pemasok', [TransaksiApi::class, 'getBarangByPemasok'])->name('barangByPemasok');
Route::get('/barang-semua', [TransaksiApi::class, 'getBarangSemua'])->name('barangSemua');

Route::post('/inventaris/store', [TransaksiApi::class, 'store'])->name('store');     

Route::get('buku_besar/get_buku_besar', [BukuBesarApi::class, 'getBukuBesar'])->name('getBukuBesar');
Route::get('buku_besar/get_jurnal', [BukuBesarApi::class, 'getJurnal'])->name('getJurnal');


Route::prefix('bep')->name('bep.')->group(function () {
    Route::get('/', [BepApi::class, 'index'])->name('index');
    Route::get('/akun-beban', [BepApi::class, 'getAkunBeban'])
        ->name('akun-beban');
    Route::get('/penjualan', [BepApi::class, 'getPenjualan'])->name('penjualan');
});

Route::prefix('faktur')->name('faktur.')->group(function () {
        Route::get('/', [FakturApi::class, 'index'])->name('index');
        Route::get('/dt/transaksi', [FakturApi::class, 'datatableTransaksi'])->name('dt.transaksi');
        Route::get('/{no}/cetak', [FakturApi::class, 'print'])->name('cetak');
        Route::get('/{no}/export/pdf', [FakturApi::class, 'exportPdf'])->name('export.pdf');
    });