<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\PegawaiController;
use App\Http\Controllers\PejabatTtdController;
use Carbon\Carbon;

class CutiController extends Controller
{
    /**
     * Modul Cuti Pegawai & Format Cetak Fisik Surat Izin Cuti Resmi (Dinas)
     * Untuk Role 1 (SDM), Role 5 (Pegawai), dan Role 7 (Direksi).
     */

    protected function getBulanRomawi(int $bulan): string
    {
        $romawi = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV',
            5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII',
            9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
        ];
        return $romawi[$bulan] ?? 'I';
    }

    /**
     * Tampilkan data dan daftar pengajuan cuti pegawai.
     */
    public function index(Request $request)
    {
        $tahun = (int) $request->get('tahun', now()->year);
        $myRole = (string) session('simpeg_user.userlevel', '1');
        $myNik = (string) session('simpeg_user.nik', '');
        $statusFilter = $request->get('status', 'all');

        try {
            // 1. Ambil dari tabel pengajuan_cuti
            $query = DB::table('pengajuan_cuti')
                ->leftJoin('pegawai', 'pengajuan_cuti.pegawai_id', '=', 'pegawai.id')
                ->select(
                    'pengajuan_cuti.*',
                    'pegawai.nik as pegawai_nik',
                    'pegawai.name as nama_lengkap',
                    'pegawai.gelar',
                    'pegawai.unit_kerja',
                    'pegawai.jabatan',
                    'pegawai.golongan'
                )
                ->where(function ($q) use ($tahun) {
                    $q->whereYear('pengajuan_cuti.tanggal_mulai', $tahun)
                      ->orWhereYear('pengajuan_cuti.created_at', $tahun);
                })
                ->orderByDesc('pengajuan_cuti.created_at');

            if ($myRole === '5') {
                $query->where(function ($q) use ($myNik) {
                    $q->where('pegawai.nik', $myNik);
                });
            }

            if ($statusFilter !== 'all' && in_array($statusFilter, ['PENDING', 'DISETUJUI', 'DITOLAK'])) {
                $query->where('pengajuan_cuti.status', $statusFilter);
            }

            $pengajuan = $query->get()->map(function ($row) {
                $namaLengkap = trim(($row->nama_lengkap ?? $row->nama_pegawai ?? 'Pegawai') . ($row->gelar ? ', ' . $row->gelar : ''));
                $tglMulai = $row->tanggal_mulai ? Carbon::parse($row->tanggal_mulai) : Carbon::parse($row->created_at);
                $tglSelesai = $row->tanggal_selesai ? Carbon::parse($row->tanggal_selesai) : $tglMulai;
                $durasi = max(1, $tglMulai->diffInDays($tglSelesai) + 1);

                return [
                    'id'              => $row->id,
                    'is_db'           => true,
                    'nik'             => $row->pegawai_nik ?? '-',
                    'nama'            => $namaLengkap,
                    'unit_kerja'      => $row->unit_kerja ?? 'Kantor Pusat',
                    'jabatan'         => $row->jabatan ?? '-',
                    'golongan'        => $row->golongan ?? 'PT',
                    'jenis'           => $row->jenis ?? 'Cuti Tahunan',
                    'no_surat'        => $row->no_surat ?? ('850/' . sprintf('%03d', $row->id) . '/SDM/' . $this->getBulanRomawi($tglMulai->month) . '/' . $tglMulai->year),
                    'tanggal_mulai'   => $row->tanggal_mulai ?? $row->created_at,
                    'tanggal_selesai' => $row->tanggal_selesai ?? $row->created_at,
                    'durasi_hari'     => $durasi,
                    'alasan'          => $row->alasan ?? '-',
                    'alamat_cuti'     => $row->alamat_cuti ?? '-',
                    'no_telp'         => $row->no_telp ?? '-',
                    'status'          => $row->status ?? 'PENDING',
                    'diputuskan_oleh' => $row->diputuskan_oleh ?? '-',
                    'catatan_approver'=> $row->catatan_approver ?? '-',
                    'created_at'      => $row->created_at,
                ];
            })->toArray();

            $cuti = $pengajuan;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('CutiController index: ' . $e->getMessage());
            $cuti = [];
        }

        $pegawaiList = app(PegawaiController::class)->all();

        return view('cuti.index', compact('cuti', 'tahun', 'myRole', 'myNik', 'statusFilter', 'pegawaiList'));
    }

    /**
     * Cetak Fisik Surat Izin Cuti Resmi (Dinas) Format Surat Keputusan Direksi.
     */
    public function surat($id)
    {
        $cuti = null;
        $isLegacy = str_starts_with((string) $id, 'p-');

        if ($isLegacy) {
            $prestasiId = substr($id, 2);
            $row = DB::table('prestasi')
                ->leftJoin('pegawai', 'prestasi.pegawai_id', '=', 'pegawai.id')
                ->where('prestasi.id', $prestasiId)
                ->select('prestasi.*', 'pegawai.nik as pegawai_nik', 'pegawai.name as nama_lengkap', 'pegawai.gelar', 'pegawai.unit_kerja', 'pegawai.jabatan', 'pegawai.golongan', 'pegawai.no_telp', 'pegawai.alamat')
                ->first();

            if ($row) {
                $bln = (int) ($row->bulan ?? 1);
                $thn = (int) ($row->tahun ?? now()->year);
                $tglMulai = Carbon::create($thn, $bln, 1);
                $durasi = max(1, (int) $row->cuti);
                $tglSelesai = (clone $tglMulai)->addDays($durasi - 1);
                $namaLengkap = trim(($row->nama_lengkap ?? 'Pegawai') . ($row->gelar ? ', ' . $row->gelar : ''));

                $cuti = [
                    'id'              => $id,
                    'no_surat'        => '850/P-' . $row->id . '/SDM/' . $this->getBulanRomawi($bln) . '/' . $thn,
                    'nik'             => $row->pegawai_nik ?? '-',
                    'nama'            => $namaLengkap,
                    'unit_kerja'      => $row->unit_kerja ?? 'Kantor Pusat',
                    'jabatan'         => $row->jabatan ?? 'Staf',
                    'golongan'        => $row->golongan ?? 'PT',
                    'jenis'           => 'Cuti Tahunan',
                    'tanggal_mulai'   => $tglMulai->format('Y-m-d'),
                    'tanggal_selesai' => $tglSelesai->format('Y-m-d'),
                    'durasi_hari'     => $durasi,
                    'alasan'          => 'Keperluan pribadi / keluarga',
                    'alamat_cuti'     => $row->alamat ?? 'Indramayu',
                    'no_telp'         => $row->no_telp ?? '-',
                    'status'          => 'DISETUJUI',
                    'diputuskan_oleh' => 'Heddy Kelana, S.H.',
                    'tanggal_surat'   => $tglMulai->subDays(3)->format('Y-m-d'),
                ];
            }
        } else {
            $row = DB::table('pengajuan_cuti')
                ->leftJoin('pegawai', 'pengajuan_cuti.pegawai_id', '=', 'pegawai.id')
                ->where('pengajuan_cuti.id', $id)
                ->select('pengajuan_cuti.*', 'pegawai.nik as pegawai_nik', 'pegawai.name as nama_lengkap', 'pegawai.gelar', 'pegawai.unit_kerja', 'pegawai.jabatan', 'pegawai.golongan', 'pegawai.no_telp as peg_telp', 'pegawai.alamat as peg_alamat')
                ->first();

            if ($row) {
                $tglMulai = $row->tanggal_mulai ? Carbon::parse($row->tanggal_mulai) : Carbon::parse($row->created_at);
                $tglSelesai = $row->tanggal_selesai ? Carbon::parse($row->tanggal_selesai) : $tglMulai;
                $durasi = max(1, $tglMulai->diffInDays($tglSelesai) + 1);
                $namaLengkap = trim(($row->nama_lengkap ?? $row->nama_pegawai ?? 'Pegawai') . ($row->gelar ? ', ' . $row->gelar : ''));

                $noSurat = $row->no_surat;
                if (!$noSurat) {
                    $noSurat = '850/' . sprintf('%03d', $row->id) . '/SDM/' . $this->getBulanRomawi($tglMulai->month) . '/' . $tglMulai->year;
                }

                $cuti = [
                    'id'              => $row->id,
                    'no_surat'        => $noSurat,
                    'nik'             => $row->pegawai_nik ?? '-',
                    'nama'            => $namaLengkap,
                    'unit_kerja'      => $row->unit_kerja ?? 'Kantor Pusat',
                    'jabatan'         => $row->jabatan ?? 'Staf',
                    'golongan'        => $row->golongan ?? 'PT',
                    'jenis'           => $row->jenis ?? 'Cuti Tahunan',
                    'tanggal_mulai'   => $tglMulai->format('Y-m-d'),
                    'tanggal_selesai' => $tglSelesai->format('Y-m-d'),
                    'durasi_hari'     => $durasi,
                    'alasan'          => $row->alasan ?? 'Keperluan keluarga',
                    'alamat_cuti'     => $row->alamat_cuti ?: ($row->peg_alamat ?: 'Indramayu'),
                    'no_telp'         => $row->no_telp ?: ($row->peg_telp ?: '-'),
                    'status'          => $row->status ?? 'DISETUJUI',
                    'diputuskan_oleh' => $row->diputuskan_oleh ?? 'Heddy Kelana, S.H.',
                    'tanggal_surat'   => Carbon::parse($row->created_at)->format('Y-m-d'),
                ];
            }
        }

        if (!$cuti) {
            return redirect()->route('cuti.index')->withErrors(['error' => 'Data cuti tidak ditemukan.']);
        }

        // Ambil pejabat penandatangan SDM & Direksi secara dinamis dari tbl_ttd
        $pejabatTtd = PejabatTtdController::getTtd('gaji');

        return view('cuti.surat', compact('cuti', 'pejabatTtd'));
    }

    /**
     * Form Pengajuan Cuti Baru (Role 5 Pegawai atau Role 1 SDM).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'pegawai_nik'     => 'nullable|string',
            'jenis'           => 'required|string|max:100',
            'tanggal_mulai'   => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'alasan'          => 'required|string|max:500',
            'alamat_cuti'     => 'nullable|string|max:255',
            'no_telp'         => 'nullable|string|max:50',
        ]);

        $myRole = (string) session('simpeg_user.userlevel', '1');
        $myNik = (string) session('simpeg_user.nik', '');

        $targetNik = ($myRole === '5') ? $myNik : ($validated['pegawai_nik'] ?? $myNik);
        $pegawai = DB::table('pegawai')->where('nik', $targetNik)->first();

        if (!$pegawai) {
            return back()->withInput()->withErrors(['pegawai_nik' => 'Pegawai dengan NIK tersebut tidak ditemukan.']);
        }

        $pegawaiUserId = $pegawai->id;

        $tglMulai = Carbon::parse($validated['tanggal_mulai']);
        $thn = $tglMulai->year;
        $blnRomawi = $this->getBulanRomawi($tglMulai->month);

        $nextNum = DB::table('pengajuan_cuti')->count() + 1;
        $noSurat = "850/" . sprintf('%03d', $nextNum) . "/SDM/{$blnRomawi}/{$thn}";

        $status = ($myRole === '1') ? 'DISETUJUI' : 'PENDING';
        $diputuskanOleh = ($myRole === '1') ? (session('simpeg_user.name') ?? 'Heddy Kelana, S.H.') : null;

        try {
            $insertedId = DB::table('pengajuan_cuti')->insertGetId([
                'pegawai_id'      => $pegawaiUserId,
                'nama_pegawai'    => $pegawai->name,
                'jenis'           => $validated['jenis'],
                'no_surat'        => $noSurat,
                'tanggal_mulai'   => $validated['tanggal_mulai'],
                'tanggal_selesai' => $validated['tanggal_selesai'],
                'alasan'          => $validated['alasan'],
                'alamat_cuti'     => $validated['alamat_cuti'] ?: $pegawai->alamat,
                'no_telp'         => $validated['no_telp'] ?: $pegawai->no_telp,
                'status'          => $status,
                'diputuskan_oleh' => $diputuskanOleh,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            // Log Audit Trail & Notifikasi jika pegawai biasa mengajukan
            if (class_exists(\App\Http\Controllers\AuditLogController::class)) {
                \App\Http\Controllers\AuditLogController::record(
                    'CREATE',
                    'Cuti',
                    "Pengajuan {$validated['jenis']} baru (#{$insertedId}) oleh {$pegawai->name} ({$targetNik}) periode " . Carbon::parse($validated['tanggal_mulai'])->format('d/m/Y') . " s/d " . Carbon::parse($validated['tanggal_selesai'])->format('d/m/Y')
                );
            }

            if ($myRole === '5' && class_exists(\App\Services\OneSignalService::class)) {
                \App\Services\OneSignalService::kirimNotifikasiRole(
                    ['sdm', 'admin'],
                    'Permohonan Cuti Baru 📋',
                    "Pegawai {$pegawai->name} ({$targetNik}) mengajukan {$validated['jenis']}.",
                    ['type' => 'cuti', 'id' => (string)$insertedId]
                );
            }

            return redirect()->route('cuti.index')
                ->with('success', "Pengajuan {$validated['jenis']} untuk {$pegawai->name} (No: {$noSurat}) berhasil disimpan!");
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Store pengajuan cuti error: ' . $e->getMessage());
            return back()->withInput()->withErrors(['error' => 'Gagal menyimpan pengajuan cuti: ' . $e->getMessage()]);
        }
    }

    /**
     * Approve / Reject Pengajuan Cuti oleh SDM.
     */
    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status'           => 'required|string|in:PENDING,DISETUJUI,DITOLAK',
            'catatan_approver' => 'nullable|string|max:500',
        ]);

        try {
            $cutiRow = DB::table('pengajuan_cuti')
                ->leftJoin('pegawai', 'pengajuan_cuti.pegawai_id', '=', 'pegawai.id')
                ->where('pengajuan_cuti.id', $id)
                ->select(
                    'pengajuan_cuti.*',
                    'pegawai.nik',
                    'pegawai.name as nama_lengkap',
                    'pegawai.id as peg_uuid'
                )
                ->first();

            if (!$cutiRow) {
                return back()->withErrors(['error' => 'Data permohonan cuti tidak ditemukan.']);
            }

            $approverName = session('simpeg_user.nama_peg') ?? session('simpeg_user.name') ?? 'Bagian SDM';

            $updateData = [
                'status'          => $validated['status'],
                'diputuskan_oleh' => $approverName,
                'updated_at'      => now(),
            ];

            if ($request->has('catatan_approver')) {
                $updateData['catatan_approver'] = $validated['catatan_approver'];
            }

            if ($validated['status'] === 'DISETUJUI' && empty($cutiRow->no_surat)) {
                $tgl = $cutiRow->tanggal_mulai ? Carbon::parse($cutiRow->tanggal_mulai) : now();
                $updateData['no_surat'] = '850/' . sprintf('%03d', $id) . '/SDM/' . $this->getBulanRomawi($tgl->month) . '/' . $tgl->year;
            }

            DB::table('pengajuan_cuti')->where('id', $id)->update($updateData);

            $targetPegawaiId = $cutiRow->peg_uuid ?: $cutiRow->pegawai_id;
            if (!$targetPegawaiId && !empty($cutiRow->nik)) {
                $peg = DB::table('pegawai')->where('nik', $cutiRow->nik)->first();
                if ($peg) {
                    $targetPegawaiId = $peg->id;
                }
            }

            $statusIcon = $validated['status'] === 'DISETUJUI' ? '✅' : ($validated['status'] === 'DITOLAK' ? '❌' : '⏳');
            $statusLabel = $validated['status'] === 'DISETUJUI' ? 'Disetujui' : ($validated['status'] === 'DITOLAK' ? 'Ditolak' : 'Diproses');
            $catatanText = !empty($validated['catatan_approver']) ? " Catatan: {$validated['catatan_approver']}" : '';
            $tglMulaiFmt = $cutiRow->tanggal_mulai ? Carbon::parse($cutiRow->tanggal_mulai)->format('d/m/Y') : '-';
            $tglSelesaiFmt = $cutiRow->tanggal_selesai ? Carbon::parse($cutiRow->tanggal_selesai)->format('d/m/Y') : '-';
            $pesanNotif = "Pengajuan {$cutiRow->jenis} ({$tglMulaiFmt} s/d {$tglSelesaiFmt}) Anda telah {$statusLabel} oleh Bagian SDM.{$catatanText}";

            // 1. Simpan ke tabel `notifikasi` Supabase (Pusat Notifikasi Web & In-App Mobile)
            if (!empty($targetPegawaiId)) {
                DB::table('notifikasi')->insert([
                    'untuk_pegawai_id' => $targetPegawaiId,
                    'judul'            => "Pengajuan Cuti {$statusLabel} {$statusIcon}",
                    'pesan'            => $pesanNotif,
                    'dibaca'           => false,
                    'waktu'            => now(),
                    'created_at'       => now(),
                ]);
            }

            // 2. Kirim Push Notification OneSignal ke Mobile Phone
            if (!empty($cutiRow->nik) && class_exists(\App\Services\OneSignalService::class)) {
                \App\Services\OneSignalService::kirimNotifikasiPegawai(
                    $cutiRow->nik,
                    "Pengajuan Cuti {$statusLabel}",
                    $pesanNotif,
                    ['type' => 'cuti', 'id' => (string)$id, 'status' => $validated['status']]
                );
            }

            // 3. Catat di Audit Trail
            if (class_exists(\App\Http\Controllers\AuditLogController::class)) {
                $namaTarget = $cutiRow->nama_lengkap ?? $cutiRow->nik;
                \App\Http\Controllers\AuditLogController::record(
                    'UPDATE',
                    'Cuti',
                    "Verifikasi cuti #{$id} ({$cutiRow->jenis}) pegawai {$namaTarget} ({$cutiRow->nik}) menjadi {$validated['status']}.{$catatanText}"
                );
            }

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Update status cuti error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Gagal memproses persetujuan cuti: ' . $e->getMessage()]);
        }

        return back()->with('success', "Status pengajuan cuti pegawai berhasil diperbarui menjadi {$validated['status']} dan notifikasi telah dikirimkan.");
    }
}
