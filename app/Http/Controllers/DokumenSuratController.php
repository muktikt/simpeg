<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class DokumenSuratController extends Controller
{
    /**
     * Tampilkan halaman daftar & kelola dokumen surat pegawai untuk Admin SDM.
     */
    public function index(Request $request)
    {
        $keyword = strtolower(trim($request->get('q', '')));
        $filterJenis = $request->get('filter', 'all');

        $pegawaiController = app(PegawaiController::class);
        $allPegawai = $pegawaiController->all();

        // Pastikan setiap pegawai punya struktur dokumen default jika belum ada
        $allPegawai = collect($allPegawai)->map(function ($p) {
            if (! isset($p['surat_kerja'])) {
                $p['surat_kerja'] = [
                    'nomor' => 'SK/SDM/2024/' . str_pad($p['id'], 3, '0', STR_PAD_LEFT),
                    'judul' => 'Surat Keputusan Pengangkatan ' . ($p['nama'] ?? 'Pegawai'),
                    'tgl_terbit' => '2024-01-15',
                    'file_name' => 'SK_' . str_replace(' ', '_', $p['nama'] ?? 'Pegawai') . '.pdf',
                    'file_url' => '#',
                ];
            }
            if (! isset($p['surat_diklat'])) {
                $p['surat_diklat'] = [
                    'nomor' => 'STP/SDM/2024/' . str_pad($p['id'] + 80, 3, '0', STR_PAD_LEFT),
                    'judul' => 'Sertifikat Diklat Manajemen Kepegawaian & Pelayanan',
                    'tgl_terbit' => '2024-05-20',
                    'file_name' => 'Sertifikat_Diklat_' . str_replace(' ', '_', $p['nama'] ?? 'Pegawai') . '.pdf',
                    'file_url' => '#',
                ];
            }
            return $p;
        });

        // Hitung statistik dokumen
        $totalPegawai = $allPegawai->count();
        $totalSk = $allPegawai->filter(fn ($p) => ! empty($p['surat_kerja']['nomor']))->count();
        $totalDiklat = $allPegawai->filter(fn ($p) => ! empty($p['surat_diklat']['nomor']))->count();

        // Filter berdasarkan pencarian kata kunci
        $filtered = $allPegawai;
        if ($keyword !== '') {
            $filtered = $filtered->filter(function ($p) use ($keyword) {
                return str_contains(strtolower($p['nama'] ?? ''), $keyword)
                    || str_contains(strtolower($p['nik'] ?? ''), $keyword)
                    || str_contains(strtolower($p['unit_kerja'] ?? ''), $keyword)
                    || str_contains(strtolower($p['jabatan'] ?? ''), $keyword)
                    || str_contains(strtolower($p['surat_kerja']['nomor'] ?? ''), $keyword)
                    || str_contains(strtolower($p['surat_diklat']['nomor'] ?? ''), $keyword);
            });
        }

        // Filter berdasarkan kelengkapan dokumen
        if ($filterJenis === 'sk_only') {
            $filtered = $filtered->filter(fn ($p) => ! empty($p['surat_kerja']['nomor']));
        } elseif ($filterJenis === 'diklat_only') {
            $filtered = $filtered->filter(fn ($p) => ! empty($p['surat_diklat']['nomor']));
        }

        $pegawaiList = $filtered->sortBy('nama')->values();
        $allPegawaiOptions = $allPegawai->sortBy('nama')->values();

        return view('dokumen-surat.index', compact(
            'pegawaiList',
            'allPegawaiOptions',
            'totalPegawai',
            'totalSk',
            'totalDiklat',
            'keyword',
            'filterJenis'
        ));
    }

    /**
     * Unggah / Perbarui dokumen pegawai (SK / Diklat) oleh Admin SDM.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'pegawai_id' => 'required|integer',
            'jenis_dokumen' => 'required|in:surat_kerja,surat_diklat',
            'nomor' => 'required|string|max:100',
            'judul' => 'required|string|max:200',
            'tgl_terbit' => 'required|date',
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $pegawaiController = app(PegawaiController::class);
        $allPegawai = $pegawaiController->all();

        $fileName = ($validated['jenis_dokumen'] === 'surat_kerja' ? 'SK_' : 'Diklat_') . time() . '.pdf';
        $fileUrl = '#';

        if ($request->hasFile('file')) {
            $uploadedFile = $request->file('file');
            $originalName = $uploadedFile->getClientOriginalName();
            $safeName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
            
            $destinationPath = public_path('uploads/dokumen');
            if (! File::exists($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true, true);
            }
            $uploadedFile->move($destinationPath, $safeName);
            $fileName = $originalName;
            $fileUrl = asset('uploads/dokumen/' . $safeName);
        }

        $targetPegawaiName = '';

        $allPegawai = collect($allPegawai)->map(function ($p) use ($validated, $fileName, $fileUrl, &$targetPegawaiName) {
            if ($p['id'] == $validated['pegawai_id']) {
                $targetPegawaiName = $p['nama'] ?? 'Pegawai';
                $p[$validated['jenis_dokumen']] = [
                    'nomor' => $validated['nomor'],
                    'judul' => $validated['judul'],
                    'tgl_terbit' => $validated['tgl_terbit'],
                    'file_name' => $fileName,
                    'file_url' => $fileUrl,
                ];
            }
            return $p;
        })->all();

        session()->put('dummy_pegawai', $allPegawai);

        // Update juga ke database tbl_dokumen / Supabase jika tabelnya ada
        try {
            $target = collect($allPegawai)->firstWhere('id', $validated['pegawai_id']);
            if ($target && ! empty($target['nik'])) {
                DB::table('dokumen_kepegawaian')->updateOrInsert(
                    [
                        'nik' => (string) $target['nik'],
                        'jenis_dokumen' => $validated['jenis_dokumen'],
                    ],
                    [
                        'nomor_surat' => $validated['nomor'],
                        'judul_dokumen' => $validated['judul'],
                        'tgl_terbit' => $validated['tgl_terbit'],
                        'file_nama' => $fileName,
                        'file_url' => $fileUrl,
                        'updated_at' => now(),
                    ]
                );
            }
        } catch (\Throwable $e) {
            // Abaikan jika tabel belum ada di DB lokal
        }

        $jenisLabel = $validated['jenis_dokumen'] === 'surat_kerja' ? 'Surat Kerja (SK)' : 'Surat Diklat / Pelatihan';
        return redirect()->route('dokumen-surat.index')->with(
            'success',
            "{$jenisLabel} untuk {$targetPegawaiName} berhasil diunggah & diperbarui."
        );
    }

    /**
     * Hapus berkas dokumen pegawai.
     */
    public function destroy(int $pegawaiId, string $jenis)
    {
        if (! in_array($jenis, ['surat_kerja', 'surat_diklat'], true)) {
            return back()->with('error', 'Jenis dokumen tidak valid.');
        }

        $pegawaiController = app(PegawaiController::class);
        $allPegawai = $pegawaiController->all();
        $targetPegawaiName = '';

        $allPegawai = collect($allPegawai)->map(function ($p) use ($pegawaiId, $jenis, &$targetPegawaiName) {
            if ($p['id'] === $pegawaiId) {
                $targetPegawaiName = $p['nama'] ?? 'Pegawai';
                $p[$jenis] = null;
            }
            return $p;
        })->all();

        session()->put('dummy_pegawai', $allPegawai);

        $jenisLabel = $jenis === 'surat_kerja' ? 'Surat Kerja (SK)' : 'Surat Diklat / Pelatihan';
        return redirect()->route('dokumen-surat.index')->with(
            'success',
            "{$jenisLabel} untuk {$targetPegawaiName} telah dihapus."
        );
    }
}
