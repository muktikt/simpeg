<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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

        // Ambil data dokumen resmi riil dari database tabel dokumen_pegawai
        $dbDocs = collect();
        try {
            $dbDocs = DB::table('dokumen_pegawai')->get();
        } catch (\Throwable $e) {}

        // Map dokumen dari database ke masing-masing pegawai
        $allPegawai = collect($allPegawai)->map(function ($p) use ($dbDocs) {
            $dbId = $p['db_id'] ?? null;
            $intId = (string) ($p['id'] ?? '');
            $nik = (string) ($p['nik'] ?? '');

            // Cari dokumen SK untuk pegawai ini
            $skDoc = $dbDocs->first(function ($d) use ($dbId, $intId, $nik) {
                $matchPegawai = ($dbId && (string) $d->pegawai_id === (string) $dbId)
                    || ((string) $d->pegawai_id === $intId)
                    || ((string) $d->pegawai_id === $nik);
                $matchKategori = in_array(strtolower($d->kategori ?? ''), ['sk', 'surat_kerja'], true);
                return $matchPegawai && $matchKategori;
            });

            // Cari dokumen Diklat untuk pegawai ini
            $diklatDoc = $dbDocs->first(function ($d) use ($dbId, $intId, $nik) {
                $matchPegawai = ($dbId && (string) $d->pegawai_id === (string) $dbId)
                    || ((string) $d->pegawai_id === $intId)
                    || ((string) $d->pegawai_id === $nik);
                $matchKategori = in_array(strtolower($d->kategori ?? ''), ['diklat', 'surat_diklat'], true);
                return $matchPegawai && $matchKategori;
            });

            $p['surat_kerja'] = $skDoc ? [
                'id' => $skDoc->id,
                'nomor' => $skDoc->nomor ?? 'SK/SDM/2024/' . str_pad($p['id'], 3, '0', STR_PAD_LEFT),
                'judul' => $skDoc->judul ?? 'Surat Keputusan Pengangkatan ' . ($p['nama'] ?? 'Pegawai'),
                'tgl_terbit' => !empty($skDoc->created_at) ? substr((string)$skDoc->created_at, 0, 10) : date('Y-m-d'),
                'file_name' => $skDoc->file_nama ?? 'SK_' . str_replace(' ', '_', $p['nama'] ?? 'Pegawai') . '.pdf',
                'file_url' => !empty($skDoc->file_url) ? $skDoc->file_url : '#',
            ] : null;

            $p['surat_diklat'] = $diklatDoc ? [
                'id' => $diklatDoc->id,
                'nomor' => $diklatDoc->nomor ?? 'STP/SDM/2024/' . str_pad($p['id'] + 80, 3, '0', STR_PAD_LEFT),
                'judul' => $diklatDoc->judul ?? 'Sertifikat Diklat Manajemen Kepegawaian & Pelayanan',
                'tgl_terbit' => !empty($diklatDoc->created_at) ? substr((string)$diklatDoc->created_at, 0, 10) : date('Y-m-d'),
                'file_name' => $diklatDoc->file_nama ?? 'Sertifikat_Diklat_' . str_replace(' ', '_', $p['nama'] ?? 'Pegawai') . '.pdf',
                'file_url' => !empty($diklatDoc->file_url) ? $diklatDoc->file_url : '#',
            ] : null;

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
            'pegawai_id' => 'required',
            'jenis_dokumen' => 'required',
            'nomor' => 'required|string|max:150',
            'judul' => 'required|string|max:255',
            'tgl_terbit' => 'nullable|date',
            'file' => 'nullable|file|max:25600',
        ]);

        $pegawaiController = app(PegawaiController::class);
        $allPegawai = $pegawaiController->all();

        // Cari target pegawai
        $targetPegawai = collect($allPegawai)->first(function ($p) use ($validated) {
            return (string)($p['id'] ?? '') === (string)$validated['pegawai_id']
                || (string)($p['db_id'] ?? '') === (string)$validated['pegawai_id']
                || (string)($p['nik'] ?? '') === (string)$validated['pegawai_id'];
        });

        if (!$targetPegawai) {
            return back()->with('error', 'Pegawai yang dipilih tidak ditemukan.');
        }

        $kategoriDb = in_array(strtolower($validated['jenis_dokumen']), ['surat_kerja', 'sk']) ? 'SK' : 'Diklat';
        $kategoriLabel = $kategoriDb === 'SK' ? 'Surat Kerja (SK)' : 'Surat Diklat / Pelatihan';
        $targetPegawaiName = $targetPegawai['nama'] ?? 'Pegawai';

        $fileName = ($kategoriDb === 'SK' ? 'SK_' : 'Diklat_') . preg_replace('/[^a-zA-Z0-9]/', '_', $targetPegawaiName) . '.pdf';
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

        $pegawaiDbId = $targetPegawai['db_id'] ?? null;
        if (! $pegawaiDbId && ! empty($targetPegawai['nik'])) {
            $pegawaiDbId = DB::table('pegawai')->where('nik', (string) $targetPegawai['nik'])->value('id');
        }
        if (! $pegawaiDbId && ! empty($validated['pegawai_id'])) {
            $pegawaiDbId = DB::table('pegawai')->where('id', $validated['pegawai_id'])->orWhere('nik', $validated['pegawai_id'])->value('id');
        }

        // Validate UUID syntax for Postgres
        if ($pegawaiDbId && ! \Illuminate\Support\Str::isUuid((string) $pegawaiDbId)) {
            $pegawaiDbId = null;
        }

        $diunggahOleh = session('simpeg_user.nama_peg', 'Admin SDM');
        $tglTerbit = ! empty($validated['tgl_terbit']) ? $validated['tgl_terbit'] . ' 08:00:00' : now();

        // Simpan / update ke database tabel dokumen_pegawai
        try {
            // Cek apakah dokumen untuk pegawai dan kategori ini sudah ada
            $existing = null;
            if ($pegawaiDbId) {
                $existing = DB::table('dokumen_pegawai')
                    ->where('pegawai_id', $pegawaiDbId)
                    ->where('kategori', $kategoriDb)
                    ->first();
            }

            if ($existing) {
                $updateData = [
                    'nomor' => $validated['nomor'],
                    'judul' => $validated['judul'],
                    'diunggah_oleh' => $diunggahOleh,
                    'created_at' => $tglTerbit,
                ];
                if ($fileUrl !== '#') {
                    $updateData['file_nama'] = $fileName;
                    $updateData['file_url'] = $fileUrl;
                }
                DB::table('dokumen_pegawai')->where('id', $existing->id)->update($updateData);
            } else {
                DB::table('dokumen_pegawai')->insert([
                    'pegawai_id' => $pegawaiDbId,
                    'kategori' => $kategoriDb,
                    'nomor' => $validated['nomor'],
                    'judul' => $validated['judul'],
                    'file_nama' => $fileName,
                    'file_url' => $fileUrl,
                    'diunggah_oleh' => $diunggahOleh,
                    'created_at' => $tglTerbit,
                ]);
            }
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menyimpan dokumen ke database: ' . $e->getMessage());
        }

        Cache::forget('simpeg_all_pegawai_list');

        return redirect()->route('dokumen-surat.index')->with(
            'success',
            "{$kategoriLabel} untuk {$targetPegawaiName} berhasil diunggah & disimpan."
        );
    }

    /**
     * Hapus berkas dokumen pegawai.
     */
    public function destroy(int $pegawaiId, string $jenis)
    {
        $kategoriDb = in_array(strtolower($jenis), ['surat_kerja', 'sk']) ? 'SK' : 'Diklat';
        $kategoriLabel = $kategoriDb === 'SK' ? 'Surat Kerja (SK)' : 'Surat Diklat / Pelatihan';

        $pegawaiController = app(PegawaiController::class);
        $allPegawai = $pegawaiController->all();
        $targetPegawai = collect($allPegawai)->firstWhere('id', $pegawaiId);

        if ($targetPegawai && !empty($targetPegawai['db_id'])) {
            try {
                DB::table('dokumen_pegawai')
                    ->where('pegawai_id', $targetPegawai['db_id'])
                    ->where('kategori', $kategoriDb)
                    ->delete();
            } catch (\Throwable $e) {}
        }

        Cache::forget('simpeg_all_pegawai_list');

        $targetPegawaiName = $targetPegawai['nama'] ?? 'Pegawai';
        return redirect()->route('dokumen-surat.index')->with(
            'success',
            "{$kategoriLabel} untuk {$targetPegawaiName} telah berhasil dihapus."
        );
    }
}
