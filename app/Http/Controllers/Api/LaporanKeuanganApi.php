<?php
namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\MstAkunModel;

class LaporanKeuanganApi
{
    public function index()
    {
        return view('laporan_keuangan.index');
    }
      public function bukbes()
    {
        return view('laporan_keuangan.bukbes');
    }
       public function jurnal()
    {
        return view('laporan_keuangan.jurnal');
    }
public function getLabaRugi(Request $request)
{
    $q       = trim($request->input('search', ''));
    $page    = max(1, (int) $request->input('page', 1));
    $perPage = min(100, max(1, (int) $request->input('per_page', 20)));
    $userId = (int) $request->input('user_id', 0);

    // ===== Ambil data dari transaksi
    $trxRows = DB::table('dat_detail_transaksi as ddt')
        ->leftJoin('mst_akun as a1', 'a1.kode_akun', '=', 'ddt.kode_akun')
        ->where('ddt.jenis_laporan', 1)
        ->where('a1.created_by', $userId)
        ->selectRaw("
            ddt.id_detail as id_row,
            'trx' as sumber,
            COALESCE(a1.nama_akun, CAST(ddt.kode_akun AS CHAR)) as nama_akun,
            a1.kategori_akun as kategori_akun,
            ddt.kode_akun as kode_akun,
            ddt.jml_debit  as debet,
            ddt.jml_kredit as kredit
        ")
        ->when($q !== '', function($w) use ($q) {
            $like = '%'.$q.'%';
            $w->where(function($x) use ($like) {
                $x->where('a1.nama_akun', 'like', $like)
                  ->orWhere('ddt.kode_akun', 'like', $like)
                  ->orWhere('a1.kode_akun', 'like', $like);
            });
        })
        ->get();

    // ===== Ambil data dari jurnal
    $jurRows = DB::table('dat_detail_jurnal as ddj')
        ->leftJoin('mst_akun as a2', 'a2.id', '=', 'ddj.id_akun')
        ->where('ddj.jenis_laporan', 1)
        ->where('a2.created_by', $userId)
        ->where('ddj.created_by', $userId)
        ->selectRaw("
            ddj.id_detail as id_row,
            'jur' as sumber,
            COALESCE(a2.nama_akun, CAST(ddj.id_akun AS CHAR)) as nama_akun,
            a2.kategori_akun as kategori_akun,
            a2.kode_akun as kode_akun,
            ddj.jml_debit  as debet,
            ddj.jml_kredit as kredit
        ")
        ->when($q !== '', function($w) use ($q) {
            $like = '%'.$q.'%';
            $w->where(function($x) use ($like) {
                $x->where('a2.nama_akun', 'like', $like)
                  ->orWhere('a2.kode_akun', 'like', $like);
            });
        })
        ->get();

    // ===== Gabungkan & casting angka
    $all = $trxRows->concat($jurRows)->map(function ($r) {
        $r->debet  = (float) $r->debet;
        $r->kredit = (float) $r->kredit;
        return $r;
    });

    // ===== Akumulasi per KODE_AKUN
    $grouped = $all->groupBy('kode_akun')->map(function ($rows, $kode) {
        $first = $rows->first();
        return (object)[
            'id_row'        => null,
            'sumber'        => 'akumulasi',
            'nama_akun'     => $first->nama_akun ?: (string)$kode,
            'kategori_akun' => $first->kategori_akun,
            'kode_akun'     => (string)$kode,
            'debet'         => $rows->sum('debet'),
            'kredit'        => $rows->sum('kredit'),
        ];
    })->values();

    // ===== Filter lagi setelah akumulasi (kalau ada search)
    if ($q !== '') {
        $grouped = $grouped->filter(function($r) use ($q) {
            return mb_stripos((string)$r->nama_akun, $q) !== false
                || mb_stripos((string)$r->kode_akun, $q) !== false;
        })->values();
    }

    // ===== Urutkan: murni berdasarkan KODE_AKUN (numeric)
    $grouped = $grouped->sortBy(function ($r) {
        $kodeNum = (int) preg_replace('/\D/', '', (string) $r->kode_akun);
        return $kodeNum;
    }, SORT_NUMERIC)->values();

    // =========================
    //  HITUNG LABA BERSIH
    //  (LOGIKA SAMA DENGAN JS)
    // =========================
    $items = $grouped->map(function ($r) {
        $debet = (float) $r->debet;
        $kredit = (float) $r->kredit;
        $kat = mb_strtolower((string) $r->kategori_akun);
        $nama = mb_strtolower((string) $r->nama_akun);
        $kode = (string) $r->kode_akun;

        // klasifikasi jenis
        $jenis = null;
        $isPendapatan = ($kat === 'pendapatan');
        $isHpp = ($kode === '5104'
                  || (int)$kode === 5104
                  || preg_match('/hpp|harga pokok/i', $nama));
        $isPenjualan = $isPendapatan && (
            preg_match('/(penjualan|sales)/i', $nama) ||
            preg_match('/^(40|41)\d{2,}$/', $kode)
        );

        if ($isPendapatan) {
            $jenis = $isPenjualan ? 'penjualan' : 'pendapatan_lain';
        } else {
            $jenis = $isHpp ? 'hpp' : 'beban';
        }

        // nilai basis (positif untuk tampilan)
        $nilai = ($jenis === 'penjualan' || $jenis === 'pendapatan_lain')
            ? ($kredit - $debet)   // pendapatan
            : ($debet - $kredit);  // HPP / beban

        return (object)[
            'nama_akun'     => $r->nama_akun,
            'kategori_akun' => $r->kategori_akun,
            'kode_akun'     => $r->kode_akun,
            'jenis'         => $jenis,
            'nilai'         => max(0, $nilai),
            'debet'         => $debet,
            'kredit'        => $kredit,
        ];
    });

    $totalPenjualan   = $items->where('jenis', 'penjualan')->sum('nilai');
    $totalHpp         = $items->where('jenis', 'hpp')->sum('nilai');
    $totalPendLain    = $items->where('jenis', 'pendapatan_lain')->sum('nilai');
    $totalPendNet     = $totalPenjualan + $totalPendLain - $totalHpp;
    $totalBeban       = $items->where('jenis', 'beban')->sum('nilai');
    $labaBersih       = $totalPendNet - $totalBeban;

    // ===== Pagination
    $total  = $grouped->count();
    $offset = ($page - 1) * $perPage;
    $rows   = $grouped->slice($offset, $perPage)->values();

    // ===== Response
    return response()->json([
        'ok'              => true,
        'data'            => $rows,
        'total'           => $total,
        'page'            => $page,

        // tambahan buat dashboard
        'total_penjualan'    => $totalPenjualan,
        'total_hpp'          => $totalHpp,
        'total_pend_lain'    => $totalPendLain,
        'total_pendapatan'   => $totalPendNet,
        'total_beban'        => $totalBeban,
        'laba_bersih'        => $labaBersih,
    ]);
}






    public function getNeraca(Request $request)
    {
        $q       = trim($request->input('search', ''));
        $page    = max(1, (int) $request->input('page', 1));
        $perPage = min(100, max(1, (int) $request->input('per_page', 20)));
        $userId = (int) $request->input('user_id', 0);
        
        $query = MstAkunModel::query()
            ->select('id', 'nama_akun', 'kategori_akun', 'saldo_berjalan', 'kode_akun')
            ->where('created_by', $userId)
            ->when($q !== '', function ($w) use ($q) {
                $like = '%'.$q.'%';
                $w->where('nama_akun', 'like', $like)
                ->orWhere('kategori_akun', 'like', $like);
            })
            ->orderBy('kategori_akun')
            ->orderBy('kode_akun', 'asc');

        $all = $query->get()
            ->map(function ($r) {
                return [
                    'id'            => $r->id,
                    'kode_akun'     => $r->kode_akun,
                    'nama_akun'     => $r->nama_akun,
                    'kategori_akun' => $r->kategori_akun,
                    'saldo'         => (float) $r->saldo_berjalan,
                ];
            });

       $grouped = [
                    'aset'       => $all->filter(fn($r) => str_contains(strtolower($r['kategori_akun']), 'aset'))
                                        ->sortBy('kode_akun')->values(),
                    'liabilitas' => $all->filter(fn($r) => str_contains(strtolower($r['kategori_akun']), 'liabilitas'))
                                        ->sortBy('kode_akun')->values(),
                    'ekuitas'    => $all->filter(fn($r) => str_contains(strtolower($r['kategori_akun']), 'ekuitas'))
                                        ->sortBy('kode_akun')->values(),
                ];

        $total  = $all->count();
        $offset = ($page - 1) * $perPage;

        // Slice hasil per page
        $rows = $all->slice($offset, $perPage)->values();

        return response()->json([
            'ok'    => true,
            'data'  => $grouped,
            'total' => $total,
            'page'  => $page,
        ]);
    }

}
