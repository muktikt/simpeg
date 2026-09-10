<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Jenis data riwayat yang dipakai khusus di dalam CV (Diklat & Sertifikasi).
     * Polanya sama seperti PegawaiDetailController::TYPES, tapi di-scope
     * sendiri di sini karena diinput mandiri oleh pegawai lewat halaman Profil,
     * bukan oleh Admin SDM lewat halaman Data Pegawai.
     */
    public const CV_DETAIL_TYPES = ['diklat', 'sertifikasi'];

    public static function cvDetailFieldConfig(string $type): array
    {
        return match ($type) {
            'diklat' => [
                'title' => 'Diklat / Pelatihan',
                'fields' => [
                    ['key' => 'nama', 'label' => 'Nama Diklat/Pelatihan', 'type' => 'text'],
                    ['key' => 'penyelenggara', 'label' => 'Penyelenggara', 'type' => 'text'],
                    ['key' => 'tahun', 'label' => 'Tahun', 'type' => 'text'],
                ],
            ],
            'sertifikasi' => [
                'title' => 'Sertifikasi',
                'fields' => [
                    ['key' => 'nama', 'label' => 'Nama Sertifikat', 'type' => 'text'],
                    ['key' => 'penyelenggara', 'label' => 'Penyelenggara', 'type' => 'text'],
                    ['key' => 'tahun', 'label' => 'Tahun', 'type' => 'text'],
                ],
            ],
            default => abort(404),
        };
    }

    /**
     * CATATAN: profile.php di sistem lama ternyata masih template demo
     * AdminLTE yang belum pernah diisi data asli (isinya "Nina Mcintire,
     * Software Engineer", followers palsu, teks Lorem ipsum) - tidak
     * pernah dihubungkan ke data pegawai yang login sama sekali.
     *
     * Jadi di sini dibuat ulang jadi halaman yang beneran fungsional:
     * menampilkan data diri pegawai yang sedang login (dari session) +
     * form ganti password, mengikuti pola perubahan_kata_sandi.php yang
     * asli (Current Password wajib cocok dulu sebelum bisa diganti).
     */
    public function show()
    {
        $userLogin = session('simpeg_user');

        $allPegawai = app(PegawaiController::class)->all();
        $pegawai = collect($allPegawai)->firstWhere('nik', $userLogin['nik']);

        if (! $pegawai) {
            $pegawai = [
                'id' => 999,
                'nik' => $userLogin['nik'],
                'nama' => $userLogin['nama_peg'],
                'jabatan' => $userLogin['jabatan'],
                'unit_kerja' => 'Unit Kerja',
                'status_peg' => 'PT',
                'tgl_masuk' => date('Y-m-d'),
                'telp' => '-',
                'alamat' => '-',
                'keluarga' => [],
                'golongan' => [],
                'jabatan_riwayat' => [],
                'pendidikan' => [],
                'prestasi' => [],
            ];
        }

        $detailTypes = [];
        foreach (PegawaiDetailController::TYPES as $type) {
            $detailTypes[$type] = PegawaiDetailController::fieldConfig($type);
        }

        $pegawai['surat_kerja'] = $pegawai['surat_kerja'] ?? null;
        $pegawai['surat_diklat'] = $pegawai['surat_diklat'] ?? null;

        // Default data CV (biodata, diklat, sertifikasi, kompetensi) supaya
        // aman dipakai di view meski pegawai belum pernah mengisi sama sekali.
        $pegawai['biodata'] = $pegawai['biodata'] ?? [];
        $pegawai['diklat'] = $pegawai['diklat'] ?? [];
        $pegawai['sertifikasi'] = $pegawai['sertifikasi'] ?? [];
        $pegawai['kompetensi'] = $pegawai['kompetensi'] ?? [];

        $cvDetailTypes = [];
        foreach (self::CV_DETAIL_TYPES as $type) {
            $cvDetailTypes[$type] = self::cvDetailFieldConfig($type);
        }

        return view('profile.show', compact('userLogin', 'pegawai', 'detailTypes', 'cvDetailTypes'));
    }

    /**
     * Cari record pegawai (dummy/DB) milik user yang sedang login, dan
     * pastikan session dummy_pegawai punya entry dengan id yang sama supaya
     * data CV bisa disimpan/diupdate secara konsisten.
     */
    protected function currentPegawai(): array
    {
        $userLogin = session('simpeg_user');
        $allPegawai = app(PegawaiController::class)->all();
        $pegawai = collect($allPegawai)->firstWhere('nik', $userLogin['nik']);

        if (! $pegawai) {
            abort(404, 'Data pegawai tidak ditemukan.');
        }

        $data = session('dummy_pegawai', []);

        if (! collect($data)->firstWhere('id', $pegawai['id'])) {
            $data[] = $pegawai;
            session()->put('dummy_pegawai', $data);
        }

        return $pegawai;
    }

    public function uploadDokumen(Request $request)
    {
        $validated = $request->validate([
            'pegawai_id' => 'required|integer',
            'jenis_dokumen' => 'required|in:surat_kerja,surat_diklat',
            'nomor' => 'required|string|max:100',
            'judul' => 'required|string|max:200',
            'tgl_terbit' => 'required|date',
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5000',
        ]);

        $allPegawai = app(PegawaiController::class)->all();
        $fileName = 'Dokumen_' . time() . '.pdf';
        
        if ($request->hasFile('file')) {
            $fileName = $request->file('file')->getClientOriginalName();
        }

        $allPegawai = collect($allPegawai)->map(function ($p) use ($validated, $fileName) {
            if ($p['id'] == $validated['pegawai_id']) {
                $p[$validated['jenis_dokumen']] = [
                    'nomor' => $validated['nomor'],
                    'judul' => $validated['judul'],
                    'tgl_terbit' => $validated['tgl_terbit'],
                    'file_name' => $fileName,
                    'file_url' => '#',
                ];
            }
            return $p;
        })->all();

        session()->put('dummy_pegawai', $allPegawai);

        return back()->with('success', 'Dokumen berhasil diunggah/diperbarui oleh Admin SDM.');
    }

    /**
     * Simpan/perbarui Data Pribadi (Biodata Diri) untuk Curriculum Vitae.
     */
    public function updateBiodata(Request $request)
    {
        $validated = $request->validate([
            'tempat_lahir' => 'nullable|string|max:100',
            'tgl_lahir' => 'nullable|date',
            'jenis_kelamin' => 'nullable|in:Laki-laki,Perempuan',
            'status_kawin' => 'nullable|string|max:50',
            'alamat' => 'nullable|string|max:255',
            'telp' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:150',
        ]);

        $pegawai = $this->currentPegawai();

        $data = collect(session('dummy_pegawai', []))->map(function ($p) use ($pegawai, $validated) {
            if ($p['id'] === $pegawai['id']) {
                $p['biodata'] = $validated;

                // Alamat & telepon dipakai juga di header profil, jadi disinkronkan.
                if (! empty($validated['alamat'])) {
                    $p['alamat'] = $validated['alamat'];
                }
                if (! empty($validated['telp'])) {
                    $p['telp'] = $validated['telp'];
                }
            }

            return $p;
        })->all();

        session()->put('dummy_pegawai', $data);

        return redirect()->route('profile.show')->with('success', 'Data Pribadi berhasil disimpan.');
    }

    /**
     * Simpan/perbarui daftar Kompetensi/Keahlian (1 baris = 1 item).
     */
    public function updateKompetensi(Request $request)
    {
        $validated = $request->validate([
            'kompetensi_text' => 'nullable|string',
        ]);

        $items = collect(explode("\n", $validated['kompetensi_text'] ?? ''))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();

        $pegawai = $this->currentPegawai();

        $data = collect(session('dummy_pegawai', []))->map(function ($p) use ($pegawai, $items) {
            if ($p['id'] === $pegawai['id']) {
                $p['kompetensi'] = $items;
            }

            return $p;
        })->all();

        session()->put('dummy_pegawai', $data);

        return redirect()->route('profile.show')->with('success', 'Kompetensi/Keahlian berhasil disimpan.');
    }

    protected function validateCvDetailType(string $type): void
    {
        abort_unless(in_array($type, self::CV_DETAIL_TYPES, true), 404);
    }

    protected function cvDetailRules(string $type): array
    {
        $rules = [];

        foreach (self::cvDetailFieldConfig($type)['fields'] as $field) {
            $rules[$field['key']] = 'required|string|max:150';
        }

        return $rules;
    }

    /**
     * Tambah item Diklat/Sertifikasi milik pegawai yang login.
     */
    public function storeCvDetail(Request $request, string $type)
    {
        $this->validateCvDetailType($type);
        $validated = $request->validate($this->cvDetailRules($type));

        $pegawai = $this->currentPegawai();

        $data = collect(session('dummy_pegawai', []))->map(function ($p) use ($pegawai, $type, $validated) {
            if ($p['id'] === $pegawai['id']) {
                $items = $p[$type] ?? [];
                $newId = $items ? max(array_column($items, 'id')) + 1 : 1;
                $validated['id'] = $newId;
                $items[] = $validated;
                $p[$type] = $items;
            }

            return $p;
        })->all();

        session()->put('dummy_pegawai', $data);

        return redirect()->route('profile.show')->with('success', self::cvDetailFieldConfig($type)['title'].' berhasil ditambahkan.');
    }

    public function updateCvDetail(Request $request, string $type, int $itemId)
    {
        $this->validateCvDetailType($type);
        $validated = $request->validate($this->cvDetailRules($type));

        $pegawai = $this->currentPegawai();

        $data = collect(session('dummy_pegawai', []))->map(function ($p) use ($pegawai, $type, $itemId, $validated) {
            if ($p['id'] === $pegawai['id']) {
                $p[$type] = collect($p[$type] ?? [])->map(function ($item) use ($itemId, $validated) {
                    if ($item['id'] === $itemId) {
                        return array_merge($item, $validated);
                    }

                    return $item;
                })->all();
            }

            return $p;
        })->all();

        session()->put('dummy_pegawai', $data);

        return redirect()->route('profile.show')->with('success', self::cvDetailFieldConfig($type)['title'].' berhasil diperbarui.');
    }

    public function destroyCvDetail(string $type, int $itemId)
    {
        $this->validateCvDetailType($type);

        $pegawai = $this->currentPegawai();

        $data = collect(session('dummy_pegawai', []))->map(function ($p) use ($pegawai, $type, $itemId) {
            if ($p['id'] === $pegawai['id']) {
                $p[$type] = collect($p[$type] ?? [])->reject(fn ($item) => $item['id'] === $itemId)->values()->all();
            }

            return $p;
        })->all();

        session()->put('dummy_pegawai', $data);

        return redirect()->route('profile.show')->with('success', 'Data berhasil dihapus.');
    }

    /**
     * Halaman CV siap cetak (A4) - dipakai untuk tombol "Lihat CV" & "Download CV"
     * (download = print-to-PDF lewat browser, mengikuti pola dokumen-surat/cetak).
     */
    public function cvCetak()
    {
        $pegawai = $this->currentPegawai();

        // Ambil versi terbaru dari session (biar data yang baru disimpan langsung kepakai).
        $pegawai = collect(session('dummy_pegawai', []))->firstWhere('id', $pegawai['id']) ?? $pegawai;

        $pegawai['biodata'] = $pegawai['biodata'] ?? [];
        $pegawai['pendidikan'] = $pegawai['pendidikan'] ?? [];
        $pegawai['jabatan_riwayat'] = $pegawai['jabatan_riwayat'] ?? [];
        $pegawai['diklat'] = $pegawai['diklat'] ?? [];
        $pegawai['sertifikasi'] = $pegawai['sertifikasi'] ?? [];
        $pegawai['kompetensi'] = $pegawai['kompetensi'] ?? [];
        $pegawai['prestasi'] = $pegawai['prestasi'] ?? [];

        return view('profile.cv-cetak', compact('pegawai'));
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:4|confirmed',
        ]);

        $userLogin = session('simpeg_user');

        if ($validated['current_password'] !== $userLogin['password']) {
            return back()->withErrors(['current_password' => 'Password saat ini salah.']);
        }

        // Update password di session login yang sedang aktif.
        $userLogin['password'] = $validated['new_password'];
        session()->put('simpeg_user', $userLogin);

        // Cascade update juga ke daftar akun di modul Pengaturan Akun Pengguna,
        // supaya tetap konsisten kalau Admin buka daftar itu.
        $users = collect(session('dummy_userakses', []))->map(function ($u) use ($userLogin, $validated) {
            if ($u['username'] === $userLogin['nik']) {
                $u['password'] = $validated['new_password'];
            }

            return $u;
        })->all();
        session()->put('dummy_userakses', $users);

        return redirect()->route('profile.show')->with('success', 'Password berhasil diubah.');
    }
}