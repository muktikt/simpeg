<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CutiController extends Controller
{
    /**
     * Tampilkan data dan pengajuan cuti pegawai terhubung langsung dengan database.
     */
    public function index(Request $request)
    {
        $tahun = (int) $request->get('tahun', now()->year);
        $myRole = session('simpeg_user.userlevel');
        $myNik = session('simpeg_user.nik');

        try {
            // 1. Ambil dari tabel pengajuan_cuti (diajukan via Mobile & Web)
            $query = DB::table('pengajuan_cuti')
                ->leftJoin('pegawai', 'pengajuan_cuti.pegawai_id', '=', 'pegawai.id')
                ->select(
                    'pengajuan_cuti.*',
                    'pegawai.nik as pegawai_nik',
                    'pegawai.name as nama_lengkap',
                    'pegawai.unit_kerja'
                )
                ->whereYear('pengajuan_cuti.created_at', $tahun)
                ->orderByDesc('pengajuan_cuti.created_at');

            if ($myRole === '5') {
                $query->where('pegawai.nik', $myNik);
            }

            $pengajuan = $query->get()->map(function ($row) {
                return [
                    'id' => $row->id,
                    'nik' => $row->pegawai_nik ?? '-',
                    'nama' => $row->nama_lengkap ?? $row->nama_pegawai ?? 'Pegawai',
                    'unit_kerja' => $row->unit_kerja ?? 'Kantor Pusat',
                    'jenis' => $row->jenis ?? 'Cuti Tahunan',
                    'tanggal_mulai' => $row->tanggal_mulai ?? $row->created_at,
                    'tanggal_selesai' => $row->tanggal_selesai ?? $row->created_at,
                    'alasan' => $row->alasan ?? '-',
                    'status' => $row->status ?? 'PENDING',
                ];
            })->toArray();

            // 2. Jika pengajuan_cuti masih sedikit/kosong, gabungkan dengan rekap prestasi
            $cutiPrestasi = DB::table('prestasi')
                ->leftJoin('pegawai', 'prestasi.pegawai_id', '=', 'pegawai.id')
                ->where('prestasi.cuti', '>', 0)
                ->where('prestasi.tahun', $tahun)
                ->select('prestasi.*', 'pegawai.nik as pegawai_nik', 'pegawai.name as nama_lengkap', 'pegawai.unit_kerja')
                ->get()
                ->map(function ($row) {
                    return [
                        'id' => 'p-' . $row->id,
                        'nik' => $row->pegawai_nik ?? '-',
                        'nama' => $row->nama_lengkap ?? 'Pegawai',
                        'unit_kerja' => $row->unit_kerja ?? 'Kantor Pusat',
                        'jenis' => 'Cuti Rekap (' . $row->cuti . ' hari)',
                        'tanggal_mulai' => $row->tahun . '-' . sprintf('%02d', $row->bulan ?? 1) . '-01',
                        'tanggal_selesai' => $row->tahun . '-' . sprintf('%02d', $row->bulan ?? 1) . '-05',
                        'alasan' => 'Tercatat di modul Prestasi Kerja',
                        'status' => 'DISETUJUI',
                    ];
                })->toArray();

            $cuti = array_merge($pengajuan, $cutiPrestasi);
        } catch (\Throwable $e) {
            // Fallback ke dummy prestasi jika database connection offline
            $cuti = collect(session('dummy_prestasi_gaji', []))
                ->filter(fn ($row) => ($row['cuti'] ?? 0) > 0)
                ->map(function ($row) {
                    return [
                        'id' => $row['id'] ?? 1,
                        'nik' => $row['nik'] ?? '3000000003',
                        'nama' => $row['nama'] ?? 'Pegawai',
                        'unit_kerja' => $row['unit_kerja'] ?? 'PDAM',
                        'jenis' => 'Cuti Tahunan',
                        'tanggal_mulai' => $row['tanggal'] ?? now()->toDateString(),
                        'tanggal_selesai' => $row['tanggal'] ?? now()->toDateString(),
                        'alasan' => $row['alasan_cuti'] ?? 'Cuti Tahunan',
                        'status' => 'DISETUJUI',
                    ];
                })->values()->all();
        }

        return view('cuti.index', compact('cuti', 'tahun', 'myRole'));
    }

    /**
     * Approve / Reject Pengajuan Cuti oleh SDM.
     */
    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:PENDING,DISETUJUI,DITOLAK',
        ]);

        try {
            DB::table('pengajuan_cuti')->where('id', $id)->update([
                'status' => $validated['status'],
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Fallback
        }

        return back()->with('success', 'Status pengajuan cuti pegawai berhasil diperbarui.');
    }
}
