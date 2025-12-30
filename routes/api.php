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

Route::get('/mst-akun', [BukuBesarApi::class, 'listMstAkun']);
Route::post('/saldo-awal', [BukuBesarApi::class, 'storeSaldoAwal']);

Route::get('/laporan/laba-rugi', [LaporanKeuanganApi::class, 'getLabaRugi']);
Route::get('/laporan/neraca', [LaporanKeuanganApi::class, 'getNeraca']);

