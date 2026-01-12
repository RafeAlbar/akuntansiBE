<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BepApi
{
    public function index()
    {
        return view('bep.index'); 
    }

      public function getAkunBeban(Request $request)
    {
       $userId = (int) $request->input('user_id', 0);

        // optional filter pencarian
        $search = trim($request->input('q', ''));

        $query = DB::table('mst_akun')
            ->where('created_by', $userId)
            ->where(function ($q) {
                // cover: "Beban", "Beban Operasional", "Beban Lain-lain", dst
                $q->where('kategori_akun', 'Beban')
                  ->orWhere('kategori_akun', 'like', 'Beban%');
            });

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nama_akun', 'like', "%{$search}%")
                  ->orWhere('kode_akun', 'like', "%{$search}%");
            });
        }

        $rows = $query
            ->select(
                'id',
                'kode_akun',
                'nama_akun',
                'kategori_akun',
                'saldo_berjalan'   // ini yang dipakai sebagai total per akun
            )
            ->orderBy('kode_akun')
            ->get();

        // total semua saldo_berjalan akun beban
        $total = $rows->sum('saldo_berjalan');

        return response()->json([
            'ok'        => true,
            'data'      => $rows,
            'total'     => $total,
            'total_rp'  => 'Rp ' . number_format($total, 0, ',', '.'),
        ]);
    }

    public function getPenjualan(Request $request)
    {
       $userId = (int) $request->input('user_id', 0);

        $rows = DB::table('mst_akun')
            ->where('created_by', $userId)
            ->whereIn('kode_akun', [4101, 4104])
            ->select(
                'id',
                'kode_akun',
                'nama_akun',
                'saldo_berjalan'
            )
            ->orderBy('id')
            ->get();

        $total = $rows->sum('saldo_berjalan');

        return response()->json([
            'ok'       => true,
            'data'     => $rows,
            'total'    => $total,
            'total_rp' => 'Rp ' . number_format($total, 0, ',', '.'),
        ]);
    }

}
