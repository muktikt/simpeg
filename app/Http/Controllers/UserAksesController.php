<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserAksesController extends Controller
{
    /**
     * DATA DUMMY BERBASIS SESSION.
     *
     * Disamakan dengan sistem lama (userakses.php / tambah_userakses.php /
     * edit_userakses.php): tabel userlogin (username, password, nama, userlevel, foto).
     *
     * CATATAN: field "foto" (upload foto profil) di sistem lama TIDAK dibawa
     * ke versi ini - butuh setup disk storage sungguhan yang di luar scope
     * data dummy berbasis session saat ini. Field lain semua dipertahankan.
     *
     * Sama seperti edit_userakses.php asli: saat EDIT, password bersifat
     * OPSIONAL - kalau dikosongkan, password lama tetap dipakai.
     */
    protected function seedIfEmpty(): void
    {
        $allPegawai = app(PegawaiController::class)->all();

        $defaultRoles = [
            '5000000001' => '7', // H. Dedi Supriadi - Direksi (DIRUT)
            '5000000002' => '1', // Victoria Usang - SDM
            '6000000001' => '2', // Antony Loyal - Keuangan
            '4000000001' => '5', // Siti Rahmawati - Pegawai (Kadiv Admin)
            '4000000005' => '5', // Nur Aisyah Lestari - Pegawai (Kadiv Admin)
            '4000000006' => '5', // Agus Setiawan - Pegawai (Kadiv Teknik)
            '4000000002' => '5', // Ahmad Fauzi - Pegawai (KSPI)
            '4000000003' => '5', // Dedi Kurniawan - Pegawai (TPDPK)
            '3000000003' => '5', // Budi Santoso - Pegawai
        ];

        $defaultPasswords = [
            '3000000003' => 'pegawai123',
            '4000000001' => 'kadiv123',
            '4000000006' => 'kadivteknik2025',
            '4000000005' => 'kadivadmin2025',
            '4000000002' => 'kspi123',
            '4000000003' => 'tpdpk123',
            '5000000001' => 'dirut123',
            '5000000002' => 'sdm123',
            '6000000001' => 'keuangan123',
            '2000000001' => 'admin123',
            '2000000002' => 'keuangan123',
        ];

        $existing = session('dummy_userakses', []);
        $obsoleteNiks = ['4000000004', '1800004', '1800005', '1800003', '1711254', '1800001'];

        // Remove obsolete NIKs from userakses session
        $filtered = array_values(array_filter($existing, function ($item) use ($obsoleteNiks) {
            return ! in_array($item['username'] ?? '', $obsoleteNiks, true);
        }));

        $updated = count($filtered) !== count($existing);

        // Sync default passwords for existing items
        foreach ($filtered as &$item) {
            $nik = $item['username'] ?? '';
            if (isset($defaultPasswords[$nik]) && $item['password'] !== $defaultPasswords[$nik]) {
                $item['password'] = $defaultPasswords[$nik];
                $updated = true;
            }
        }
        unset($item);

        $existingNiks = array_column($filtered, 'username');
        $maxId = $filtered ? max(array_column($filtered, 'id')) : 0;

        foreach ($allPegawai as $p) {
            if (! in_array($p['nik'], $existingNiks, true)) {
                $maxId++;
                $filtered[] = [
                    'id' => $maxId,
                    'username' => $p['nik'],
                    'password' => $defaultPasswords[$p['nik']] ?? 'password',
                    'nama' => $p['nama'],
                    'userlevel' => $defaultRoles[$p['nik']] ?? '5',
                ];
                $updated = true;
            }
        }

        if ($updated || ! session()->has('dummy_userakses')) {
            session()->put('dummy_userakses', $filtered);
        }
    }

    protected function all(): array
    {
        $this->seedIfEmpty();

        return session('dummy_userakses', []);
    }

    protected function save(array $data): void
    {
        session()->put('dummy_userakses', $data);
    }

    public function index()
    {
        $users = collect($this->all())->sortBy('nama')->values();

        return view('user-akses.index', [
            'users' => $users,
            'roleList' => config('simpeg_roles'),
        ]);
    }

    public function create()
    {
        return view('user-akses.create', ['roleList' => config('simpeg_roles')]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|max:20',
            'password' => 'required|string|min:4',
            'nama' => 'required|string|max:100',
            'userlevel' => 'required|string',
        ]);

        // Validasi username unik manual (tidak pakai Rule::unique karena data di session, bukan DB).
        $sudahAda = collect($this->all())->contains('username', $validated['username']);
        if ($sudahAda) {
            return back()->withErrors(['username' => 'Username (NIK) ini sudah dipakai akun lain.'])->withInput();
        }

        $data = $this->all();
        $newId = $data ? max(array_column($data, 'id')) + 1 : 1;
        $validated['id'] = $newId;

        $data[] = $validated;
        $this->save($data);

        return redirect()->route('user-akses.index')->with('success', 'Akun pengguna berhasil ditambahkan.');
    }

    public function edit(int $id)
    {
        $user = collect($this->all())->firstWhere('id', $id);
        abort_if(! $user, 404);

        return view('user-akses.edit', [
            'user' => $user,
            'roleList' => config('simpeg_roles'),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'username' => 'required|string|max:20',
            'password' => 'nullable|string|min:4',
            'nama' => 'required|string|max:100',
            'userlevel' => 'required|string',
        ]);

        $sudahAda = collect($this->all())->contains(fn ($u) => $u['username'] === $validated['username'] && $u['id'] !== $id);
        if ($sudahAda) {
            return back()->withErrors(['username' => 'Username (NIK) ini sudah dipakai akun lain.'])->withInput();
        }

        $data = collect($this->all())->map(function ($u) use ($id, $validated) {
            if ($u['id'] === $id) {
                // Password opsional saat edit - kosongkan berarti tetap pakai yang lama.
                if (empty($validated['password'])) {
                    $validated['password'] = $u['password'];
                }
                $validated['id'] = $id;

                return $validated;
            }

            return $u;
        })->all();

        $this->save($data);

        return redirect()->route('user-akses.index')->with('success', 'Akun pengguna berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        $data = collect($this->all())->reject(fn ($u) => $u['id'] === $id)->values()->all();
        $this->save($data);

        return redirect()->route('user-akses.index')->with('success', 'Akun pengguna berhasil dihapus.');
    }
}
