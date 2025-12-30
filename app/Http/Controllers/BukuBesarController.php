<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Models\MstAkunModel;
use App\Models\PemasokModel;
use App\Models\PelangganModel;

use App\Models\DatBarangModel;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class BukuBesarController extends Controller
{
    public function index()
    {
        $mstAkun = MstAkunModel::orderBy('kode_akun')->get(['id','kode_akun','nama_akun']);

        return view('buku_besar.index', compact('mstAkun'));
    }


    public function storeakun(Request $r)
    {
        $data = $r->validate([
            'kode_akun'     => ['required','string','max:50','unique:mst_akun,kode_akun'],
            'nama_akun'     => ['required','string','max:150'],
            'kategori_akun' => ['required','string','max:100'],
            'saldo_awal'    => ['nullable','string'],
            'saldo_berjalan'=> ['nullable','string'],
            'status_aktif'  => ['nullable'],
        ]);

        $akun = MstAkunModel::create([
            'kode_akun'      => $data['kode_akun'],
            'nama_akun'      => $data['nama_akun'],
            'kategori_akun'  => $data['kategori_akun'],
            'saldo_awal'     => $data['saldo_awal'] ?? '0',
            'saldo_berjalan' => $data['saldo_berjalan'] ?? '0',
            'status_aktif'   => (bool)($data['status_aktif'] ?? 1),
            'created_by'     => $userId,
        ]);

        return response()->json([
            'ok'   => true,
            'data' => $akun,
        ]);
    }

     public function listMstAkun() //terpakai
    {
       $items = MstAkunModel::where('created_by', $this->userId)
        ->orderBy('kode_akun')
        ->get(['id','kode_akun','nama_akun']);

        return response()->json([
            'ok'   => true,
            'data' => $items,
        ]);
    }

   public function storeSubAkun(Request $request)
    {
        $data = $request->validate([
            'mst_akun_id' => ['required', 'exists:mst_akun,id'],
            'nama_sub'    => ['required', 'string', 'max:150'],
        ]);

        // ambil akun induk
        $parent = MstAkunModel::findOrFail($data['mst_akun_id']);

        $last = DatAkunModel::where('mst_akun_id', $parent->id)
                ->orderByDesc('kode_sub')
                ->value('kode_sub');

        $next = 1;

        if ($last) {
            $suffix = (int) substr($last, -3);
            $next = $suffix + 1;
        }

        $kodeSub = $parent->kode_akun . str_pad($next, 3, '0', STR_PAD_LEFT);

        $sub = DatAkunModel::create([
            'mst_akun_id'   => $parent->id,
            'kode_sub'      => $kodeSub,
            'nama_sub'      => $data['nama_sub'],
            'saldo_awal'    => '0',
            'saldo_berjalan'=> '0',
            'status_aktif'  => true,
        ]);

        return response()->json(['ok' => true, 'data' => $sub]);
    }

    public function listAkunFlat(Request $r)
{
    $masters = MstAkunModel::with([
        'subAkuns:id,mst_akun_id,kode_sub,nama_sub'
    ])
    ->where('created_by', $this->userId)
    ->orderBy('kode_akun')
    ->get(['id','kode_akun','nama_akun','kategori_akun']);

    $rows = [];
    foreach ($masters as $m) {
        $rows[] = [
            'is_sub'        => false,
            'id'            => $m->id,
            'kode'          => $m->kode_akun,      
            'nama_akun'     => $m->nama_akun,      
            'sub_akun'      => null,            
            'kategori_akun' => $m->kategori_akun,
        ];

        foreach ($m->subAkuns as $s) {
            $rows[] = [
                'is_sub'        => true,
                'id'            => $s->id,
                'kode'          => $s->kode_sub,     
                'nama_akun'     => null,             
                'sub_akun'      => $s->nama_sub,     
                'kategori_akun' => $m->kategori_akun 
            ];
        }
    }

    if ($r->boolean('dt')) {
        return DataTables::of($rows)->make(true);
    }

    return response()->json(['ok' => true, 'data' => $rows]);
}

public function subAkunList(Request $r)
{
    $id = $r->get('mst_akun_id');
    $items = DatAkunModel::where('mst_akun_id', $id)
        ->orderBy('kode_sub')
        ->get(['id','kode_sub','nama_sub']);

    return response()->json(['ok' => true, 'data' => $items]);
}
public function storeSaldoAwal(Request $r)
{
    $mstId   = $r->input('mst_akun_id');
    $subIds  = $r->input('sub_akun_id', []);
    $nominal = $r->input('nominal', []);
    $tanggal = $r->input('tanggal', []);
    
    if (!$mstId) {
        return response()->json(['ok'=>false,'message'=>'Kode akun wajib diisi'], 422);
    }

    $clean = static function($v){
        $n = (int) preg_replace('/[^\d\-]/', '', (string)$v);
        return max(0, $n);
    };
    $nominal = array_map($clean, (array)$nominal);

    $count = min(count((array)$subIds), count($nominal));
    if (!is_array($tanggal)) {
        $tanggal = array_fill(0, $count, (string)$tanggal);
    } else {
        $tanggal = array_values($tanggal);
        if (count($tanggal) < $count) {
            $last = end($tanggal) ?: now()->toDateString();
            $tanggal = array_pad($tanggal, $count, $last);
        }
    }

    $total = 0;
    for ($i=0; $i<$count; $i++) {
        $total += (int)$nominal[$i];
    }

     $userId = $this->userId;
        
    try {
        DB::transaction(function () use ($mstId, $subIds, $nominal, $tanggal, $count, $total, $userId) {

            /** @var MstAkunModel $mst */
          $mst = MstAkunModel::where('created_by', $userId)
                ->where('id', $mstId)      // [CHANGES] filter pakai kolom id
                ->lockForUpdate()
                ->firstOrFail();


            $toInt = static function($v){
                return (int) preg_replace('/[^\d\-]/', '', (string)($v ?? '0'));
            };

            // Natur akun (debit/kredit)
            $akunKode = (string) ($mst->kode_akun ?? '');
            $isDebitNature = in_array($akunKode, ['1101','1103','1104'])
                ? true
                : (in_array($akunKode, ['2101','2201']) ? false : in_array(substr($akunKode, 0, 1), ['1','5']));

           $akunTambahModal = [
                '1101','1102','1103','1104','1105','1106',
                '1201','1202','1203','1204',
                '4101',
                '4103', // NOTE: 4103 juga ada di grup pengurang, di-logika bawah akan diikuti sebagai pengurang
                '4510',
                '4511',
            ];

            $akunKurangiModal = [
                '2101','2102','2103','2104','2105','2106',
                '2201','2202',
                '4102',
                '4103', // muncul di dua grup → diperlakukan sebagai pengurang supaya tidak double efek
                '5104',
                '6101','6102','6103',
                '6201','6202','6203','6204','6205','6208','6209','6210','6211','6212','6213',
                '6301','6302',
            ];

            // Akun pasangan: Modal 3101
            /** @var MstAkunModel $modalAcc */
            $modalAcc = MstAkunModel::where('kode_akun', '3101')
                ->where('created_by', $userId)
                ->lockForUpdate()
                ->firstOrFail();
            $modalId  = (int) $modalAcc->id;

            // Update saldo akun induk
            $currAwal  = $toInt($mst->saldo_awal ?? '0');
            $currJalan = $toInt($mst->saldo_berjalan ?? '0');
            $mst->saldo_awal      = (string)($currAwal + $total);
            $mst->saldo_berjalan  = (string)($currJalan + $total);
            $mst->save();

            // Update saldo sub-akun (jika ada)
            // $bySub = [];
            // for ($i=0; $i<$count; $i++) {
            //     $sid = $subIds[$i] ?? null;
            //     $val = $nominal[$i] ?? 0;
            //     if (!$sid || $val <= 0) continue;
            //     $bySub[$sid] = ($bySub[$sid] ?? 0) + $val;
            // }

            // if (!empty($bySub)) {
            //     $subs = DatAkunModel::whereIn('id', array_keys($bySub))
            //             ->lockForUpdate()
            //             ->get();

            //     foreach ($subs as $sub) {
            //         $sAwal  = $toInt($sub->saldo_awal ?? '0');
            //         $sJalan = $toInt($sub->saldo_berjalan ?? '0');
            //         $add    = $bySub[$sub->id];
            //         $sub->saldo_awal     = (string)($sAwal + $add);
            //         $sub->saldo_berjalan = (string)($sJalan + $add);
            //         $sub->save();
            //     }
            // }

            // ===== Agregasi per-periode (periode diambil dari TANGGAL REQUEST) =====
            $aggPerPeriode = [];  // ['YYYY-MM' => ['debit'=>x, 'kredit'=>y]]
            for ($i=0; $i<$count; $i++) {
                $val = (int)($nominal[$i] ?? 0);
                if ($val <= 0) continue;

                // ambil raw dari request
                $tglRaw = (string) ($tanggal[$i] ?? '');
                // normalisasi ke Y-m-d untuk penyimpanan DATE
                try {
                    $tglStore  = \Carbon\Carbon::parse($tglRaw)->toDateString();
                    $periode   = \Carbon\Carbon::parse($tglRaw)->format('Y-m');
                } catch (\Throwable $e) {
                    // fallback bila parse gagal
                    $tglStore  = now()->toDateString();
                    $periode   = now()->format('Y-m');
                }

                if (!isset($aggPerPeriode[$periode])) $aggPerPeriode[$periode] = ['debit'=>0, 'kredit'=>0];
                if ($isDebitNature)  $aggPerPeriode[$periode]['debit']  += $val;
                else                 $aggPerPeriode[$periode]['kredit'] += $val;
            }

            // Agregasi untuk akun pasangan (dibalik)
            $aggPerPeriodeModal = [];
            foreach ($aggPerPeriode as $periode => $dk) {
                $aggPerPeriodeModal[$periode] = [
                    'debit'  => $dk['kredit'],
                    'kredit' => $dk['debit'],
                ];
            }

            // Helper akumulasi buku besar (builder baru tiap call)
            $applyBB = static function (int $akunId, string $periode, int $debit, int $kredit) use ($toInt, $userId) {
                $row = DB::table('dat_buku_besar')
                    ->lockForUpdate()
                    ->where('created_by', $userId)
                    ->where('id_akun', $akunId)
                    ->where('periode', $periode)
                    ->first();

                if ($row) {
                    $newDebit  = $toInt($row->ttl_debit)  + $debit;
                    $newKredit = $toInt($row->ttl_kredit) + $kredit;

                    DB::table('dat_buku_besar')
                        ->where('created_by', $userId)
                        ->where('id_akun', $akunId)
                        ->where('periode', $periode)
                        ->update([
                            'ttl_debit'   => (string) $newDebit,
                            'ttl_kredit'  => (string) $newKredit,
                            'saldo_akhir' => (string) ($newDebit - $newKredit),
                            'updated_at'  => now(),
                        ]);
                } else {
                    DB::table('dat_buku_besar')->insert([
                        'id_akun'     => $akunId,
                        'periode'     => $periode,
                        'ttl_debit'   => (string) $debit,
                        'ttl_kredit'  => (string) $kredit,
                        'saldo_akhir' => (string) ($debit - $kredit),
                        'created_by'     => $userId,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                }
            };

            // Update buku besar
            foreach ($aggPerPeriode as $periode => $dk) {
                $applyBB((int) $mstId, $periode, (int) $dk['debit'], (int) $dk['kredit']);
            }
            foreach ($aggPerPeriodeModal as $periode => $dk) {
                $applyBB((int) $modalId, $periode, (int) $dk['debit'], (int) $dk['kredit']);
            }

            // Penyesuaian saldo akun modal
            // if ($total > 0) {
            //     if (in_array($akunKode, ['1101','1103','1104'], true)) {
            //         $mAwal  = $toInt($modalAcc->saldo_awal ?? '0');
            //         $mJalan = $toInt($modalAcc->saldo_berjalan ?? '0');
            //         $modalAcc->saldo_awal     = (string)($mAwal + $total);
            //         $modalAcc->saldo_berjalan = (string)($mJalan + $total);
            //         $modalAcc->save();
            //     } elseif (in_array($akunKode, ['2101','2201'], true)) {
            //         $mAwal  = $toInt($modalAcc->saldo_awal ?? '0');
            //         $mJalan = $toInt($modalAcc->saldo_berjalan ?? '0');
            //         $modalAcc->saldo_awal     = (string)($mAwal - $total);
            //         $modalAcc->saldo_berjalan = (string)($mJalan - $total);
            //         $modalAcc->save();
            //     }
            // }

             if ($total > 0) {
                $mAwal  = $toInt($modalAcc->saldo_awal ?? '0');
                $mJalan = $toInt($modalAcc->saldo_berjalan ?? '0');

                $isTambahModal  = in_array($akunKode, $akunTambahModal, true)
                    && !in_array($akunKode, $akunKurangiModal, true); // jika dobel, diprioritaskan sebagai pengurang
                $isKurangiModal = in_array($akunKode, $akunKurangiModal, true);

                if ($isTambahModal) {
                    // akun yang *menambah* modal → saldo modal 3101 bertambah
                    $modalAcc->saldo_awal     = (string)($mAwal + $total);
                    $modalAcc->saldo_berjalan = (string)($mJalan + $total);
                    $modalAcc->save();
                } elseif ($isKurangiModal) {
                    // akun yang *mengurangi* modal → saldo modal 3101 berkurang
                    $modalAcc->saldo_awal     = (string)($mAwal - $total);
                    $modalAcc->saldo_berjalan = (string)($mJalan - $total);
                    $modalAcc->save();
                }
            }
            // ===== Jurnal per TANGGAL REQUEST =====
            // kunci: tanggal disimpan dari request (dinormalisasi ke Y-m-d)
            $aggPerTanggal = [];  // ['Y-m-d' => total]
            for ($i=0; $i<$count; $i++) {
                $val = (int)($nominal[$i] ?? 0);
                if ($val <= 0) continue;

                $tglRaw = (string) ($tanggal[$i] ?? '');
                try {
                    $tglStore = \Carbon\Carbon::parse($tglRaw)->toDateString(); // simpan sesuai request
                } catch (\Throwable $e) {
                    $tglStore = now()->toDateString();
                }

                $aggPerTanggal[$tglStore] = ($aggPerTanggal[$tglStore] ?? 0) + $val;
            }

            foreach ($aggPerTanggal as $tglStore => $amt) {
                $noReferensi = 'SA-' . ($mst->kode_akun ?? $mstId) . '-' . \Carbon\Carbon::parse($tglStore)->format('Ymd');
                $ket = 'Saldo Awal ' . ($mst->nama_akun ?? 'Akun');

                $idJurnal = DB::table('dat_header_jurnal')->insertGetId([
                    'tgl_transaksi' => $tglStore,   // header pakai tanggal request
                    'no_referensi'  => $noReferensi,
                    'keterangan'    => $ket,
                    'modul_sumber'  => 'Saldo Awal',
                    'created_by' => $userId,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);

                $userId = session('user_id');
                  $saldoAkunAfter = $toInt(
                    DB::table('mst_akun')
                        ->where('id', $mstId)
                        ->where('created_by', $userId)
                        ->lockForUpdate()
                        ->value('saldo_berjalan')
                );

                $saldoModalAfter = $toInt(
                    DB::table('mst_akun')
                        ->where('id', $modalId)
                        ->where('created_by', $userId)
                        ->lockForUpdate()
                        ->value('saldo_berjalan')
                );
// dd($saldoAkunAfter);
                if ($isDebitNature) {
                    // Dr Akun Induk, Cr Modal
                    DB::table('dat_detail_jurnal')->insert([
                        [
                            'id_jurnal'       => $idJurnal,
                            'id_akun'         => (int)$mstId,
                            'jml_debit'       => (int)$amt,
                            'jml_kredit'      => 0,
                            'saldo_berjalan'  => (string) $saldoAkunAfter,
                            'tanggal'         => $tglStore, // ← dari request (Y-m-d)
                            'created_at'      => now(),
                            'created_by'         => $userId,
                            'updated_at'      => now(),
                        ],
                        [
                            'id_jurnal'       => $idJurnal,
                            'id_akun'         => $modalId,
                            'jml_debit'       => 0,
                            'jml_kredit'      => (int)$amt,
                            'saldo_berjalan'  => (string) $saldoModalAfter,
                            'tanggal'         => $tglStore, // ← dari request (Y-m-d)
                            'created_by'         => $userId,
                            'created_at'      => now(),
                            'updated_at'      => now(),
                        ],
                    ]);
                } else {
                    // Dr Modal, Cr Akun Induk
                    DB::table('dat_detail_jurnal')->insert([
                        [
                            'id_jurnal'       => $idJurnal,
                            'id_akun'         => $modalId,
                            'jml_debit'       => (int)$amt,
                            'jml_kredit'      => 0,
                            'saldo_berjalan'  => (string) $saldoModalAfter,
                            'tanggal'         => $tglStore, // ← dari request (Y-m-d)
                            'created_by'         => $userId,
                            'created_at'      => now(),
                            'updated_at'      => now(),
                        ],
                        [
                            'id_jurnal'       => $idJurnal,
                            'id_akun'         => (int)$mstId,
                            'jml_debit'       => 0,
                            'jml_kredit'      => (int)$amt,
                            'saldo_berjalan'  => (string) $saldoAkunAfter,
                            'tanggal'         => $tglStore,
                            'created_by'         => $userId,
                            'created_at'      => now(),
                            'updated_at'      => now(),
                        ],
                    ]);
                }
            }
        });

        return response()->json([
            'ok'      => true,
            'message' => 'Saldo awal & jurnal berhasil disimpan',
            'total'   => $total,
        ]);

    } catch (\Throwable $e) {
        return response()->json([
            'ok'      => false,
            'message' => 'Gagal menyimpan saldo awal',
            'error'   => $e->getMessage(),
        ], 500);
    }
}




protected function getSaldoNormalAkun(string $kodeAkun, string $namaAkun = ''): string
{
    $kodeAkun = preg_replace('/\D/', '', (string) $kodeAkun);
    $namaLower = strtolower((string) $namaAkun);

 
    $map = [
        // 1xx ASET LANCAR
        '1101' => 'DEBIT',  // Kas
        '1102' => 'DEBIT',  // Bank
        '1103' => 'DEBIT',  // Piutang Usaha
        '1104' => 'DEBIT',  // Persediaan Barang Dagang
        '1105' => 'DEBIT',  // Uang Muka Pembelian
        '1106' => 'DEBIT',  // Alat Tulis Kantor

        // 12xx ASET TETAP
        '1201' => 'DEBIT',  // Tanah
        '1202' => 'DEBIT',  // Bangunan
        '1203' => 'DEBIT',  // Peralatan
        '1204' => 'DEBIT',  // Kendaraan
        '1205' => 'KREDIT', // Akumulasi Penyusutan (kontra aset)

        // 21xx UTANG LANCAR
        '2101' => 'KREDIT', // Utang Usaha
        '2102' => 'KREDIT', // Utang Pajak
        '2103' => 'KREDIT', // Utang Gaji & Upah
        '2104' => 'KREDIT', // Utang Biaya
        '2105' => 'KREDIT', // Utang Lainnya
        '2106' => 'KREDIT', // Utang PPN

        // 22xx UTANG JANGKA PANJANG
        '2201' => 'KREDIT', // Utang Bank
        '2202' => 'KREDIT', // Obligasi

        // 3xxx EKUITAS
        '3101' => 'KREDIT', // Modal Disetor
        '3201' => 'KREDIT', // Saldo / Laba
        '3301' => 'DEBIT',  // Dividen / Prive

        // 4xxx PENDAPATAN
        '4101' => 'KREDIT', // Penjualan Barang Dagang
        '4102' => 'KREDIT', // Potongan Penjualan
        '4103' => 'KREDIT', // Retur Penjualan
        '4104' => 'KREDIT', // Penjualan (lainnya)
        '4510' => 'KREDIT', // Pendapatan Bunga
        '4511' => 'KREDIT', // Pendapatan lain-lain

        // 5xxx HPP
        '5104' => 'DEBIT',  // Harga Pokok Penjualan

        // 6xxx BEBAN
        '6101' => 'DEBIT', // Beban Gaji
        '6102' => 'DEBIT', // Beban Listrik
        '6103' => 'DEBIT', // Beban Bank

        '6201' => 'DEBIT', // Beban Gaji Manajemen & Administrasi
        '6202' => 'DEBIT', // Beban Kantor
        '6203' => 'DEBIT', // Beban Penyusutan Kantor
        '6204' => 'DEBIT', // Beban Penyusutan Peralatan
        '6205' => 'DEBIT', // Beban Pajak

        '6208' => 'DEBIT', // Beban Ongkir
        '6209' => 'DEBIT', // Beban Sewa
        '6210' => 'DEBIT', // Beban Listrik Telepon
        '6211' => 'DEBIT', // Beban Pemeliharaan
        '6212' => 'DEBIT', // Beban Lain-lain
        '6213' => 'DEBIT', // Beban Iklan

        '6301' => 'DEBIT', // Beban Bunga
        '6302' => 'DEBIT', // Beban Transportasi
    ];

    if (isset($map[$kodeAkun])) {
        return $map[$kodeAkun];
    }


    $kelompok = substr($kodeAkun, 0, 1);

    if ($kelompok === '1') {
        return 'DEBIT';  // Aset
    }
    if (in_array($kelompok, ['2', '3'], true)) {
        return 'KREDIT'; 
    }
    if ($kelompok === '4') {
        return 'KREDIT';
    }
    if (in_array($kelompok, ['5', '6', '7', '8', '9'], true)) {
        return 'DEBIT'; 
    }

    if (str_contains($namaLower, 'beban')) {
        return 'DEBIT';
    }
    if (str_contains($namaLower, 'pendapatan')) {
        return 'KREDIT';
    }

    return 'DEBIT';
}


        
public function storetransaksi(Request $request)
{
    $request->validate([
        'tipe' => 'required|string',
        'nominal' => 'required|numeric',
        'tanggal' => 'required|date',
        'keterangan' => 'nullable|string',
        'akun_debet_id' => 'nullable|exists:mst_akun,id',
        'akun_kredit_id' => 'nullable|exists:mst_akun,id',
    ]);

    if ($request->tipe === 'Manual') {
        $request->validate([
            'akun_debet_id'  => 'required|exists:mst_akun,id|different:akun_kredit_id',
            'akun_kredit_id' => 'required|exists:mst_akun,id',
        ]);
    }

    $tipe     = $request->tipe;
    $nominal  = (float) $request->nominal;
    $tanggal  = \Carbon\Carbon::parse($request->tanggal)->toDateString();
    $ket      = $request->keterangan ?? null;
    $akunD    = $request->akun_debet_id;
    $akunK    = $request->akun_kredit_id;
    $userId = $this->userId;

    DB::beginTransaction();
    try {
        
        $prefix = 'K'; 
        $jenisCode = 3; 

        $lastNo = DB::table('dat_transaksi')
            ->where('created_by', $userId)
            ->where('no_transaksi', 'like', $prefix . '%')
            ->orderByDesc('id_transaksi')
            ->value('no_transaksi');

        $seq = 0;
        if ($lastNo && preg_match('/\d+$/', $lastNo, $m)) {
            $seq = (int) $m[0];
        }
        $noTransaksi = $prefix . str_pad($seq + 1, 7, '0', STR_PAD_LEFT);


        $toMoney = static function($v){
            $s = preg_replace('/[^\d\-]/', '', (string)$v);
            return (int)($s === '' ? 0 : $s);
        };
        $nominal = $toMoney($request->nominal); 

        $kp = null; $noUtang = null;
        if ($tipe === 'Bayar Utang Usaha') {
            $request->validate([
                'kode_pemasok' => ['required','string','max:50'],
                'no_transaksi' => ['required','string','max:50'],
            ]);
            $kp     = (string)$request->kode_pemasok;
            $noUtang= (string)$request->no_transaksi;

            $totalOutstanding = (int) DB::table('dat_utang')
                ->where('created_by', $userId)
                ->where('kode_pemasok', $kp)
                ->where('no_transaksi', $noUtang)
                ->where('status', 0)
                ->lockForUpdate()
                ->sum('nominal');

            if ($totalOutstanding <= 0) {
                throw new \RuntimeException('Utang sudah lunas / tidak ditemukan.');
            }

            if ($nominal !== $totalOutstanding) {
                throw new \RuntimeException('Nominal bayar harus sama dengan total utang: ' . number_format($totalOutstanding,0,',','.'));
            }
        }

        $toMoney = static function($v){ $s=preg_replace('/[^\d\-]/','',(string)$v); return (int)($s===''?0:$s); };
        $nominal = $toMoney($request->nominal);

        // CHANGES: Piutang
        $idPelanggan = null; $noPiutang = null;
        if ($tipe === 'Bayar Piutang Usaha') {
            $request->validate([
                'id_pelanggan' => ['required','integer'],
                'no_transaksi' => ['required','string','max:50'],
            ]);
            $idPelanggan = (int)$request->id_pelanggan;
            $noPiutang   = (string)$request->no_transaksi;

            $totalOutstandingPiutang = (int)\DB::table('dat_piutang')
                ->where('created_by', $userId)
                ->where('id_pelanggan', $idPelanggan)
                ->where('no_transaksi', $noPiutang)
                ->where('status', 0)
                ->lockForUpdate()
                ->sum('nominal');

            if ($totalOutstandingPiutang <= 0) {
                throw new \RuntimeException('Piutang sudah lunas / tidak ditemukan.');
            }
            if ($nominal !== $totalOutstandingPiutang) {
                throw new \RuntimeException('Nominal bayar harus sama dengan total piutang: ' . number_format($totalOutstandingPiutang,0,',','.'));
            }
        }
        // =========================
        // Transaksi Manual
        // =========================
        if ($tipe === 'Manual') {

            $akunDebet = DB::table('mst_akun')
                ->where('id', $akunD)
                ->where('created_by', $userId)
                ->lockForUpdate()
                ->first();

            $akunKredit = DB::table('mst_akun')
                ->where('id', $akunK)
                ->where('created_by', $userId)
                ->lockForUpdate()
                ->first();

            if (!$akunDebet || !$akunKredit) {
                throw new \RuntimeException('Akun tidak ditemukan.');
            }

            $normalDebet  = $this->getSaldoNormalAkun($akunDebet->kode_akun,  $akunDebet->nama_akun);
            $normalKredit = $this->getSaldoNormalAkun($akunKredit->kode_akun, $akunKredit->nama_akun);

            $hitungSaldoBaru = function (float $saldoAwal, string $normal, string $posisi, float $nominal): float {
                if ($posisi === 'DEBIT') {
                    // posting di sisi DEBIT
                    return $normal === 'DEBIT'
                        ? $saldoAwal + $nominal   
                        : $saldoAwal - $nominal; 
                }

                // posting di sisi KREDIT
                return $normal === 'KREDIT'
                    ? $saldoAwal + $nominal      
                    : $saldoAwal - $nominal;  
            };

            $saldoDebetBaru  = $hitungSaldoBaru((float) $akunDebet->saldo_berjalan,  $normalDebet,  'DEBIT',  $nominal);
            $saldoKreditBaru = $hitungSaldoBaru((float) $akunKredit->saldo_berjalan, $normalKredit, 'KREDIT', $nominal);

          if ($akunK == 1) {
                $saldoKas = (float) DB::table('mst_akun')
                    ->where('id', 1)
                    ->where('created_by', $userId)   // filter multi user
                    ->lockForUpdate()
                    ->value('saldo_berjalan');
                
                if ($saldoKas < $nominal) {
                    throw new \RuntimeException("Saldo kas tidak mencukupi untuk transaksi ini.");
                }
            }


            // === 1) HEADER JURNAL ===
            $idJurnal = DB::table('dat_header_jurnal')->insertGetId([
                'tgl_transaksi' => $tanggal,
                'no_referensi'  => $noTransaksi,
                'keterangan'    => $ket,
                'modul_sumber'  => 'Transaksi Kas/Bank',
                'created_by'    => $userId,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            $periode = Carbon::parse($tanggal)->format('Y-m');
            // === 2) DETAIL JURNAL ===
            foreach ([
                $akunD => ['debit' => $nominal, 'kredit' => 0],
                $akunK => ['debit' => 0,        'kredit' => $nominal],
            ] as $akunId => $val) {

                $bukbes = DB::table('dat_buku_besar')
                    ->where('created_by', $userId)              // [CHANGES]
                    ->where('id_akun', $akunId)
                    ->where('periode', $periode)
                    ->lockForUpdate()
                    ->first();

                if ($bukbes) {
                    DB::table('dat_buku_besar')
                        ->where('created_by', $userId)          // [CHANGES]
                        ->where('id_bukbes', $bukbes->id_bukbes)
                        ->update([
                            'ttl_debit'   => (float)$bukbes->ttl_debit   + (float)$val['debit'],
                            'ttl_kredit'  => (float)$bukbes->ttl_kredit  + (float)$val['kredit'],
                            'saldo_akhir' => (float)$bukbes->saldo_akhir + ((float)$val['debit'] - (float)$val['kredit']),
                            'updated_at'  => now(),
                        ]);
                } else {
                    DB::table('dat_buku_besar')->insert([
                        'id_akun'     => $akunId,
                        'periode'     => $periode,
                        'ttl_debit'   => (float)$val['debit'],
                        'ttl_kredit'  => (float)$val['kredit'],
                        'saldo_akhir' => (float)$val['debit'] - (float)$val['kredit'],
                        'created_by'  => $userId,               // [CHANGES]
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                }
            }
                DB::table('dat_detail_jurnal')->insert([
                [
                    'id_jurnal'      => $idJurnal,
                    'id_akun'        => $akunD,
                    'jml_debit'      => $nominal,
                    'jml_kredit'     => 0,
                    'tanggal'        => $tanggal,
                    'saldo_berjalan' => $saldoDebetBaru,   // [CHANGES]
                    'created_by'     => $userId,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ],
                [
                    'id_jurnal'      => $idJurnal,
                    'id_akun'        => $akunK,
                    'jml_debit'      => 0,
                    'jml_kredit'     => $nominal,
                    'tanggal'        => $tanggal,
                    'saldo_berjalan' => $saldoKreditBaru,  // [CHANGES]
                    'created_by'     => $userId,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ],
            ]);


            // === 3) HEADER TRANSAKSI ===
            $idTransaksi = DB::table('dat_transaksi')->insertGetId([
                'no_transaksi'     => $noTransaksi,
                'tgl'              => $tanggal,
                'jenis_transaksi'  => $jenisCode,
                // 'tipe_pembayaran'  => 1,
                'total'            => $nominal,
                // 'keterangan'       => $ket,
                'created_by'       => $userId,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            // === 4) DETAIL TRANSAKSI ===
            DB::table('dat_detail_transaksi')->insert([
                [
                    'no_transaksi' => $noTransaksi, // 
                    'kode_akun'      => $akunD,
                    'nama_akun'      => 'null',
                    'jenis_laporan'  => 'null',
                    'jml_debit'      => $nominal,
                    'jml_kredit'     => 0,
                    'created_by'       => $userId,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ],
                [
                    'no_transaksi' => $noTransaksi, // 
                    'kode_akun'      => $akunK,
                    'nama_akun'      => 'null',
                    'jenis_laporan'  => 'null',
                    'jml_debit'      => 0,
                    'jml_kredit'     => $nominal,
                    'created_by'       => $userId,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ],
            ]);
            $akunDebet = DB::table('mst_akun')
                ->where('id', $akunD)
                ->where('created_by', $userId)
                ->lockForUpdate()
                ->first();

            $akunKredit = DB::table('mst_akun')
                ->where('id', $akunK)
                ->where('created_by', $userId)
                ->lockForUpdate()
                ->first();

            if (!$akunDebet || !$akunKredit) { // [NEW]
                throw new \RuntimeException('Akun tidak ditemukan.');
            }

            $normalDebet  = $this->getSaldoNormalAkun($akunDebet->kode_akun,  $akunDebet->nama_akun);
            $normalKredit = $this->getSaldoNormalAkun($akunKredit->kode_akun, $akunKredit->nama_akun);
               $hitungSaldoBaru = function (float $saldoAwal, string $normal, string $posisi, float $nominal): float {
                if ($posisi === 'DEBIT') {
                    // posting di sisi DEBIT
                    return $normal === 'DEBIT'
                        ? $saldoAwal + $nominal   // akun normal DEBIT, didebet → saldo naik
                        : $saldoAwal - $nominal;  // akun normal KREDIT, didebet → saldo turun
                }

                // posting di sisi KREDIT
                return $normal === 'KREDIT'
                    ? $saldoAwal + $nominal      // akun normal KREDIT, dikredit → saldo naik
                    : $saldoAwal - $nominal;     // akun normal DEBIT, dikredit → saldo turun
            };

            // [NEW] Hitung saldo baru masing-masing akun
            $saldoDebetBaru  = $hitungSaldoBaru((float) $akunDebet->saldo_berjalan,  $normalDebet,  'DEBIT',  $nominal);
            $saldoKreditBaru = $hitungSaldoBaru((float) $akunKredit->saldo_berjalan, $normalKredit, 'KREDIT', $nominal);

            // [NEW] Update ke mst_akun
            DB::table('mst_akun')
                ->where('id', $akunDebet->id)
                ->where('created_by', $userId)
                ->update([
                    'saldo_berjalan' => $saldoDebetBaru,
                    'updated_at'     => now(),
                ]);

            DB::table('mst_akun')
                ->where('id', $akunKredit->id)
                ->where('created_by', $userId)
                ->update([
                    'saldo_berjalan' => $saldoKreditBaru,
                    'updated_at'     => now(),
                ]);
        }

        
       elseif (in_array($tipe, [
            'Bayar Gaji',
           
            'Bayar Listrik/Telepon/Internet/Air',
            'Bayar Utang Bank',
            'Bayar Utang Usaha',
            'Bayar Piutang Usaha',
            'Bayar Utang Lainnya',
            'Bayar Bunga Bank',
            'Bayar Pajak',
            'Bayar Iklan/Promosi',
            'Bayar Transportasi (Ongkir, BBM, dll)',
            'Bayar Sewa Ruko/Outlet/dll',
            'Bayar Pemeliharaan (Servis, dll)',
            'Bayar Lain-lain',
            'Beli Peralatan Tunai',
            'Beli ATK Tunai',
            'Beli Tanah Tunai',
            'Beli Persediaan Tunai',
            'Membuat/Beli Bangunan Tunai',
            'Beli Kendaraan Tunai',
            'Pengambilan Pribadi',
            'Pinjam Uang di Bank',
            'Pinjam Uang Lainnya',
            'Pendapatan Bunga',
            'Pendapatan Lain-lain (Komisi/Hadiah)',
            'Setoran Pemilik',
            'Jual Tanah',
            'Jual Bangunan',
            'Jual Kendaraan',
            'Jual Jasa',
        ], true)) {

            $map = [
                // Sudah ada
                'Bayar Gaji'                          => [7,  1, 1, 2],
                'Bayar Listrik'                       => [8,  1, 1, 2],
                'Bayar Utang Bank'                    => [14, 1, 2, 2],
                'Bayar Utang Usaha'                   => [5,  1, 2, 2],
                'Bayar Piutang Usaha'                   => [1, 20, 2, 2],
                'Beli Peralatan Tunai'                => [10, 1, 2, 2],
                'Beli ATK Tunai'                      => [11, 1, 2, 2],
                'Beli Persediaan Tunai'               => [6, 1, 2, 2],
                'Pengambilan Pribadi'                 => [12, 1, 2, 2],
                'Pinjam Uang di Bank'                 => [1,  14, 2, 2],
                'Pendapatan Bunga'                    => [1,  58, 2, 1],
                'Setoran Pemilik'                     => [1,  16, 2, 2],

                'Bayar Listrik/Telepon/Internet/Air'  => [69, 1, 1, 2],
                'Bayar Utang Lainnya'                 => [59, 1, 2, 2],
                'Bayar Bunga Bank'                    => [9, 1, 1, 2],
                'Bayar Pajak'                         => [13, 1, 1, 2],
                'Bayar Iklan/Promosi'                 => [61, 1, 1, 2],
                'Bayar Transportasi (Ongkir, BBM, dll)'=> [66, 1, 1, 2],
                'Bayar Sewa Ruko/Outlet/dll'          => [67, 1, 1, 2],
                'Bayar Pemeliharaan (Servis, dll)'    => [70, 1, 1, 2],
                'Bayar Lain-lain'                     => [71, 1, 1, 2],
                'Beli Tanah Tunai'                    => [42, 1, 2, 2],
                'Membuat/Beli Bangunan Tunai'         => [43, 1, 2, 2],
                'Beli Kendaraan Tunai'                => [44, 1, 2, 2],
                'Pinjam Uang Lainnya'                 => [1, 59, 2, 2],
                'Pendapatan Lain-lain (Komisi/Hadiah)'=> [1, 52, 2, 1],
                'Jual Tanah'                          => [1, 42, 2, 2],
                'Jual Bangunan'                       => [1, 43, 2, 2],
                'Jual Kendaraan'                      => [1, 44, 2, 2],
                'Jual Jasa'                           => [1, 17, 2, 1],
            ];


            [$akunD, $akunK, $jlD, $jlK] = $map[$tipe];

            if ($tipe === 'Bayar Utang Bank') {
                $saldoUtangBank = (float) DB::table('mst_akun')
                    ->where('id', 14)
                    ->where('created_by', $this->userId)
                    ->lockForUpdate()
                    ->value('saldo_berjalan');

                if ($saldoUtangBank <= 0) {
                    throw new \RuntimeException('Anda tidak memiliki utang bank.');
                }
            }

            if ($tipe === 'Bayar Utang Usaha') {
                $saldoUtangUsaha = (float) DB::table('mst_akun')
                    ->where('id', 5)
                    ->where('created_by', $this->userId)
                    ->lockForUpdate()
                    ->value('saldo_berjalan');

                if ($saldoUtangUsaha <= 0) {
                    throw new \RuntimeException('Anda tidak memiliki utang usaha.');
                }
            }

            if ($tipe === 'Bayar Utang Usaha') {
                DB::table('dat_utang')
                    ->where('created_by', $userId)
                    ->where('kode_pemasok', $kp)
                    ->where('no_transaksi', $noUtang)
                    ->where('status', 0)
                    ->update([
                        'status'     => 1,
                       
                    ]);
            }
            if ($tipe === 'Bayar Piutang Usaha') {
                DB::table('dat_piutang')
                ->where('created_by', $userId)
                ->where('id_pelanggan', $idPelanggan)
                ->where('no_transaksi', $noPiutang)
                ->where('status', 0)
                ->update(['status' => 1]);
            }

             if ($tipe === 'Bayar Utang Lainnya') {
                $saldoUtangLain = (float) DB::table('mst_akun')
                    ->where('id', 59)
                    ->where('created_by', $userId)
                    ->lockForUpdate()
                    ->value('saldo_berjalan');

                if ($saldoUtangLain <= 0) {
                    throw new \RuntimeException('Anda tidak memiliki utang lainnya.');
                }
            }

           
            $tipeKreditNaik = [                       
                'Setoran Pemilik',
                'Pinjam Uang di Bank',
                'Pinjam Uang Lainnya',
                'Pendapatan Bunga',
                'Pendapatan Lain-lain (Komisi/Hadiah)',
                'Pinjang Uang Lainnya',  
                    
                'Jual Jasa'                 
            ];
            $kreditMenambahSaldo = in_array($tipe, $tipeKreditNaik, true); 

            $tipeDebetBerkurang = [
                'Bayar Utang Bank',
                'Bayar Utang Usaha',
                
                'Bayar Utang Lainnya',
            ];

            if ($akunK == 1) {
                $saldoKas = (float) DB::table('mst_akun')
                    ->where('id', 1)
                    ->where('created_by', $userId)   // ⬅️ filter per user
                    ->lockForUpdate()
                    ->value('saldo_berjalan');
                if ($saldoKas < $nominal) {
                    
                    throw new \RuntimeException("Saldo kas tidak mencukupi untuk transaksi {$tipe}.");
                }
            }
            $debetMengurangiSaldo = in_array($tipe, $tipeDebetBerkurang, true);
            $this->insertJurnalSimple(
                $tanggal,
                (float)$nominal,
                $ket,
                (int)$akunD,
                (int)$akunK,
                (int)$jlD,
                (int)$jlK,
                $noTransaksi,
                'Transaksi Kas/Bank',
                $kreditMenambahSaldo,
                 $debetMengurangiSaldo
            );

            // === HEADER TRANSAKSI ===
            $idTransaksi = DB::table('dat_transaksi')->insertGetId([
                'no_transaksi'     => $noTransaksi,
                'tgl'              => $tanggal,
                'jenis_transaksi'  => $jenisCode,
                'tipe_pembayaran'  => 1,
                'total'            => $nominal,
                'created_by'            => $userId,
                // 'keterangan'       => $tipe,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
         

            // === DETAIL TRANSAKSI ===
            DB::table('dat_detail_transaksi')->insert([
                [
                    'no_transaksi' => $noTransaksi, // ✅ gunakan kode transaksi sesuai FK
                    'kode_akun'      => $akunD,
                    'nama_akun'      => 'null',
                    'jenis_laporan'  => 'null',
                    'jml_debit'      => $nominal,
                    'jml_kredit'     => 0,
                    'created_by'            => $userId,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ],
                [
                    'no_transaksi' => $noTransaksi, // ✅ fix
                    'kode_akun'      => $akunK,
                    'nama_akun'      => 'null',
                    'jenis_laporan'  => 'null',
                    'jml_debit'      => 0,
                    'jml_kredit'     => $nominal,
                    'created_by'            => $userId,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ],
            ]);

            //  if (in_array($tipe, ['Bayar Utang Bank'], true)) {
            //     DB::table('mst_akun')
            //         ->where('id', 14)
            //         ->lockForUpdate()
            //         ->decrement('saldo_berjalan', $nominal);
            // }
           
           $tipeSaldoKeluar = [
                'Bayar Bunga Bank',
                'Bayar Pajak',
                'Bayar Gaji',
                'Bayar Iklan/Promosi',        
                'Bayar Iklan/promosi',
                'Bayar Transportasi',
                'Bayar Transportasi (Ongkir, BBM, dll)', 
                'Bayar Sewa Ruko/Outlet/dll',
                'Bayar Listrik',
                'Bayar Listrik/Telepon/Internet/Air',    
                'Bayar Pemeliharaan',
                'Bayar Pemeliharaan (Servis, dll)',      
                'Bayar Lain-lain',
                'Pendapatan Lain-lain (Komisi/Hadiah)',
            ];

            if (in_array($tipe, $tipeSaldoKeluar, true)) {
                DB::table('mst_akun')
                    ->where('id', 17)
                    ->lockForUpdate()
                    ->decrement('saldo_berjalan', $nominal);
            }

             $tipeSaldoMasuk = [
                'Pendapatan Bunga',
               
            ];

            if (in_array($tipe, $tipeSaldoMasuk, true)) {
                DB::table('mst_akun')
                    ->where('id', 17)
                    ->lockForUpdate()
                    ->increment('saldo_berjalan', $nominal);
            }
            
        }

        // =========================
        // COMMIT SEMUA
        // =========================
        DB::commit();
        return response()->json([
            'ok' => true,
            'message' => 'Transaksi tersimpan',
            'no_transaksi' => $noTransaksi
        ]);

    } catch (\Throwable $e) {
        DB::rollBack();
        return response()->json([
            'ok' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}



  private function insertJurnalSimple(
    string $tanggal,
    float $nominal,
    ?string $keterangan,
    int $akunDebet,
    int $akunKredit,
    int $jenisLaporanDebet = 1,
    int $jenisLaporanKredit = 1,
    string $noReferensi = 'tes',
    string $modulSumber = 'tes',
    bool $kreditMenambahSaldo = false,
    bool $debetMengurangiSaldo = false
): void {
    // 1) Header jurnal (tetap)
    $userId = $this->userId;
    $idJurnal = DB::table('dat_header_jurnal')->insertGetId([
        'tgl_transaksi' => $tanggal,
        'no_referensi'  => $noReferensi,
        'keterangan'    => $keterangan,
        'modul_sumber'  => $modulSumber,
        'created_by'  => $userId,
        'created_at'    => now(),
        'updated_at'    => now(),
    ]);

    // 2) Buku Besar (per-periode) — tetap
    $periode = Carbon::parse($tanggal)->format('Y-m');

    foreach ([
        $akunDebet  => ['debit' => $nominal, 'kredit' => 0],
        $akunKredit => ['debit' => 0,        'kredit' => $nominal],
    ] as $akunId => $val) {
        $bukbes = DB::table('dat_buku_besar')
            ->where('created_by', $userId)
            ->where('id_akun', $akunId)
            ->where('periode', $periode)
            ->lockForUpdate()
            ->first();

        if ($bukbes) {
            DB::table('dat_buku_besar')
                ->where('created_by', $userId)
                ->where('id_bukbes', $bukbes->id_bukbes)
                ->update([
                    'ttl_debit'   => (float)$bukbes->ttl_debit + (float)$val['debit'],
                    'ttl_kredit'  => (float)$bukbes->ttl_kredit + (float)$val['kredit'],
                    'saldo_akhir' => (float)$bukbes->saldo_akhir + ((float)$val['debit'] - (float)$val['kredit']),
                    'updated_at'  => now(),
                ]);
        } else {
            DB::table('dat_buku_besar')->insert([
                'id_akun'     => $akunId,
                'periode'     => $periode,
                'ttl_debit'   => (float)$val['debit'],
                'ttl_kredit'  => (float)$val['kredit'],
                'saldo_akhir' => (float)$val['debit'] - (float)$val['kredit'],
                'created_by'     => $userId,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }

    $toInt = static function($v){
        return (int) preg_replace('/[^\d\-]/', '', (string)($v ?? '0'));
    };

    $userId = $this->userId;
        if ($debetMengurangiSaldo) {                                              // [changes]
            $affD = DB::table('mst_akun')
                ->where('id', $akunDebet)
                ->where('created_by', $userId)                                    // [changes]
                ->lockForUpdate()
                ->decrement('saldo_berjalan', $nominal);
        } else {
            $affD = DB::table('mst_akun')
                ->where('id', $akunDebet)
                ->where('created_by', $userId)                                    // [changes]
                ->lockForUpdate()
                ->increment('saldo_berjalan', $nominal);
        }

        if ($affD === 0) {
            throw new \RuntimeException("Akun debet (ID {$akunDebet}) tidak ditemukan di mst_akun.");
        }

        $saldoDebetAfter = $toInt(
            DB::table('mst_akun')
                ->where('id', $akunDebet)
                ->where('created_by', $userId)                                    // [changes]
                ->lockForUpdate()
                ->value('saldo_berjalan')
        );                                                                         // [changes]

        if ($kreditMenambahSaldo) {                                               // [changes]
            $affK = DB::table('mst_akun')
                ->where('id', $akunKredit)
                ->where('created_by', $userId)                                    // [changes]
                ->lockForUpdate()
                ->increment('saldo_berjalan', $nominal);
        } else {
            $affK = DB::table('mst_akun')
                ->where('id', $akunKredit)
                ->where('created_by', $userId)                                    // [changes]
                ->lockForUpdate()
                ->decrement('saldo_berjalan', $nominal);
        }

        if ($affK === 0) {
            throw new \RuntimeException("Akun kredit (ID {$akunKredit}) tidak ditemukan di mst_akun.");
        }

        $saldoKreditAfter = $toInt(
            DB::table('mst_akun')
                ->where('id', $akunKredit)
                ->where('created_by', $userId)                                    // [changes]
                ->lockForUpdate()
                ->value('saldo_berjalan')
        );
    DB::table('dat_detail_jurnal')->insert([
        [
            'id_jurnal'       => $idJurnal,
            'id_akun'         => $akunDebet,
            'jml_debit'       => $nominal,
            'jml_kredit'      => 0,
            'id_proyek'       => null,
            'kode_pajak'      => null,
            'jenis_laporan'   => $jenisLaporanDebet,
            'saldo_berjalan'  => (string) $saldoDebetAfter,                   // [changes]
             'tanggal'    => $tanggal,
             'created_by'    => $userId,
            'created_at'      => now(),
            'updated_at'      => now(),
        ],
        [
            'id_jurnal'       => $idJurnal,
            'id_akun'         => $akunKredit,
            'jml_debit'       => 0,
            'jml_kredit'      => $nominal,
            'id_proyek'       => null,
            'kode_pajak'      => null,
            'jenis_laporan'   => $jenisLaporanKredit,
            'saldo_berjalan'  => (string) $saldoKreditAfter,                  // [changes]
             'tanggal'    => $tanggal,
             'created_by'    => $userId,
            'created_at'      => now(),
            'updated_at'      => now(),
        ],
    ]);
}

 public function getJurnal(Request $request)
{
    $search    = trim((string) $request->get('search', '')); // filter bebas (opsional)
    $akunId    = $request->get('akun_id');                   // [changes] prioritas filter by id_akun
    $akunName  = trim((string) $request->get('akun_name', '')); // [changes] fallback exact name
    $dateFrom  = $request->get('date_from'); // 'YYYY-MM-DD'
    $dateTo    = $request->get('date_to');   // 'YYYY-MM-DD'
    $page      = max(1, (int) $request->get('page', 1));
    $perPage   = max(1, min(100, (int) $request->get('per_page', 20)));
    $userId    = $this->userId;
    $q = DB::table('dat_detail_jurnal as d')
        ->join('dat_header_jurnal as h', 'h.id_jurnal', '=', 'd.id_jurnal')
        ->join('mst_akun as a', 'a.id', '=', 'd.id_akun')
        ->select([
            'h.tgl_transaksi as tanggaltrx',
            'd.tanggal as tanggal',
            'h.keterangan',
            'a.nama_akun',
            'd.jml_debit as debet',
            'd.jml_kredit as kredit',
            DB::raw("COALESCE(h.modul_sumber, 'Manual') as tipe"),
            'd.saldo_berjalan as saldo',
        ])
         ->where('a.created_by', $userId)  
        ->where('h.created_by', $userId)  
        ->where('d.created_by', $userId);

    // [changes] filter AKUN spesifik (prioritas id, fallback exact name)
    if (!empty($akunId)) {
        $q->where('d.id_akun', (int) $akunId);
    } elseif ($akunName !== '') {
        $q->where('a.nama_akun', '=', $akunName);
    }

    // [changes] 'search' hanya untuk filter bebas tambahan (tidak menentukan akun)
    if ($search !== '') {
        $q->where(function($w) use ($search) {
            $w->where('h.keterangan', 'like', "%{$search}%")
              ->orWhere('a.kode_akun', 'like', "%{$search}%");
        });
    }

    if ($dateFrom) $q->whereDate('h.tgl_transaksi', '>=', $dateFrom);
    if ($dateTo)   $q->whereDate('h.tgl_transaksi', '<=', $dateTo);

    $total = (clone $q)->count();

    $rows = $q
   
   ->orderBy('d.tanggal', 'asc')
    ->orderBy('d.id_detail', 'asc')         
        ->offset(($page - 1) * $perPage)
        ->limit($perPage)
        ->get()
        ->map(function($r){
            $r->debet  = (float) $r->debet;
            $r->kredit = (float) $r->kredit;
            $r->saldo  = (float) $r->saldo;
            return $r;
        });

    return response()->json([
        'ok'       => true,
        'data'     => $rows,
        'page'     => $page,
        'per_page' => $perPage,
        'total'    => $total,
    ]);
}


    
    public function getBukuBesar(Request $request)
    {
        $search   = trim($request->get('search', ''));
        $periode  = $request->get('periode'); 
        $page     = max(1, (int) $request->get('page', 1));
        $perPage  = max(1, min(100, (int) $request->get('per_page', 20)));
        $userId   = $this->userId;
            $q = DB::table('dat_buku_besar as b')
            ->join('mst_akun as a', 'a.id', '=', 'b.id_akun')
            ->select([
                'a.kode_akun', 
                'a.nama_akun',
                'b.periode as tanggal', 
                'b.ttl_debit as debet',
                'b.ttl_kredit as kredit',
                'a.saldo_berjalan as saldo',
                 DB::raw("'Manual' as tipe"),
            ])
            ->where('a.created_by', $userId)
            ->where('b.created_by', $userId);;

        if ($search !== '') {
            $q->where(function($w) use ($search) {
                $w->where('a.nama_akun', 'like', "%{$search}%")
                  ->orWhere('a.kode_akun', 'like', "%{$search}%")
                  ->orWhere('b.periode', 'like', "%{$search}%");
            });
        }

        if ($periode) {
            $q->where('b.periode', $periode); 
        }

        $total = (clone $q)->count();
        $rows = $q->orderBy('a.kode_akun', 'asc')   // ← urutkan berdasarkan kode akun ASC
              ->orderBy('b.periode', 'desc')    // opsional: dalam tiap akun, periode terbaru dulu
              ->offset(($page - 1) * $perPage)
              ->limit($perPage)
              ->get()
              ->map(function($r){
                  $r->debet  = (float) $r->debet;
                  $r->kredit = (float) $r->kredit;
                  $r->saldo  = (float) $r->saldo;
                  return $r;
              });

    return response()->json([
        'ok'        => true,
        'data'      => $rows,
        'page'      => $page,
        'per_page'  => $perPage,
        'total'     => $total,
    ]);
}
    public function listPemasok()
    {
        $userId = $this->userId;
        $tp = (new PemasokModel)->getTable();     
        $tb = (new DatBarangModel)->getTable();   
        $userId = $this->userId; 

        $items = PemasokModel::query()
            ->from("$tp as p")
            ->leftJoin("$tb as b", function ($j) use ($userId) {  
                $j->on('b.kode_pemasok', '=', 'p.kode_pemasok')
                ->where('b.created_by', $userId);               
            })
            ->where('p.created_by', $userId)                       
            ->orderBy('p.kode_pemasok')
            ->get([
                'p.id_pemasok',
                'p.kode_pemasok',
                'p.nama_pemasok',
                'b.nama_barang',
            ]);

        return response()->json([
            'ok'   => true,
            'data' => $items,
        ]);
    }

         public function listPelanggan()
    {
        $userId = $this->userId;
         $items = pelangganModel::where('created_by', $userId) 
        ->orderBy('id_pelanggan')
        ->get(['id_pelanggan','nama_pelanggan']);

        return response()->json([
            'ok'   => true,
            'data' => $items,
        ]);
    }

    public function storePemasok(Request $request)
{
    $userId = $this->userId;

    $rules = [
        'nama_pemasok' => 'required|string|max:150',
        'alamat'       => 'nullable|string',
        'no_hp'        => 'nullable|string|max:30',
        'email'        => 'nullable|email|max:150',
        'npwp'         => 'nullable|string|max:50',
        'nama_barang'  => 'required|string|max:150',
        'satuan_ukur'  => 'required|string|max:50',
        'harga_satuan' => 'required|numeric|min:0',
        'harga_jual'   => 'required|numeric|min:0',
        'stok'         => 'required|integer|min:0',
    ];

    $v = Validator::make($request->all(), $rules);
    if ($v->fails()) {
        return response()->json([
            'ok'      => false,
            'message' => $v->errors()->first(),
        ], 422);
    }

    DB::beginTransaction(); // [CHANGES] supaya pemasok+barang+jurnal atomic
    try {

        // ==========================
        // 1. Generate kode pemasok
        // ==========================
        $lastPemasok = PemasokModel::where('created_by', $userId)
            ->orderBy('id_pemasok', 'desc')
            ->first();

        $nextNumber  = $lastPemasok ? ((int) substr($lastPemasok->kode_pemasok, 3)) + 1 : 1;
        $kodePemasok = 'SUP' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        // ==========================
        // 2. Simpan pemasok
        // ==========================
        $pemasok = PemasokModel::create([
            'kode_pemasok' => $kodePemasok,
            'nama_pemasok' => $request->nama_pemasok,
            'alamat'       => $request->alamat,
            'no_hp'        => $request->no_hp,
            'email'        => $request->email,
            'npwp'         => $request->npwp,
            'created_by'   => $userId,
            'saldo_utang'  => 0,
        ]);

        // ==========================
        // 3. Simpan barang
        // ==========================
        $stok = (int) $request->stok;

        $barang = DatBarangModel::create([
            'kode_pemasok' => $pemasok->kode_pemasok,
            'nama_barang'  => $request->nama_barang,
            'satuan_ukur'  => $request->satuan_ukur,
            'harga_satuan' => $request->harga_satuan,
            'harga_jual'   => $request->harga_jual,
            'stok_awal'    => $stok,
            'stok_akhir'   => $stok,
            'created_by'   => $userId,
        ]);

        // ==========================
        // 4. Hitung nilai persediaan
        // ==========================
        $nilaiPersediaan = (float) $stok * (float) $request->harga_satuan;

        // ==========================
        // 5. Ambil akun persediaan (1104) & modal (3101)
        // ==========================
        $akunPersediaan = MstAkunModel::where('kode_akun', '1104')
            ->where('created_by', $userId)
            ->lockForUpdate() // [CHANGES] supaya aman saat update saldo lewat jurnal
            ->first();

        $akunModal = MstAkunModel::where('kode_akun', '3101')
            ->where('created_by', $userId)
            ->lockForUpdate()
            ->first();

        // ==========================
        // 6. Buat jurnal kalau ada stok
        //    Debet: Persediaan (1104)
        //    Kredit: Modal (3101)
        // ==========================
        if ($stok > 0 && $nilaiPersediaan > 0 && $akunPersediaan && $akunModal) {
            $tglJurnal   = Carbon::today()->format('Y-m-d');
            $keterangan  = 'Stok awal barang ' . $barang->nama_barang . ' dari pemasok ' . $pemasok->nama_pemasok;
            $noReferensi = 'SA-' . $pemasok->kode_pemasok; // contoh: SA-SUP001

            // pakai helper insertJurnalSimple yang sudah kamu punya
            $this->insertJurnalSimple(
                $tglJurnal,
                (float) $nilaiPersediaan,
                $keterangan,
                (int) $akunPersediaan->id, // Debet Persediaan
                (int) $akunModal->id,      // Kredit Modal
                2,                         // jenis_laporan debet  (Neraca)
                2,                         // jenis_laporan kredit (Neraca)
                $noReferensi,
                'STOK_AWAL_PEMASOK',       // modul_sumber
                true                       // kredit menambah saldo (modal)
            );
        }

        DB::commit();

        return response()->json([
            'ok'   => true,
            'data' => [
                'pemasok'          => $pemasok,
                'barang'           => $barang,
                'update_akun'      => $akunPersediaan ? true : false,
                'update_modal'     => $akunModal ? true : false,
                'nilai_persediaan' => $nilaiPersediaan,
            ],
        ]);
    } catch (\Throwable $e) {
        DB::rollBack();

        return response()->json([
            'ok'      => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}

//     public function storePemasok(Request $request)
//     {

//         $userId = $this->userId;
        
//         $rules = [
//             'nama_pemasok' => 'required|string|max:150',
//             'alamat'       => 'nullable|string',
//             'no_hp'        => 'nullable|string|max:30',
//             'email'        => 'nullable|email|max:150',
//             'npwp'         => 'nullable|string|max:50',
//             'nama_barang'  => 'required|string|max:150',
//             'satuan_ukur'  => 'required|string|max:50',
//             'harga_satuan' => 'required|numeric|min:0', 
//             'harga_jual'   => 'required|numeric|min:0',
//             'stok'         => 'required|integer|min:0',
//         ];

//         $v = Validator::make($request->all(), $rules);
//         if ($v->fails()) {
//             return response()->json([
//                 'ok'      => false,
//                 'message' => $v->errors()->first(),
//             ], 422);
//         }

//         $lastPemasok = PemasokModel::where('created_by', $userId)
//         ->orderBy('id_pemasok', 'desc')
//         ->first();
//         $nextNumber = $lastPemasok ? ((int)substr($lastPemasok->kode_pemasok, 3)) + 1 : 1;
//         $kodePemasok = 'SUP' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

//         $pemasok = PemasokModel::create([
//             'kode_pemasok' => $kodePemasok,
//             'nama_pemasok' => $request->nama_pemasok,
//             'alamat'       => $request->alamat,
//             'no_hp'        => $request->no_hp,
//             'email'        => $request->email,
//             'npwp'         => $request->npwp,
//             'created_by'   => $userId, 
//             'saldo_utang'  => 0,
//         ]);

//        $barang = DatBarangModel::create([
//         'kode_pemasok' => $pemasok->kode_pemasok, 
//         'nama_barang'  => $request->nama_barang,
//         'satuan_ukur'  => $request->satuan_ukur,
//         'harga_satuan' => $request->harga_satuan,
//         'harga_jual'   => $request->harga_jual,
//         'stok_awal'    => (int)$request->stok ?? 0,
//         'stok_akhir'   => (int)$request->stok ?? 0,
//         'created_by'   => $userId, 
//     ]);

//       $akunPersediaan = MstAkunModel::where('kode_akun', '1104')
//         ->where('created_by', $userId)
//         ->first();

//     $nilaiPersediaan = (float)$request->stok * (float)$request->harga_satuan;

  
//     if ($akunPersediaan) {
//         $akunPersediaan->saldo_awal += $nilaiPersediaan;
//         $akunPersediaan->saldo_berjalan += $nilaiPersediaan;
//         $akunPersediaan->save();
//     }

//    $akunModal = MstAkunModel::where('kode_akun', '3101')
//     ->where('created_by', $userId)
//     ->first();
//     if ($akunModal) {
//         $akunModal->saldo_awal += $nilaiPersediaan;
//         $akunModal->saldo_berjalan += $nilaiPersediaan;
//         $akunModal->save();
//     }

//     return response()->json([
//         'ok'   => true,
//         'data' => [
//             'pemasok'           => $pemasok,
//             'barang'            => $barang,
//             'update_akun'       => $akunPersediaan ? true : false,
//             'update_modal'      => $akunModal ? true : false, // ✅ [CHANGES]
//             'nilai_persediaan'  => $nilaiPersediaan,
//         ],
//     ]);
// }
    public function resetData(Request $request)
    {        $userId = $this->userId;


        $tablesToWipe = [
            'dat_barang',
            'dat_buku_besar',
            'dat_detail_jurnal',
            'dat_detail_transaksi',
            'dat_header_jurnal',
            'dat_pelanggan',
            'dat_pemasok',
            'dat_transaksi',
            'dat_piutang',
            'dat_utang',
        ];

        try {
            DB::beginTransaction();

            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            foreach ($tablesToWipe as $table) {
                 DB::table($table)
                ->where('created_by', $userId)
                ->delete();
                DB::statement("ALTER TABLE `$table` AUTO_INCREMENT = 1");
            }

            DB::table('mst_akun')
            ->where('created_by', $userId)
            ->update([
                'saldo_awal'     => 0,
                'saldo_berjalan' => 0,
            ]);

            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            DB::commit();

            return back()->with('status', 'Reset data berhasil dijalankan.');
        } catch (\Throwable $e) {
            try { DB::statement('SET FOREIGN_KEY_CHECKS=1'); } catch (\Throwable $ignored) {}
            DB::rollBack();

            report($e);
            return back()->withErrors('Gagal mereset data: '.$e->getMessage());
        }
    }

    public function resetTransaksi(Request $request)
    {
        $userId = $this->userId;
        $tablesToWipe = [
            
            'dat_buku_besar',
            'dat_detail_jurnal',
            'dat_detail_transaksi',
            'dat_header_jurnal',
          
            'dat_transaksi',
            'dat_piutang',
            'dat_utang',
        ];

        try {
            DB::beginTransaction();

            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            foreach ($tablesToWipe as $table) {
                 DB::table($table)
                ->where('created_by', $userId)
                ->delete();
                
            }


            DB::table('mst_akun')
            ->where('created_by', $userId)
            ->update([
                'saldo_awal'     => 0,
                'saldo_berjalan' => 0,
            ]);

            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            DB::commit();

            return back()->with('status', 'Reset data berhasil dijalankan.');
        } catch (\Throwable $e) {
            try { DB::statement('SET FOREIGN_KEY_CHECKS=1'); } catch (\Throwable $ignored) {}
            DB::rollBack();

            report($e);
            return back()->withErrors('Gagal mereset data: '.$e->getMessage());
        }
    }


}
