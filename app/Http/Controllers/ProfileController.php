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

        // Pastikan session dummy_pegawai terisi jika belum ada
        if (! session()->has('dummy_pegawai')) {
            app(PegawaiController::class)->index(request());
        }

        $allPegawai = session('dummy_pegawai', []);
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

        return view('profile.show', compact('userLogin', 'pegawai', 'detailTypes'));
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
