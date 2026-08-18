<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProfileController extends Controller
{
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

        // Simulasi data dokumen (Surat Kerja & Surat Diklat) jika belum ada
        if (! isset($pegawai['surat_kerja'])) {
            $pegawai['surat_kerja'] = [
                'nomor' => 'SK/SDM/2024/001',
                'judul' => 'Surat Keputusan Pengangkatan Pegawai Tetap',
                'tgl_terbit' => '2024-01-15',
                'file_name' => 'SK_Pengangkatan_Pegawai.pdf',
                'file_url' => '#',
            ];
        }

        if (! isset($pegawai['surat_diklat'])) {
            $pegawai['surat_diklat'] = [
                'nomor' => 'STP/SDM/2024/088',
                'judul' => 'Sertifikat Diklat & Pelatihan Manajemen Kepegawaian',
                'tgl_terbit' => '2024-05-20',
                'file_name' => 'Sertifikat_Diklat_SDM.pdf',
                'file_url' => '#',
            ];
        }

        return view('profile.show', compact('userLogin', 'pegawai', 'detailTypes'));
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
