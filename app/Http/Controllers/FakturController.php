<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\DatBarangModel;

use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\BinaryFileResponse;


class FakturController extends Controller
{
    public function index()
    {
        return view('faktur.index');
    }

 public function datatableTransaksi()
{
    // [change] ambil id user login untuk filter created_by
    $userId = $this->userId; 

    $agg = DB::table('dat_transaksi as t')
        ->select(
            't.no_transaksi',
            DB::raw('MIN(t.tgl) as tgl'),
            DB::raw('MAX(t.jenis_transaksi) as jenis_transaksi'),
            DB::raw('MAX(t.id_kontak) as id_kontak'),
            DB::raw('SUM(t.jml_barang) as qty'),
            DB::raw('SUM(t.total) as total')
        )
       
        ->when($userId, function ($q) use ($userId) {
            $q->where('t.created_by', $userId);
        })
        ->groupBy('t.no_transaksi');

    $x = DB::query()->fromSub($agg, 'x')
        ->leftJoin('dat_pelanggan as pl', function($j){
            $j->on('pl.id_pelanggan', '=', 'x.id_kontak')
              ->where('x.jenis_transaksi', '=', 1);
        })
        ->leftJoin('dat_pemasok as ps', function($j){
            $j->on('ps.id_pemasok', '=', 'x.id_kontak')
              ->where('x.jenis_transaksi', '=', 2);
        })
        ->select([
            'x.no_transaksi',
            'x.tgl',
            'x.jenis_transaksi',
            DB::raw('COALESCE(pl.nama_pelanggan, ps.nama_pemasok) as nama_kontak'),
            'x.qty',
            'x.total',
            DB::raw("(SELECT b.nama_barang 
                      FROM dat_transaksi t2 
                      JOIN dat_barang b ON b.id_barang = t2.id_barang
                      WHERE t2.no_transaksi = x.no_transaksi
                      ORDER BY t2.id_transaksi ASC
                      LIMIT 1) as deskripsi")
        ])
        ->orderByDesc('x.tgl')
        ->orderByDesc('x.no_transaksi')
        ->get();

    $rows = $x->map(function($r){
        switch ((int)$r->jenis_transaksi) {
            case 1: $tipe = 'Penjualan'; break;
            case 2: $tipe = 'Inventaris'; break;
            case 3: $tipe = 'Kas & Bank'; break;
            default: $tipe = 'Inventaris';
        }

        return [
            'tgl'           => $r->tgl,
            'tipe_label'    => $tipe,
            'no_transaksi'  => $r->no_transaksi,
            'nama_kontak'   => $r->nama_kontak ?: '-',
            'deskripsi'     => $r->deskripsi ?: '-',
            'qty'           => (float) $r->qty,
            'total'         => (float) $r->total,
        ];
    });

    return response()->json(['data' => $rows]);
}





  public function print(string $no): View|RedirectResponse
    {
        $data = $this->getInvoiceData($no);
        if (!$data) abort(404);

        return view('faktur.cetak', [
            'header'     => $data['header'],
            'namaKontak' => $data['namaKontak'],
            'items'      => $data['items'],
            'isPdf'      => false,
        ]);
    }

  
    public function exportPdf(string $no, string $width = '80')
    {
        if (!class_exists(Pdf::class)) {
            return redirect()->route('faktur.cetak', $no)
                ->with('warning', 'Paket dompdf belum terpasang. Menampilkan halaman cetak.');
        }

        $data = $this->getInvoiceData($no);
        if (!$data) abort(404);

        // Konversi mm -> pt
        $mmToPt = fn(float $mm) => $mm * 2.83465;

        // Lebar kertas thermal (pt)
        $paperWidthPt = $mmToPt($width === '58' ? 58 : 80);

        // Tinggi dinamis: base + per item (kasar)
        $basePt   = 250;                    // header + meta + total
        $perItem  = 22;                     // estimasi tinggi per baris item
        $count    = max(count($data['items']), 1);
        $heightPt = $basePt + ($perItem * $count);

        // Ukuran custom untuk DomPDF: [0,0,width,height] (pt)
        $customPaper = [0, 0, $paperWidthPt, $heightPt];

        // (Opsional) logo base64 agar selalu tampil
        $logoBase64 = null; $logoMime = 'png';
        $logoPath = public_path('images/logo.png'); // ganti sesuai asetmu
        if (is_file($logoPath)) {
            $logoBase64 = base64_encode(file_get_contents($logoPath));
            $ext = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
            $logoMime = $ext === 'jpg' ? 'jpeg' : $ext;
        }

        $pdf = Pdf::loadView('faktur.pdf', [
            'header'     => $data['header'],
            'namaKontak' => $data['namaKontak'],
            'items'      => $data['items'],
            'logoBase64' => $logoBase64,
            'logoMime'   => $logoMime,
            'widthMm'    => $width === '58' ? 58 : 80,
        ])
        ->setPaper($customPaper, 'portrait')
        ->set_option('dpi', 120)
        ->set_option('defaultFont', 'DejaVu Sans')     // dukung UTF-8
        ->set_option('isRemoteEnabled', true);

        $fname = "struk_{$no}_{$width}mm.pdf";
        return $pdf->download($fname);
    }

    // ================== Common data ==================
    private function getInvoiceData(string $no): ?array
    {
        $userId = $this->userId;

       $header = DB::table('dat_transaksi as t')
        ->select(
            't.no_transaksi',
            DB::raw('MIN(t.tgl) as tgl'),
            DB::raw('MAX(t.jenis_transaksi) as jenis_transaksi'),
            DB::raw('MAX(t.id_kontak) as id_kontak'),
            DB::raw('MAX(t.diskon) as diskon_persen'),
            DB::raw('MAX(t.biaya_lain) as biaya_lain'),
            DB::raw('SUM(t.pajak) as pajak_nominal'),
            DB::raw('SUM(t.jml_barang) as qty'),
            DB::raw('SUM(t.total) as total')
        )
        ->where('t.no_transaksi', $no)
         ->when($userId, function ($q) use ($userId) {
            $q->where('t.created_by', $userId);
        })
        ->groupBy('t.no_transaksi')
        ->first();


        if (!$header) return null;

        $namaKontak = ((int)$header->jenis_transaksi === 1)
            ? DB::table('dat_pelanggan')->where('id_pelanggan', $header->id_kontak)->value('nama_pelanggan')
            : DB::table('dat_pemasok')->where('id_pemasok', $header->id_kontak)->value('nama_pemasok');

        $items = DB::table('dat_transaksi as t')
            ->join('dat_barang as b', 'b.id_barang', '=', 't.id_barang')
            ->select('b.nama_barang','t.jml_barang as qty', 't.harga_mentah','t.subtotal','t.total')
            ->where('t.no_transaksi', $no)
             ->when($userId, function ($q) use ($userId) {
            $q->where('t.created_by', $userId);
             })
            ->orderBy('t.id_transaksi')
            ->get();

        return [
            'header'     => $header,
            'namaKontak' => $namaKontak ?: '-',
            'items'      => $items,
        ];
    }
}
