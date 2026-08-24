<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PengaduanController extends Controller
{
    /**
     * Tampilkan daftar pengaduan pegawai terhubung database Supabase.
     */
    public function index()
    {
        $myRole = session('simpeg_user.userlevel');
        $myNik = session('simpeg_user.nik');

        try {
            $query = DB::table('pengaduan_pegawai')->orderByDesc('created_at');

            if ($myRole === '5') {
                $query->where('nik', $myNik);
            }

            $pengaduan = $query->get()->map(function ($row) {
                return [
                    'id' => $row->id,
                    'nomor_pengaduan' => $row->nomor_pengaduan ?? 'PGD-' . $row->id,
                    'nik' => $row->nik ?? '-',
                    'nama' => $row->nama_pegawai ?? 'Pegawai',
                    'subjek' => $row->judul ?? $row->kategori ?? 'Pengaduan',
                    'pesan' => $row->deskripsi ?? '-',
                    'status' => $row->status ?? 'DIAJUKAN',
                    'tanggal' => $row->tanggal_pengaduan ? date('d M Y H:i', strtotime($row->tanggal_pengaduan)) : date('d M Y H:i', strtotime($row->created_at ?? now())),
                ];
            })->toArray();
        } catch (\Throwable $e) {
            $pengaduan = session('dummy_pengaduan', []);
            if ($myRole === '5') {
                $pengaduan = collect($pengaduan)->where('nik', $myNik)->values()->all();
            }
        }

        return view('pengaduan.index', compact('pengaduan', 'myRole'));
    }

    /**
     * Simpan pengaduan baru dari Web.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'subjek' => 'required|string|max:150',
            'pesan' => 'required|string',
        ]);

        $userLogin = session('simpeg_user');
        $nik = $userLogin['nik'] ?? '';
        $nama = $userLogin['nama_peg'] ?? 'Pegawai';
        $now = now();
        $nomorPengaduan = 'PGD-' . date('Ymd') . '-' . rand(1000, 9999);

        try {
            $pegawai = DB::table('pegawai')->where('nik', $nik)->first();
            $pegawaiId = $pegawai ? $pegawai->id : null;

            DB::table('pengaduan_pegawai')->insert([
                'nomor_pengaduan' => $nomorPengaduan,
                'pelapor_id' => $pegawaiId,
                'kategori' => 'Umum',
                'judul' => $validated['subjek'],
                'deskripsi' => $validated['pesan'],
                'tanggal_pengaduan' => $now,
                'nama_pegawai' => $nama,
                'nik' => $nik,
                'cabang' => $pegawai->unit_kerja ?? 'PDAM Tirta Darma Ayu',
                'golongan' => $pegawai->golongan ?? '',
                'anonim' => false,
                'status' => 'DIAJUKAN',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } catch (\Throwable $e) {
            $items = session('dummy_pengaduan', []);
            $newId = $items ? max(array_column($items, 'id')) + 1 : 1;
            $items[] = [
                'id' => $newId,
                'nomor_pengaduan' => $nomorPengaduan,
                'nik' => $nik,
                'nama' => $nama,
                'subjek' => $validated['subjek'],
                'pesan' => $validated['pesan'],
                'status' => 'DIAJUKAN',
                'tanggal' => date('d M Y H:i'),
            ];
            session()->put('dummy_pengaduan', $items);
        }

        return back()->with('success', 'Pengaduan Anda berhasil dikirim ke Manajemen SDM.');
    }

    /**
     * Update status pengaduan oleh Admin SDM.
     */
    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:DIAJUKAN,DIPROSES,SELESAI,DITOLAK',
        ]);

        try {
            DB::table('pengaduan_pegawai')->where('id', $id)->update([
                'status' => $validated['status'],
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $items = session('dummy_pengaduan', []);
            foreach ($items as &$item) {
                if ($item['id'] == $id) {
                    $item['status'] = $validated['status'];
                }
            }
            session()->put('dummy_pengaduan', $items);
        }

        return back()->with('success', 'Status pengaduan berhasil diperbarui.');
    }
}
