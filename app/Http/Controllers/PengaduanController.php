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
        $nik = (string) ($sessionUser['nik'] ?? '');
        $userLevel = $sessionUser['userlevel'] ?? '5';
        $rolePengaduan = $sessionUser['role_pengaduan'] ?? 'pegawai';
        $divisiKadiv = $sessionUser['divisi_kadiv'] ?? null;
        $nama = $sessionUser['nama_peg'] ?? 'Pegawai';

        $id = null;
        if (!empty($nik)) {
            try {
                $peg = DB::table('pegawai')->where('nik', $nik)->first();
                if ($peg) {
                    $id = $peg->id ?? null;
                    if (empty($nama) || $nama === 'Pegawai') {
                        $nama = $peg->name ?? $peg->nama_peg ?? 'Pegawai';
                    }
                    $dbRole = strtolower($peg->role ?? '');
                    $divisiKadiv = $peg->divisi_kadiv ?? $divisiKadiv;

                    if ($dbRole === 'direktur') {
                        $rolePengaduan = 'dirut';
                    } elseif ($dbRole === 'kspi' && $nik === '1711161') {
                        $rolePengaduan = 'kspi';
                    } elseif ($dbRole === 'tpdpk') {
                        $rolePengaduan = 'tpdpk';
                    } elseif ($dbRole === 'kadiv' || $dbRole === 'kadivkategori') {
                        $rolePengaduan = 'kadiv';
                    } elseif ($userLevel === '1' || $dbRole === 'admin' || $dbRole === 'sdm') {
                        $rolePengaduan = 'sdm';
                    } elseif ($dbRole === 'keuangan' || $dbRole === 'keu') {
                        $rolePengaduan = 'keuangan';
                    }
                }
            } catch (\Throwable $e) {}
        }

        // Pastikan mapping NIK struktural Pengaduan selalu akurat
        if ($nik === '1711161') {
            $rolePengaduan = 'kspi';
            $divisiKadiv = null;
        } elseif ($nik === '1711571') {
            $rolePengaduan = 'kadiv';
            $divisiKadiv = 'administrasi';
        } elseif ($nik === '1711251') {
            $rolePengaduan = 'kadiv';
            $divisiKadiv = 'teknik';
        } elseif ($rolePengaduan === 'kspi' && $nik !== '1711161') {
            // KSPI HANYA NIK 1711161
            $rolePengaduan = 'pegawai';
        }

        return [
            'id' => $id,
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
        $tugasSaya = [];
        $daftarPegawai = [];

        $baseQuery = function () {
            return DB::table('pengaduan_pegawai')
                ->select(
                    'pengaduan_pegawai.*',
                    DB::raw("(SELECT keterangan FROM riwayat_status_pengaduan WHERE pengaduan_id = pengaduan_pegawai.id AND keterangan IS NOT NULL AND TRIM(keterangan) != '' ORDER BY id DESC LIMIT 1) as keterangan_terakhir")
                );
        };

        try {
            // 1. Ambil pengaduan milik sendiri (Semua role punya tab ini)
            $pengaduanSaya = $baseQuery()
                ->where('nik', $myNik)
                ->orderByDesc('created_at')
                ->get()
                ->toArray();

            // 2. Ambil data kotak masuk sesuai wewenang role
            if ($myRole === 'kadiv') {
                $divisiLabel = ($divisiKadiv === 'teknik') ? 'Pelanggaran Teknik' : 'Pelanggaran Administrasi';

                $query = $baseQuery()
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

                $pengaduanRiwayat = $baseQuery()
                    ->where('kategori', $divisiLabel)
                    ->whereNotIn('status', ['menungguKadiv', 'menungguVerifikasiKadiv'])
                    ->orderByDesc('updated_at')
                    ->limit(50)
                    ->get()
                    ->toArray();

            } elseif ($myRole === 'kspi') {
                $pengaduanMasuk = $baseQuery()
                    ->whereIn('status', ['reviewKspi', 'menungguPilihEksekutor', 'menungguReviewKspi', 'ditolakDirektur'])
                    ->orderByDesc('created_at')
                    ->get()
                    ->toArray();

                $pengaduanRiwayat = $baseQuery()
                    ->whereNotIn('status', ['reviewKspi', 'menungguPilihEksekutor', 'menungguReviewKspi', 'ditolakDirektur'])
                    ->orderByDesc('updated_at')
                    ->limit(50)
                    ->get()
                    ->toArray();

            } elseif ($myRole === 'dirut') {
                $pengaduanMasuk = $baseQuery()
                    ->whereIn('status', ['menungguDirutTahap1', 'menungguDirutTahap2', 'menungguPilihEksekutorTindakLanjut'])
                    ->orderByDesc('created_at')
                    ->get()
                    ->toArray();

                $pengaduanRiwayat = $baseQuery()
                    ->whereIn('status', ['selesai', 'arsip', 'investigasiBerjalan', 'menungguReviewKspi'])
                    ->orderByDesc('updated_at')
                    ->limit(50)
                    ->get()
                    ->toArray();

            } elseif ($myRole === 'tpdpk') {
                $pengaduanMasuk = $baseQuery()
                    ->where('eksekutor', 'tpdpk')
                    ->whereIn('status', ['investigasiBerjalan', 'revisiInvestigasi', 'tindakLanjutBerjalan'])
                    ->orderByDesc('created_at')
                    ->get()
                    ->toArray();

                $pengaduanRiwayat = $baseQuery()
                    ->where('eksekutor', 'tpdpk')
                    ->whereIn('status', ['menungguReviewKspi', 'menungguDirutTahap2', 'selesai', 'arsip'])
                    ->orderByDesc('updated_at')
                    ->limit(50)
                    ->get()
                    ->toArray();

            } elseif ($myRole === 'sdm') {
                // SDM HANYA bisa melihat pengaduan yang terbukti pada tahap menungguSdm
                $pengaduanMasuk = $baseQuery()
                    ->where('status', 'menungguSdm')
                    ->where('kesimpulan_investigasi', 'terbukti')
                    ->orderByDesc('created_at')
                    ->get()
                    ->toArray();

                // Riwayat SDM: Pengaduan yang sudah selesai yang terbukti
                $pengaduanRiwayat = $baseQuery()
                    ->where('status', 'selesai')
                    ->where('kesimpulan_investigasi', 'terbukti')
                    ->orderByDesc('updated_at')
                    ->limit(50)
                    ->get()
                    ->toArray();
            }

            // 3. Ambil daftar tugas eksekutor yang ditugaskan ke user ini
            if (!empty($ctx['id'])) {
                $tugasSaya = DB::table('tasks')
                    ->leftJoin('pengaduan_pegawai', 'tasks.pengaduan_id', '=', 'pengaduan_pegawai.id')
                    ->select(
                        'tasks.*',
                        'pengaduan_pegawai.nomor_pengaduan',
                        'pengaduan_pegawai.kategori as kategori_pengaduan',
                        'pengaduan_pegawai.judul as judul_pengaduan',
                        'pengaduan_pegawai.deskripsi as deskripsi_pengaduan',
                        'pengaduan_pegawai.pihak_terlapor'
                    )
                    ->where('tasks.assigned_to', $ctx['id'])
                    ->where('tasks.is_active', true)
                    ->orderByDesc('tasks.created_at')
                    ->get()
                    ->toArray();
            }

            // 4. Daftar pegawai untuk modal pemilihan terlapor & eksekutor
            $daftarPegawai = DB::table('pegawai')
                ->select('id', 'nik', 'name', 'jabatan', 'unit_kerja', 'role')
                ->whereNotNull('name')
                ->orderBy('name')
                ->get()
                ->toArray();

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
            'pengaduanRiwayat',
            'tugasSaya',
            'daftarPegawai'
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

        $this->notifyPengaduanUpdate(
            $id,
            'Pengaduan Berhasil Dikirim 📩',
            "Pengaduan Anda ($nomorPengaduan) berhasil diterima dan menunggu verifikasi.",
            ['kadiv', 'kspi', 'sdm']
        );

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

        if ($myRole === 'sdm' && !$isOwner) {
            // SDM HANYA berhak mengakses pengaduan yang telah terbukti dan diteruskan Dirut
            $isTerbukti = strtolower((string)($pengaduan->kesimpulan_investigasi ?? '')) === 'terbukti';
            $isValidStatus = in_array($pengaduan->status, ['menungguSdm', 'selesai']);
            if (!$isTerbukti || !$isValidStatus) {
                abort(403, 'Pihak SDM hanya dapat mengakses pengaduan yang telah dinyatakan Terbukti dan diteruskan oleh Direktur Utama.');
            }
        } elseif (!$isOwner && !$isPrivileged && $myRole !== 'sdm') {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat pengaduan ini.');
        }

        $riwayat = DB::table('riwayat_status_pengaduan')
            ->where('pengaduan_id', $id)
            ->orderBy('tanggal', 'asc')
            ->get();

        $tasks = DB::table('tasks')
            ->leftJoin('pegawai', 'tasks.assigned_to', '=', 'pegawai.id')
            ->select('tasks.*', 'pegawai.name as nama_eksekutor', 'pegawai.nik as nik_eksekutor', 'pegawai.jabatan as jabatan_eksekutor')
            ->where('tasks.pengaduan_id', $id)
            ->where('tasks.is_active', true)
            ->orderByDesc('tasks.created_at')
            ->get();

        $daftarPegawai = DB::table('pegawai')
            ->select('id', 'nik', 'name', 'jabatan', 'unit_kerja', 'role', 'divisi_kadiv', 'foto_url')
            ->whereNotNull('name')
            ->orderBy('name')
            ->get()
            ->toArray();

        return view('pengaduan.detail', compact('pengaduan', 'riwayat', 'ctx', 'myRole', 'isOwner', 'isPrivileged', 'tasks', 'daftarPegawai'));
    }

    /**
     * Halaman Format Lembar Surat Resmi & Ekspor PDF Pengaduan.
     */
    public function surat($id)
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

        if ($myRole === 'sdm' && !$isOwner) {
            $isTerbukti = strtolower((string)($pengaduan->kesimpulan_investigasi ?? '')) === 'terbukti';
            $isValidStatus = in_array($pengaduan->status, ['menungguSdm', 'selesai']);
            if (!$isTerbukti || !$isValidStatus) {
                abort(403, 'Pihak SDM hanya dapat mengakses pengaduan yang telah dinyatakan Terbukti dan diteruskan oleh Direktur Utama.');
            }
        } elseif (!$isOwner && !$isPrivileged && $myRole !== 'sdm') {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat surat pengaduan ini.');
        }

        $riwayat = DB::table('riwayat_status_pengaduan')
            ->where('pengaduan_id', $id)
            ->orderBy('tanggal', 'asc')
            ->get();

        $tasks = DB::table('tasks')
            ->leftJoin('pegawai', 'tasks.assigned_to', '=', 'pegawai.id')
            ->select('tasks.*', 'pegawai.name as nama_eksekutor', 'pegawai.nik as nik_eksekutor', 'pegawai.jabatan as jabatan_eksekutor')
            ->where('tasks.pengaduan_id', $id)
            ->where('tasks.is_active', true)
            ->orderByDesc('tasks.created_at')
            ->get();

        // Helper decoder array/json
        $decodeList = function ($val) {
            if (empty($val)) return [];
            if (is_array($val)) return $val;
            $res = json_decode($val, true);
            return is_array($res) ? $res : [];
        };

        $fotoBukti = $decodeList($pengaduan->foto_bukti);
        $dokumenPendukung = $decodeList($pengaduan->dokumen_pendukung);
        $videoBukti = $decodeList($pengaduan->video_bukti ?? null);
        $voiceNote = $decodeList($pengaduan->voice_note ?? null);

        $investigasiFoto = $decodeList($pengaduan->investigasi_foto ?? null);
        $investigasiDokumen = $decodeList($pengaduan->investigasi_dokumen ?? null);
        $investigasiVideo = $decodeList($pengaduan->investigasi_video ?? null);
        $investigasiVoice = $decodeList($pengaduan->investigasi_voice ?? null);

        // Data Direktur Utama jika ada di sistem
        $dirut = DB::table('pegawai')
            ->where(function ($q) {
                $q->whereRaw('LOWER(jabatan) LIKE ?', ['%direktur utama%'])
                  ->orWhereRaw('LOWER(role) LIKE ?', ['%direktur%']);
            })
            ->first();

        return view('pengaduan.surat', compact(
            'pengaduan',
            'riwayat',
            'tasks',
            'ctx',
            'myRole',
            'isOwner',
            'fotoBukti',
            'dokumenPendukung',
            'videoBukti',
            'voiceNote',
            'investigasiFoto',
            'investigasiDokumen',
            'investigasiVideo',
            'investigasiVoice',
            'dirut'
        ));
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

        $this->notifyPengaduanUpdate($id, 'Pengaduan Diverifikasi Kadiv', "Pengaduan $id telah diverifikasi Kadiv dan diteruskan ke KSPI.", ['kspi']);

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
     * KSPI — Pilih Eksekutor Investigasi (TPDPK, Kadiv, atau Tim Pegawai).
     */
    public function kspiPilihEksekutor(Request $request, $id)
    {
        $request->validate([
            'eksekutor' => 'nullable|string|in:tpdpk,kadiv,pegawai,kspi',
            'divisi_kadiv' => 'nullable|in:administrasi,teknik',
            'pegawai_ids' => 'nullable|array',
            'pegawai_ids.*' => 'nullable|string',
            'petugas_investigasi' => 'nullable|string|max:255',
            'catatan' => 'nullable|string',
        ]);

        $ctx = $this->getUserContext();
        $now = now();

        // Ambil data pengaduan
        $pengaduan = DB::table('pengaduan_pegawai')->where('id', $id)->first();
        if (!$pengaduan) {
            return back()->with('error', 'Pengaduan tidak ditemukan.');
        }

        // Ambil daftar pegawai terpilih jika ada
        $selectedPegawai = collect();
        if (!empty($request->pegawai_ids)) {
            $selectedPegawai = DB::table('pegawai')->whereIn('id', $request->pegawai_ids)->get();
        }

        if ($selectedPegawai->isEmpty() && empty($request->petugas_investigasi)) {
            return back()->with('error', 'Harap pilih minimal 1 pegawai eksekutor investigasi.');
        }

        $petugasNames = $selectedPegawai->isNotEmpty()
            ? $selectedPegawai->pluck('name')->implode(', ')
            : ($request->petugas_investigasi ?? '');

        $firstExecutor = $selectedPegawai->first();
        $firstExecutorId = $firstExecutor->id ?? null;

        // Resolve nilai kolom eksekutor agar memenuhi check constraint DB: 'kadiv', 'tpdpk', 'kspi'
        // Sesuai dengan _resolveEksekutorCategory di aplikasi simpeg Flutter:
        // Jika kadiv -> 'kadiv', jika kspi -> 'kspi', jika pegawai biasa / tpdpk / staf spi -> 'tpdpk'
        $eksekutorInput = $request->eksekutor;
        $divisi = $request->divisi_kadiv;

        $hasDodi = $selectedPegawai->contains(function ($p) {
            return ($p->nik ?? '') === '1711161' || str_contains(strtolower($p->name ?? ''), 'dodi sudrajat');
        });

        $hasKadiv = $selectedPegawai->contains(function ($p) {
            return str_contains(strtolower($p->role ?? ''), 'kadiv') || in_array($p->nik ?? '', ['1711251', '1711571']);
        });

        if ($hasDodi || $eksekutorInput === 'tpdpk') {
            $dbEksekutor = 'tpdpk';
            $divisi = null;
        } elseif ($hasKadiv || $eksekutorInput === 'kadiv') {
            $dbEksekutor = 'kadiv';
            if (!$divisi && $firstExecutor) {
                $divisi = $firstExecutor->divisi_kadiv ?: (str_contains(strtolower($firstExecutor->jabatan ?? ''), 'teknik') || ($firstExecutor->nik ?? '') === '1711251' ? 'teknik' : 'administrasi');
            }
        } else {
            $dbEksekutor = 'tpdpk';
        }

        DB::table('pengaduan_pegawai')->where('id', $id)->update([
            'status' => 'investigasiBerjalan',
            'eksekutor' => $dbEksekutor,
            'executor_id' => $firstExecutorId,
            'eksekutor_divisi_kadiv' => $divisi,
            'petugas_investigasi' => $petugasNames,
            'updated_at' => $now,
        ]);

        // Nonaktifkan task lama jika ada perubahan tim eksekutor
        if ($selectedPegawai->isNotEmpty()) {
            DB::table('tasks')
                ->where('pengaduan_id', $id)
                ->where('is_active', true)
                ->whereNotIn('assigned_to', $selectedPegawai->pluck('id'))
                ->update([
                    'is_active' => false,
                    'status' => 'Dibatalkan',
                    'updated_at' => $now,
                ]);

            $assignedById = (!empty($ctx['id']) && preg_match('/^[0-9a-f-]{36}$/i', (string)$ctx['id'])) ? $ctx['id'] : null;

            // Buat record task baru untuk setiap pegawai yang belum memiliki task aktif
            foreach ($selectedPegawai as $p) {
                $exists = DB::table('tasks')
                    ->where('pengaduan_id', $id)
                    ->where('assigned_to', $p->id)
                    ->where('is_active', true)
                    ->exists();

                if (!$exists) {
                    DB::table('tasks')->insert([
                        'pengaduan_id' => $id,
                        'assigned_to' => $p->id,
                        'assigned_by' => $assignedById,
                        'assigned_by_name' => $ctx['nama'],
                        'title' => 'Investigasi: ' . ($pengaduan->nomor_pengaduan ?? ('PGD-' . $id)),
                        'category' => $pengaduan->kategori ?? 'Investigasi',
                        'description' => $request->catatan ?: ($pengaduan->judul ?? 'Investigasi pengaduan'),
                        'status' => 'Menunggu',
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }

        $label = match ($dbEksekutor) {
            'kadiv' => "Kadiv SPI ($divisi)",
            'tpdpk' => 'Tim Eksekutor Investigasi',
            'kspi' => 'KSPI',
            default => 'Eksekutor',
        };

        DB::table('riwayat_status_pengaduan')->insert([
            'pengaduan_id' => $id,
            'status' => 'investigasiBerjalan',
            'status_lama' => 'menungguPilihEksekutor',
            'oleh' => $ctx['nama'],
            'role' => 'kspi',
            'aksi' => "Memilih eksekutor investigasi: $label" . (!empty($petugasNames) ? " ($petugasNames)" : ''),
            'keterangan' => $request->catatan,
            'tanggal' => $now,
        ]);

        return redirect()->route('pengaduan.detail', $id)->with('success', "Penugasan investigasi diberikan kepada $label: $petugasNames.");
    }

    /**
     * Eksekutor — Perbarui Status Tugas (Tasks).
     */
    public function updateTask(Request $request, $taskId)
    {
        $request->validate([
            'status' => 'required|in:Diproses,Selesai',
            'notes' => 'nullable|string',
            'foto_bukti.*' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
            'dokumen.*' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        ]);

        $ctx = $this->getUserContext();
        $task = DB::table('tasks')->where('id', $taskId)->first();
        if (!$task) {
            return back()->with('error', 'Tugas tidak ditemukan.');
        }

        if ($task->assigned_to !== $ctx['id'] && $ctx['role'] !== 'kspi') {
            return back()->with('error', 'Anda tidak memiliki hak akses untuk memperbarui tugas ini.');
        }

        $now = now();
        $uploadedFiles = [];
        if ($request->hasFile('foto_bukti')) {
            foreach ($request->file('foto_bukti') as $file) {
                $path = $file->store('tasks_bukti', 'public');
                $uploadedFiles[] = '/storage/' . $path;
            }
        }
        if ($request->hasFile('dokumen')) {
            foreach ($request->file('dokumen') as $file) {
                $path = $file->store('tasks_dokumen', 'public');
                $uploadedFiles[] = '/storage/' . $path;
            }
        }

        $currentProof = json_decode($task->proof_files ?? '[]', true) ?: [];
        $allProof = array_merge($currentProof, $uploadedFiles);

        DB::table('tasks')->where('id', $taskId)->update([
            'status' => $request->status,
            'notes' => $request->notes ?: $task->notes,
            'proof_files' => json_encode($allProof),
            'updated_at' => $now,
        ]);

        if ($request->status === 'Selesai') {
            DB::table('pengaduan_pegawai')->where('id', $task->pengaduan_id)->update([
                'status' => 'menungguReviewKspi',
                'hasil_investigasi' => $request->notes ?: 'Tugas investigasi diselesaikan oleh eksekutor.',
                'updated_at' => $now,
            ]);

            DB::table('riwayat_status_pengaduan')->insert([
                'pengaduan_id' => $task->pengaduan_id,
                'status' => 'menungguReviewKspi',
                'status_lama' => 'investigasiBerjalan',
                'oleh' => $ctx['nama'],
                'role' => $ctx['role'] ?: 'eksekutor',
                'aksi' => 'Tugas investigasi diselesaikan oleh ' . $ctx['nama'],
                'keterangan' => $request->notes,
                'tanggal' => $now,
            ]);
        }

        return back()->with('success', "Status tugas berhasil diperbarui menjadi {$request->status}.");
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
     * Alur langsung ke Direktur Utama (menungguDirutTahap2) dengan kesimpulan 'terbukti' / 'tidak_terbukti'.
     */
    public function tpdpkHasilInvestigasi(Request $request, $id)
    {
        $request->validate([
            'kesimpulan_investigasi' => 'required|in:terbukti,tidak_terbukti',
            'hasil_investigasi' => 'required|string',
            'surat_rekomendasi' => 'required|string',
            'foto_bukti.*' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:10240',
            'video_bukti.*' => 'nullable|file|mimes:mp4,mov,avi,mkv,webm,3gp|max:102400',
            'voice_bukti.*' => 'nullable|file|mimes:mp3,wav,ogg,m4a,aac,webm|max:51200',
            'dokumen_investigasi.*' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx|max:20480',
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

        $foto = [];
        if ($request->hasFile('foto_bukti')) {
            foreach ($request->file('foto_bukti') as $file) {
                $path = $file->store('investigasi_foto', 'public');
                $foto[] = '/storage/' . $path;
            }
        }

        $video = [];
        if ($request->hasFile('video_bukti')) {
            foreach ($request->file('video_bukti') as $file) {
                $path = $file->store('investigasi_video', 'public');
                $video[] = '/storage/' . $path;
            }
        }

        $voice = [];
        if ($request->hasFile('voice_bukti')) {
            foreach ($request->file('voice_bukti') as $file) {
                $path = $file->store('investigasi_voice', 'public');
                $voice[] = '/storage/' . $path;
            }
        }

        $update = [
            'status' => 'menungguDirutTahap2',
            'kesimpulan_investigasi' => $request->kesimpulan_investigasi,
            'hasil_investigasi' => $request->hasil_investigasi,
            'surat_rekomendasi' => $request->surat_rekomendasi,
            'tanggal_hasil_investigasi' => $now,
            'updated_at' => $now,
        ];
        if (!empty($dokumen)) {
            $update['investigasi_dokumen'] = json_encode($dokumen);
        }
        if (!empty($foto)) {
            $update['investigasi_foto'] = json_encode($foto);
        }
        if (!empty($video)) {
            $update['investigasi_video'] = json_encode($video);
        }
        if (!empty($voice)) {
            $update['investigasi_voice'] = json_encode($voice);
        }

        DB::table('pengaduan_pegawai')->where('id', $id)->update($update);

        // Update status tasks milik pengaduan ini menjadi Selesai
        DB::table('tasks')
            ->where('pengaduan_id', $id)
            ->where('is_active', true)
            ->update([
                'status' => 'Selesai',
                'notes' => 'Investigasi selesai: Kesimpulan ' . ($request->kesimpulan_investigasi === 'terbukti' ? 'TERBUKTI' : 'TIDAK TERBUKTI'),
                'updated_at' => $now,
            ]);

        $kesimpulanLabel = ($request->kesimpulan_investigasi === 'terbukti') ? 'TERBUKTI' : 'TIDAK TERBUKTI';

        DB::table('riwayat_status_pengaduan')->insert([
            'pengaduan_id' => $id,
            'status' => 'menungguDirutTahap2',
            'status_lama' => 'investigasiBerjalan',
            'oleh' => $ctx['nama'],
            'role' => $ctx['role'] === 'kadiv' ? 'kadiv' : 'tpdpk',
            'aksi' => "Menyelesaikan investigasi (Kesimpulan: {$kesimpulanLabel}) & menerbitkan Surat Rekomendasi langsung ke Direktur Utama",
            'keterangan' => $request->hasil_investigasi,
            'tanggal' => $now,
        ]);

        $this->notifyPengaduanUpdate(
            $id,
            'Hasil Investigasi & Rekomendasi Masuk 📋',
            "Hasil investigasi pengaduan $id ({$kesimpulanLabel}) dan Surat Rekomendasi telah masuk langsung ke Direktur Utama.",
            ['direktur']
        );

        return redirect()->route('pengaduan.detail', $id)->with('success', "Hasil investigasi ($kesimpulanLabel) dan Surat Rekomendasi berhasil diterbitkan langsung ke Direktur Utama.");
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
     * DIRUT — Approval Tahap 2 (Terima Hasil Investigasi / Tinjau Ulang / Tolak).
     * Jika Tinjau Ulang -> kembali ke KSPI untuk memilih eksekutor ulang.
     * Jika Terima:
     *   - Tidak Terbukti -> Diarsipkan (selesai, pulihkan nama baik pihak terlapor)
     *   - Terbukti -> Diteruskan ke SDM (menungguSdm) untuk dibuatkan Surat Putusan Sanksi.
     */
    public function dirutTahap2(Request $request, $id)
    {
        $request->validate([
            'keputusan' => 'required|in:terima,tinjau_ulang',
            'catatan' => 'nullable|string',
        ]);

        $ctx = $this->getUserContext();
        $now = now();
        $keputusan = $request->keputusan;

        $pengaduan = DB::table('pengaduan_pegawai')->where('id', $id)->first();
        if (!$pengaduan) {
            return back()->with('error', 'Pengaduan tidak ditemukan.');
        }

        if ($keputusan === 'tinjau_ulang') {
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
                'aksi' => 'Meminta tinjau ulang, dikembalikan ke KSPI untuk menugaskan eksekutor ulang',
                'keterangan' => $request->catatan,
                'tanggal' => $now,
            ]);

            $this->notifyPengaduanUpdate($id, 'Pengaduan Diminta Tinjau Ulang', "Direktur Utama meminta tinjau ulang atas pengaduan $id ke KSPI.", ['kspi']);

            return redirect()->route('pengaduan.detail', $id)->with('success', 'Permintaan tinjau ulang dikirim ke KSPI untuk pemilihan eksekutor ulang.');
        }

        // Keputusan == 'terima'
        $kesimpulan = strtolower((string)($pengaduan->kesimpulan_investigasi ?? ''));

        if ($kesimpulan === 'tidak_terbukti') {
            // Jika TIDAK TERBUKTI -> langsung diarsipkan dan selesai
            DB::table('pengaduan_pegawai')->where('id', $id)->update([
                'status' => 'arsip',
                'keputusan_dirut_tahap2' => 'terima',
                'catatan_dirut_tahap2' => $request->catatan,
                'arsip_pada_tahap' => 'dirutTahap2',
                'alasan_arsip' => 'Hasil investigasi menyatakan TIDAK TERBUKTI. Pengaduan diarsipkan dan nama baik pihak terlapor dipulihkan.',
                'updated_at' => $now,
            ]);

            DB::table('riwayat_status_pengaduan')->insert([
                'pengaduan_id' => $id,
                'status' => 'arsip',
                'status_lama' => 'menungguDirutTahap2',
                'oleh' => $ctx['nama'],
                'role' => 'direktur',
                'aksi' => 'Menerima hasil investigasi: TIDAK TERBUKTI (Diarsipkan & nama baik terlapor dipulihkan)',
                'keterangan' => $request->catatan ?: 'Hasil pemeriksaan menyatakan dugaan pelanggaran tidak terbukti. Kasus ditutup dan diarsipkan.',
                'tanggal' => $now,
            ]);

            $this->notifyPengaduanUpdate($id, 'Pengaduan Dihentikan (Tidak Terbukti) 📂', "Hasil investigasi pengaduan $id tidak terbukti dan diarsipkan.");

            return redirect()->route('pengaduan.detail', $id)->with('success', 'Hasil investigasi (Tidak Terbukti) diterima Direktur Utama. Pengaduan berhasil diarsipkan.');
        } else {
            // Jika TERBUKTI -> diteruskan ke SDM untuk dibuatkan surat putusan sanksi
            DB::table('pengaduan_pegawai')->where('id', $id)->update([
                'status' => 'menungguSdm',
                'keputusan_dirut_tahap2' => 'terima',
                'catatan_dirut_tahap2' => $request->catatan,
                'updated_at' => $now,
            ]);

            DB::table('riwayat_status_pengaduan')->insert([
                'pengaduan_id' => $id,
                'status' => 'menungguSdm',
                'status_lama' => 'menungguDirutTahap2',
                'oleh' => $ctx['nama'],
                'role' => 'direktur',
                'aksi' => 'Menerima hasil investigasi: TERBUKTI (Diteruskan ke SDM untuk Surat Putusan Sanksi)',
                'keterangan' => $request->catatan ?: 'Diteruskan ke Bagian SDM untuk penerbitan Surat Putusan Pemberian Sanksi.',
                'tanggal' => $now,
            ]);

            $this->notifyPengaduanUpdate(
                $id,
                'Pengaduan Diteruskan ke SDM ⚠️',
                "Hasil investigasi pengaduan $id terbukti dan diteruskan ke SDM untuk penetapan Surat Putusan Sanksi.",
                ['sdm']
            );

            return redirect()->route('pengaduan.detail', $id)->with('success', 'Hasil investigasi (Terbukti) diterima Direktur Utama. Diteruskan ke Bagian SDM untuk penerbitan surat putusan sanksi.');
        }
    }

    /**
     * SDM — Menerbitkan Surat Putusan Sanksi Resmi (Nomor Surat, Jenis Sanksi, Upload Berkas).
     * Pengaduan otomatis berstatus 'selesai'.
     */
    public function sdmPutusanSanksi(Request $request, $id)
    {
        $request->validate([
            'nomor_surat_putusan' => 'required|string|max:100',
            'jenis_sanksi' => 'required|string|max:100',
            'catatan_sdm' => 'nullable|string',
            'file_surat_putusan' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:20480',
        ]);

        $ctx = $this->getUserContext();
        if ($ctx['role'] !== 'sdm') {
            return back()->with('error', 'Hanya Bagian SDM yang berwenang menerbitkan Surat Putusan Sanksi.');
        }

        $now = now();
        $filePath = null;
        if ($request->hasFile('file_surat_putusan')) {
            $path = $request->file('file_surat_putusan')->store('surat_putusan', 'public');
            $filePath = '/storage/' . $path;
        }

        DB::table('pengaduan_pegawai')->where('id', $id)->update([
            'status' => 'selesai',
            'nomor_surat_putusan' => $request->nomor_surat_putusan,
            'jenis_sanksi' => $request->jenis_sanksi,
            'catatan_sdm' => $request->catatan_sdm,
            'file_surat_putusan' => $filePath,
            'tanggal_surat_putusan' => $now,
            'updated_at' => $now,
        ]);

        DB::table('riwayat_status_pengaduan')->insert([
            'pengaduan_id' => $id,
            'status' => 'selesai',
            'status_lama' => 'menungguSdm',
            'oleh' => $ctx['nama'],
            'role' => 'sdm',
            'aksi' => "Menerbitkan Surat Putusan Sanksi No. {$request->nomor_surat_putusan} ({$request->jenis_sanksi})",
            'keterangan' => $request->catatan_sdm ?: "Surat Keputusan Sanksi telah diterbitkan secara resmi oleh Bagian SDM.",
            'tanggal' => $now,
        ]);

        $this->notifyPengaduanUpdate(
            $id,
            'Surat Putusan Sanksi Diterbitkan ✅',
            "Surat Putusan No. {$request->nomor_surat_putusan} ({$request->jenis_sanksi}) telah diterbitkan oleh SDM. Pengaduan $id selesai."
        );

        return redirect()->route('pengaduan.detail', $id)->with('success', "Surat Putusan Sanksi No. {$request->nomor_surat_putusan} berhasil diterbitkan dan kasus telah selesai.");
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

        $this->notifyPengaduanUpdate($id, 'Pengaduan Diminta Peninjauan Kembali', "Direktur Utama meminta peninjauan kembali atas pengaduan $id.", ['kspi']);

        return redirect()->route('pengaduan.detail', $id)->with('success', 'Permintaan peninjauan kembali telah dikirim ke KSPI.');
    }

    /**
     * Helper Push Notification OneSignal untuk progress Pengaduan.
     */
    protected function notifyPengaduanUpdate($pengaduanId, string $statusJudul, string $pesan, ?array $notifyRoles = null)
    {
        try {
            $p = DB::table('pengaduan_pegawai')->where('id', $pengaduanId)->first();
            if ($p && ! empty($p->nik)) {
                \App\Services\OneSignalService::kirimNotifikasiPegawai(
                    $p->nik,
                    $statusJudul,
                    $pesan,
                    ['type' => 'pengaduan', 'id' => (string) $pengaduanId, 'nomor' => $p->nomor_pengaduan ?? '']
                );
            }
            if (! empty($notifyRoles)) {
                \App\Services\OneSignalService::kirimNotifikasiRole(
                    $notifyRoles,
                    $statusJudul,
                    $pesan,
                    ['type' => 'pengaduan', 'id' => (string) $pengaduanId, 'nomor' => $p->nomor_pengaduan ?? '']
                );
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('OneSignal notify pengaduan failed: ' . $e->getMessage());
        }
    }
}
