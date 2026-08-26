<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PengaduanController extends Controller
{
    /**
     * Helper untuk mendapatkan NIK & Role Pengaduan user yang sedang login.
     */
    protected function getUserContext(): array
    {
        $sessionUser = session('simpeg_user', []);
        $nik = $sessionUser['nik'] ?? '';
        $userLevel = $sessionUser['userlevel'] ?? '5';
        $rolePengaduan = $sessionUser['role_pengaduan'] ?? 'pegawai';
        $divisiKadiv = $sessionUser['divisi_kadiv'] ?? null;
        $nama = $sessionUser['nama_peg'] ?? 'Pegawai';

        // Jika belum ada di session, deteksi langsung dari tabel pegawai
        if (empty($sessionUser['role_pengaduan']) && !empty($nik)) {
            try {
                $peg = DB::table('pegawai')->where('nik', $nik)->first();
                if ($peg) {
                    $dbRole = strtolower($peg->role ?? '');
                    $jabatanLower = strtolower($peg->jabatan ?? '');
                    $divisiKadiv = $peg->divisi_kadiv ?? null;

                    if ($dbRole === 'direktur') {
                        $rolePengaduan = 'dirut';
                    } elseif ($dbRole === 'kspi') {
                        $rolePengaduan = 'kspi';
                    } elseif ($dbRole === 'tpdpk') {
                        $rolePengaduan = 'tpdpk';
                    } elseif ($dbRole === 'kadiv' || $dbRole === 'kadivkategori') {
                        $rolePengaduan = 'kadiv';
                    } elseif ($userLevel === '1' || $dbRole === 'admin' || $dbRole === 'sdm') {
                        $rolePengaduan = 'sdm';
                    } elseif ($dbRole === 'keuangan' || $dbRole === 'keu') {
                        $rolePengaduan = 'keuangan';
                    } else {
                        $rolePengaduan = 'pegawai';
                    }
                }
            } catch (\Throwable $e) {}
        }

        return [
            'nik' => $nik,
            'nama' => $nama,
            'userlevel' => $userLevel,
            'role' => $rolePengaduan,
            'divisi_kadiv' => $divisiKadiv,
        ];
    }

    /**
     * Dashboard / Daftar Pengaduan sesuai role yang login.
     */
    public function index(Request $request)
    {
        $ctx = $this->getUserContext();
        $myRole = $ctx['role'];
        $myNik = $ctx['nik'];
        $divisiKadiv = $ctx['divisi_kadiv'];
        $tab = $request->query('tab', 'masuk');

        $pengaduanMasuk = [];
        $pengaduanSaya = [];
        $pengaduanRiwayat = [];

        try {
            // 1. Ambil pengaduan milik sendiri (Semua role punya tab ini)
            $pengaduanSaya = DB::table('pengaduan_pegawai')
                ->where('nik', $myNik)
                ->orderByDesc('created_at')
                ->get()
                ->toArray();

            // 2. Ambil data kotak masuk sesuai wewenang role
            if ($myRole === 'kadiv') {
                $divisiLabel = ($divisiKadiv === 'teknik') ? 'Pelanggaran Teknik' : 'Pelanggaran Administrasi';

                // Aduan yang butuh tindakan Kadiv:
                // a. Menunggu verifikasi awal sesuai kategori divisinya
                // b. Sedang diinvestigasi oleh Kadiv divisi ini
                // c. Sedang tindak lanjut oleh Kadiv divisi ini
                $query = DB::table('pengaduan_pegawai')
                    ->where(function ($q) use ($divisiLabel, $divisiKadiv) {
                        $q->where(function ($sub) use ($divisiLabel) {
                            $sub->whereIn('status', ['menungguKadiv', 'menungguVerifikasiKadiv'])
                                ->where('kategori', $divisiLabel);
                        })->orWhere(function ($sub) use ($divisiKadiv) {
                            $sub->where('status', 'investigasiBerjalan')
                                ->where('eksekutor', 'kadiv')
                                ->where('eksekutor_divisi_kadiv', $divisiKadiv);
                        })->orWhere(function ($sub) use ($divisiKadiv) {
                            $sub->where('status', 'tindakLanjutBerjalan')
                                ->where('eksekutor_tindak_lanjut', 'kadiv')
                                ->where('eksekutor_tindak_lanjut_divisi_kadiv', $divisiKadiv);
                        });
                    })
                    ->orderByDesc('created_at');

                $pengaduanMasuk = $query->get()->toArray();

                // Riwayat pengaduan yang sudah pernah diverifikasi kadiv
                $pengaduanRiwayat = DB::table('pengaduan_pegawai')
                    ->where('kategori', $divisiLabel)
                    ->whereNotIn('status', ['menungguKadiv', 'menungguVerifikasiKadiv'])
                    ->orderByDesc('updated_at')
                    ->limit(50)
                    ->get()
                    ->toArray();

            } elseif ($myRole === 'kspi') {
                // KSPI: Review Awal (reviewKspi), Pilih Eksekutor (menungguPilihEksekutor), Review Hasil (menungguReviewKspi)
                $pengaduanMasuk = DB::table('pengaduan_pegawai')
                    ->whereIn('status', ['reviewKspi', 'menungguPilihEksekutor', 'menungguReviewKspi', 'ditolakDirektur'])
                    ->orderByDesc('created_at')
                    ->get()
                    ->toArray();

                $pengaduanRiwayat = DB::table('pengaduan_pegawai')
                    ->whereNotIn('status', ['reviewKspi', 'menungguPilihEksekutor', 'menungguReviewKspi', 'ditolakDirektur'])
                    ->orderByDesc('updated_at')
                    ->limit(50)
                    ->get()
                    ->toArray();

            } elseif ($myRole === 'dirut') {
                // DIRUT: Approval Tahap 1 (menungguDirutTahap1), Approval Tahap 2 (menungguDirutTahap2), Pilih Eksekutor Tindak Lanjut
                $pengaduanMasuk = DB::table('pengaduan_pegawai')
                    ->whereIn('status', ['menungguDirutTahap1', 'menungguDirutTahap2', 'menungguPilihEksekutorTindakLanjut'])
                    ->orderByDesc('created_at')
                    ->get()
                    ->toArray();

                $pengaduanRiwayat = DB::table('pengaduan_pegawai')
                    ->whereIn('status', ['selesai', 'arsip', 'investigasiBerjalan', 'menungguReviewKspi'])
                    ->orderByDesc('updated_at')
                    ->limit(50)
                    ->get()
                    ->toArray();

            } elseif ($myRole === 'tpdpk') {
                // TPDPK: Penugasan Investigasi (investigasiBerjalan, revisiInvestigasi) yang ditugaskan ke TPDPK
                $pengaduanMasuk = DB::table('pengaduan_pegawai')
                    ->where('eksekutor', 'tpdpk')
                    ->whereIn('status', ['investigasiBerjalan', 'revisiInvestigasi', 'tindakLanjutBerjalan'])
                    ->orderByDesc('created_at')
                    ->get()
                    ->toArray();

                $pengaduanRiwayat = DB::table('pengaduan_pegawai')
                    ->where('eksekutor', 'tpdpk')
                    ->whereIn('status', ['menungguReviewKspi', 'menungguDirutTahap2', 'selesai', 'arsip'])
                    ->orderByDesc('updated_at')
                    ->limit(50)
                    ->get()
                    ->toArray();
            }

        } catch (\Throwable $e) {
            // Fallback empty on DB error
        }

        return view('pengaduan.index', compact(
            'ctx',
            'myRole',
            'divisiKadiv',
            'tab',
            'pengaduanMasuk',
            'pengaduanSaya',
            'pengaduanRiwayat'
        ));
    }

    /**
     * Submit Pengaduan Baru oleh Pelapor (Pegawai, SDM, dll).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kategori' => 'required|string|in:Pelanggaran Administrasi,Pelanggaran Teknik,Umum',
            'judul' => 'required|string|max:150',
            'deskripsi' => 'required|string',
            'pihak_terlapor' => 'required|string|max:100',
            'nik_pelaku' => 'nullable|string|max:50',
            'jabatan_pelaku' => 'nullable|string|max:100',
            'anonim' => 'nullable|boolean',
            'foto_bukti.*' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
            'dokumen_pendukung.*' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        ]);

        $ctx = $this->getUserContext();
        $nik = $ctx['nik'];
        $nama = $ctx['nama'];
        $now = now();

        // Generate Nomor Pengaduan Unik (PGD-YYYYMMDD-001)
        $prefix = 'PGD-' . date('Ymd');
        $countToday = DB::table('pengaduan_pegawai')->where('nomor_pengaduan', 'like', "$prefix%")->count();
        $nomorPengaduan = $prefix . '-' . str_pad($countToday + 1, 3, '0', STR_PAD_LEFT);

        try {
            $pegawai = DB::table('pegawai')->where('nik', $nik)->first();
            $pegawaiId = $pegawai ? $pegawai->id : null;

            // Simpan Berkas Bukti bila ada
            $fotoBukti = [];
            if ($request->hasFile('foto_bukti')) {
                foreach ($request->file('foto_bukti') as $file) {
                    $path = $file->store('pengaduan_bukti', 'public');
                    $fotoBukti[] = '/storage/' . $path;
                }
            }

            $dokumenPendukung = [];
            if ($request->hasFile('dokumen_pendukung')) {
                foreach ($request->file('dokumen_pendukung') as $file) {
                    $path = $file->store('pengaduan_dokumen', 'public');
                    $dokumenPendukung[] = '/storage/' . $path;
                }
            }

            $id = DB::table('pengaduan_pegawai')->insertGetId([
                'nomor_pengaduan' => $nomorPengaduan,
                'pelapor_id' => $pegawaiId,
                'kategori' => $validated['kategori'],
                'judul' => $validated['judul'],
                'deskripsi' => $validated['deskripsi'],
                'nama_pegawai' => $nama,
                'nik' => $nik,
                'cabang' => $pegawai->unit_kerja ?? 'PDAM Tirta Darma Ayu',
                'golongan' => $pegawai->golongan ?? '',
                'pihak_terlapor' => trim($validated['pihak_terlapor']),
                'nik_pelaku' => trim($validated['nik_pelaku'] ?? ''),
                'jabatan_pelaku' => trim($validated['jabatan_pelaku'] ?? ''),
                'anonim' => $request->boolean('anonim'),
                'foto_bukti' => !empty($fotoBukti) ? json_encode($fotoBukti) : null,
                'dokumen_pendukung' => !empty($dokumenPendukung) ? json_encode($dokumenPendukung) : null,
                'status' => 'menungguKadiv',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Audit Trail Status
            DB::table('riwayat_status_pengaduan')->insert([
                'pengaduan_id' => $id,
                'status' => 'menungguKadiv',
                'status_lama' => null,
                'oleh' => $request->boolean('anonim') ? 'Anonim' : $nama,
                'role' => 'pegawai',
                'aksi' => 'Pengaduan dibuat',
                'keterangan' => 'Pelaku diadukan: ' . trim($validated['pihak_terlapor']) .
                    (!empty($validated['nik_pelaku']) ? ' · NIK ' . trim($validated['nik_pelaku']) : ''),
                'tanggal' => $now,
            ]);

        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal mengirim pengaduan: ' . $e->getMessage());
        }

        return back()->with('success', "Pengaduan berhasil dikirim dengan nomor $nomorPengaduan.");
    }

    /**
     * Detail Pengaduan & Riwayat Timeline Audit Trail.
     */
    public function detail($id)
    {
        $ctx = $this->getUserContext();
        $myRole = $ctx['role'];
        $myNik = $ctx['nik'];

        $pengaduan = DB::table('pengaduan_pegawai')->where('id', $id)->first();
        if (!$pengaduan) {
            abort(404, 'Pengaduan tidak ditemukan.');
        }

        // Cek izin akses: Pemilik pengaduan ATAU Role pemeriksa (Kadiv, KSPI, Dirut, TPDPK)
        $isOwner = ($pengaduan->nik === $myNik);
        $isPrivileged = in_array($myRole, ['kadiv', 'kspi', 'dirut', 'tpdpk']);

        if (!$isOwner && !$isPrivileged && $myRole !== 'sdm') {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat pengaduan ini.');
        }

        $riwayat = DB::table('riwayat_status_pengaduan')
            ->where('pengaduan_id', $id)
            ->orderBy('tanggal', 'asc')
            ->get();

        return view('pengaduan.detail', compact('pengaduan', 'riwayat', 'ctx', 'myRole', 'isOwner', 'isPrivileged'));
    }

    /**
     * KADIV — Verifikasi Pengaduan (Terima / Tolak dicatat) -> Teruskan ke KSPI.
     */
    public function kadivVerifikasi(Request $request, $id)
    {
        $request->validate([
            'keputusan' => 'required|in:terima,tolak',
            'catatan' => 'nullable|string',
            'kategori_baru' => 'nullable|string|in:Pelanggaran Administrasi,Pelanggaran Teknik',
        ]);

        $ctx = $this->getUserContext();
        $now = now();
        $keputusan = $request->keputusan;
        $kategoriBaru = $request->kategori_baru;

        $update = [
            'status' => 'reviewKspi',
            'keputusan_kadiv' => $keputusan,
            'catatan_kadiv' => $request->catatan,
            'updated_at' => $now,
        ];
        if (!empty($kategoriBaru)) {
            $update['kategori'] = $kategoriBaru;
        }

        DB::table('pengaduan_pegawai')->where('id', $id)->update($update);

        $aksi = ($keputusan === 'terima')
            ? 'Verifikasi (Terima), diteruskan ke KSPI' . ($kategoriBaru ? " • kategori diubah ke $kategoriBaru" : '')
            : 'Verifikasi (Tolak dicatat), tetap diteruskan ke KSPI' . ($kategoriBaru ? " • kategori diubah ke $kategoriBaru" : '');

        DB::table('riwayat_status_pengaduan')->insert([
            'pengaduan_id' => $id,
            'status' => 'reviewKspi',
            'status_lama' => 'menungguKadiv',
            'oleh' => $ctx['nama'],
            'role' => 'kadivKategori',
            'aksi' => $aksi,
            'keterangan' => $request->catatan,
            'tanggal' => $now,
        ]);

        return redirect()->route('pengaduan.detail', $id)->with('success', 'Pengaduan berhasil diverifikasi dan diteruskan ke KSPI.');
    }

    /**
     * KADIV — Alihkan Kategori Pelanggaran (ke Kadiv Administrasi / Teknik).
     */
    public function kadivAlihkan(Request $request, $id)
    {
        $request->validate([
            'kategori_baru' => 'required|string|in:Pelanggaran Administrasi,Pelanggaran Teknik',
            'catatan' => 'nullable|string',
        ]);

        $ctx = $this->getUserContext();
        $now = now();
        $kategoriBaru = $request->kategori_baru;

        DB::table('pengaduan_pegawai')->where('id', $id)->update([
            'kategori' => $kategoriBaru,
            'status' => 'menungguKadiv',
            'updated_at' => $now,
        ]);

        DB::table('riwayat_status_pengaduan')->insert([
            'pengaduan_id' => $id,
            'status' => 'menungguKadiv',
            'status_lama' => 'menungguKadiv',
            'oleh' => $ctx['nama'],
            'role' => 'kadivKategori',
            'aksi' => "Kategori dialihkan ke $kategoriBaru",
            'keterangan' => $request->catatan,
            'tanggal' => $now,
        ]);

        return redirect()->route('pengaduan.index')->with('success', "Pengaduan dialihkan ke kategori $kategoriBaru.");
    }

    /**
     * KSPI — Tolak Pengaduan pada Review Awal -> Diarsipkan.
     */
    public function kspiTolak(Request $request, $id)
    {
        $request->validate([
            'catatan' => 'required|string',
        ]);

        $ctx = $this->getUserContext();
        $now = now();

        DB::table('pengaduan_pegawai')->where('id', $id)->update([
            'status' => 'arsip',
            'arsip_pada_tahap' => 'kspi',
            'alasan_arsip' => $request->catatan,
            'updated_at' => $now,
        ]);

        DB::table('riwayat_status_pengaduan')->insert([
            'pengaduan_id' => $id,
            'status' => 'arsip',
            'status_lama' => 'reviewKspi',
            'oleh' => $ctx['nama'],
            'role' => 'kspi',
            'aksi' => 'KSPI menolak pengaduan, diarsipkan',
            'keterangan' => $request->catatan,
            'tanggal' => $now,
        ]);

        return redirect()->route('pengaduan.detail', $id)->with('success', 'Pengaduan berhasil ditolak dan diarsipkan.');
    }

    /**
     * KSPI — Teruskan ke Direktur Utama (Persetujuan Tahap 1).
     */
    public function kspiTeruskanDirut(Request $request, $id)
    {
        $ctx = $this->getUserContext();
        $now = now();

        DB::table('pengaduan_pegawai')->where('id', $id)->update([
            'status' => 'menungguDirutTahap1',
            'updated_at' => $now,
        ]);

        DB::table('riwayat_status_pengaduan')->insert([
            'pengaduan_id' => $id,
            'status' => 'menungguDirutTahap1',
            'status_lama' => 'reviewKspi',
            'oleh' => $ctx['nama'],
            'role' => 'kspi',
            'aksi' => 'Meneruskan pengaduan ke Direktur Utama',
            'keterangan' => $request->catatan,
            'tanggal' => $now,
        ]);

        return redirect()->route('pengaduan.detail', $id)->with('success', 'Pengaduan berhasil diteruskan ke Direktur Utama.');
    }

    /**
     * KSPI — Pilih Eksekutor Investigasi (TPDPK atau Kadiv).
     */
    public function kspiPilihEksekutor(Request $request, $id)
    {
        $request->validate([
            'eksekutor' => 'required|in:tpdpk,kadiv',
            'divisi_kadiv' => 'nullable|required_if:eksekutor,kadiv|in:administrasi,teknik',
            'petugas_investigasi' => 'nullable|string|max:100',
            'catatan' => 'nullable|string',
        ]);

        $ctx = $this->getUserContext();
        $now = now();
        $eksekutor = $request->eksekutor;
        $divisi = $request->divisi_kadiv;
        $petugas = $request->petugas_investigasi;

        DB::table('pengaduan_pegawai')->where('id', $id)->update([
            'status' => 'investigasiBerjalan',
            'eksekutor' => $eksekutor,
            'eksekutor_divisi_kadiv' => $divisi,
            'petugas_investigasi' => $petugas,
            'updated_at' => $now,
        ]);

        $label = ($eksekutor === 'kadiv') ? "Kadiv ($divisi)" : "TPDPK";

        DB::table('riwayat_status_pengaduan')->insert([
            'pengaduan_id' => $id,
            'status' => 'investigasiBerjalan',
            'status_lama' => 'menungguPilihEksekutor',
            'oleh' => $ctx['nama'],
            'role' => 'kspi',
            'aksi' => "Memilih eksekutor investigasi: $label" . ($petugas ? " (Petugas: $petugas)" : ''),
            'keterangan' => $request->catatan,
            'tanggal' => $now,
        ]);

        return redirect()->route('pengaduan.detail', $id)->with('success', "Penugasan investigasi diberikan kepada $label.");
    }

    /**
     * DIRUT — Approval Tahap 1 (Kelayakan Investigasi).
     */
    public function dirutTahap1(Request $request, $id)
    {
        $request->validate([
            'keputusan' => 'required|in:terima,tolak',
            'catatan' => 'nullable|string',
        ]);

        $ctx = $this->getUserContext();
        $now = now();
        $keputusan = $request->keputusan;

        if ($keputusan === 'tolak') {
            DB::table('pengaduan_pegawai')->where('id', $id)->update([
                'status' => 'arsip',
                'keputusan_dirut_tahap1' => 'tolak',
                'catatan_dirut_tahap1' => $request->catatan,
                'arsip_pada_tahap' => 'dirutTahap1',
                'alasan_arsip' => $request->catatan,
                'updated_at' => $now,
            ]);

            DB::table('riwayat_status_pengaduan')->insert([
                'pengaduan_id' => $id,
                'status' => 'arsip',
                'status_lama' => 'menungguDirutTahap1',
                'oleh' => $ctx['nama'],
                'role' => 'direktur',
                'aksi' => 'Menolak kelayakan investigasi, pengaduan diarsipkan',
                'keterangan' => $request->catatan,
                'tanggal' => $now,
            ]);

            return redirect()->route('pengaduan.detail', $id)->with('success', 'Pengaduan ditolak dan diarsipkan.');
        }

        DB::table('pengaduan_pegawai')->where('id', $id)->update([
            'status' => 'menungguPilihEksekutor',
            'keputusan_dirut_tahap1' => 'terima',
            'catatan_dirut_tahap1' => $request->catatan,
            'updated_at' => $now,
        ]);

        DB::table('riwayat_status_pengaduan')->insert([
            'pengaduan_id' => $id,
            'status' => 'menungguPilihEksekutor',
            'status_lama' => 'menungguDirutTahap1',
            'oleh' => $ctx['nama'],
            'role' => 'direktur',
            'aksi' => 'Menyetujui (layak diinvestigasi), diteruskan ke KSPI untuk memilih eksekutor',
            'keterangan' => $request->catatan,
            'tanggal' => $now,
        ]);

        return redirect()->route('pengaduan.detail', $id)->with('success', 'Persetujuan Tahap 1 diberikan. Diteruskan ke KSPI untuk pemilihan eksekutor.');
    }

    /**
     * TPDPK / KADIV (Eksekutor) — Kirim Hasil Investigasi & Surat Rekomendasi Sanksi.
     */
    public function tpdpkHasilInvestigasi(Request $request, $id)
    {
        $request->validate([
            'hasil_investigasi' => 'required|string',
            'surat_rekomendasi' => 'required|string',
            'dokumen_investigasi.*' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ]);

        $ctx = $this->getUserContext();
        $now = now();

        $dokumen = [];
        if ($request->hasFile('dokumen_investigasi')) {
            foreach ($request->file('dokumen_investigasi') as $file) {
                $path = $file->store('investigasi_dokumen', 'public');
                $dokumen[] = '/storage/' . $path;
            }
        }

        $update = [
            'status' => 'menungguReviewKspi',
            'hasil_investigasi' => $request->hasil_investigasi,
            'surat_rekomendasi' => $request->surat_rekomendasi,
            'tanggal_hasil_investigasi' => $now,
            'updated_at' => $now,
        ];
        if (!empty($dokumen)) {
            $update['investigasi_dokumen'] = json_encode($dokumen);
        }

        DB::table('pengaduan_pegawai')->where('id', $id)->update($update);

        DB::table('riwayat_status_pengaduan')->insert([
            'pengaduan_id' => $id,
            'status' => 'menungguReviewKspi',
            'status_lama' => 'investigasiBerjalan',
            'oleh' => $ctx['nama'],
            'role' => $ctx['role'] === 'kadiv' ? 'kadivKategori' : 'tpdpk',
            'aksi' => 'Mengirim hasil investigasi & surat rekomendasi, diteruskan ke KSPI',
            'keterangan' => 'Hasil investigasi dan rekomendasi sanksi telah dilampirkan.',
            'tanggal' => $now,
        ]);

        return redirect()->route('pengaduan.detail', $id)->with('success', 'Hasil investigasi berhasil dikirim ke KSPI untuk peninjauan.');
    }

    /**
     * KSPI — Review Hasil Investigasi (Sesuai -> Dirut Tahap 2 / Tidak -> Revisi Investigasi).
     */
    public function kspiReviewHasil(Request $request, $id)
    {
        $request->validate([
            'sesuai' => 'required|boolean',
            'catatan' => 'nullable|string',
        ]);

        $ctx = $this->getUserContext();
        $now = now();
        $sesuai = $request->boolean('sesuai');

        $statusBaru = $sesuai ? 'menungguDirutTahap2' : 'revisiInvestigasi';

        DB::table('pengaduan_pegawai')->where('id', $id)->update([
            'status' => $statusBaru,
            'catatan_review_hasil_kspi' => $request->catatan,
            'updated_at' => $now,
        ]);

        $aksi = $sesuai
            ? 'Hasil investigasi sesuai, diteruskan ke Direktur Utama'
            : 'Hasil investigasi dikembalikan untuk revisi';

        DB::table('riwayat_status_pengaduan')->insert([
            'pengaduan_id' => $id,
            'status' => $statusBaru,
            'status_lama' => 'menungguReviewKspi',
            'oleh' => $ctx['nama'],
            'role' => 'kspi',
            'aksi' => $aksi,
            'keterangan' => $request->catatan,
            'tanggal' => $now,
        ]);

        return redirect()->route('pengaduan.detail', $id)->with('success', $sesuai
            ? 'Hasil investigasi disetujui KSPI dan diteruskan ke Direktur Utama.'
            : 'Hasil investigasi dikembalikan ke tim investigator untuk revisi.');
    }

    /**
     * DIRUT — Approval Tahap 2 (Terima Hasil Investigasi & Sanksi / Tolak).
     */
    public function dirutTahap2(Request $request, $id)
    {
        $request->validate([
            'keputusan' => 'required|in:terima,tolak',
            'catatan' => 'nullable|string',
        ]);

        $ctx = $this->getUserContext();
        $now = now();
        $keputusan = $request->keputusan;

        if ($keputusan === 'tolak') {
            DB::table('pengaduan_pegawai')->where('id', $id)->update([
                'status' => 'arsip',
                'keputusan_dirut_tahap2' => 'tolak',
                'catatan_dirut_tahap2' => $request->catatan,
                'arsip_pada_tahap' => 'dirutTahap2',
                'alasan_arsip' => $request->catatan,
                'updated_at' => $now,
            ]);

            DB::table('riwayat_status_pengaduan')->insert([
                'pengaduan_id' => $id,
                'status' => 'arsip',
                'status_lama' => 'menungguDirutTahap2',
                'oleh' => $ctx['nama'],
                'role' => 'direktur',
                'aksi' => 'Menolak hasil investigasi, pengaduan diarsipkan',
                'keterangan' => $request->catatan,
                'tanggal' => $now,
            ]);

            return redirect()->route('pengaduan.detail', $id)->with('success', 'Hasil investigasi ditolak dan pengaduan diarsipkan.');
        }

        DB::table('pengaduan_pegawai')->where('id', $id)->update([
            'status' => 'selesai',
            'keputusan_dirut_tahap2' => 'terima',
            'catatan_dirut_tahap2' => $request->catatan,
            'updated_at' => $now,
        ]);

        DB::table('riwayat_status_pengaduan')->insert([
            'pengaduan_id' => $id,
            'status' => 'selesai',
            'status_lama' => 'menungguDirutTahap2',
            'oleh' => $ctx['nama'],
            'role' => 'direktur',
            'aksi' => 'Menerima hasil investigasi & rekomendasi sanksi (Selesai)',
            'keterangan' => $request->catatan,
            'tanggal' => $now,
        ]);

        return redirect()->route('pengaduan.detail', $id)->with('success', 'Hasil investigasi dan rekomendasi sanksi disetujui Direktur Utama.');
    }

    /**
     * DIRUT — Peninjauan Kembali (Hasil investigasi belum cukup, investigasi ulang ke KSPI).
     */
    public function dirutPeninjauanKembali(Request $request, $id)
    {
        $request->validate([
            'catatan' => 'required|string',
        ]);

        $ctx = $this->getUserContext();
        $now = now();

        DB::table('pengaduan_pegawai')->where('id', $id)->update([
            'status' => 'menungguPilihEksekutor',
            'catatan_peninjauan_kembali' => $request->catatan,
            'updated_at' => $now,
        ]);

        DB::table('riwayat_status_pengaduan')->insert([
            'pengaduan_id' => $id,
            'status' => 'menungguPilihEksekutor',
            'status_lama' => 'menungguDirutTahap2',
            'oleh' => $ctx['nama'],
            'role' => 'direktur',
            'aksi' => 'Meminta peninjauan kembali, dikembalikan ke KSPI untuk investigasi ulang',
            'keterangan' => $request->catatan,
            'tanggal' => $now,
        ]);

        return redirect()->route('pengaduan.detail', $id)->with('success', 'Permintaan peninjauan kembali telah dikirim ke KSPI.');
    }
}
