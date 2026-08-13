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
        if (! session()->has('dummy_pegawai')) {
            app(PegawaiController::class)->index(request());
        }

        $allPegawai = session('dummy_pegawai', []);

        if (! session()->has('dummy_userakses')) {
            $defaultRoles = [
                '1711254' => '1', // Mukti - Admin
                '6000000001' => '1', // Rina - Admin (SDM)
                '5000000002' => '1', // Rina - Admin (SDM)
                '1800001' => '2', // Dewi - Keuangan
                '1800003' => '5', // Nur - Pegawai
                '1800004' => '7', // Bambang - Direksi
                '5000000001' => '7', // Dedi Supriadi - Direksi (DIRUT)
            ];

            $defaultPasswords = [
                '3000000003' => 'pegawai123',
                '4000000001' => 'kadivadmin123',
                '4000000004' => 'kadivteknik123',
                '4000000002' => 'kspi123',
                '4000000003' => 'tpdpk123',
                '5000000001' => 'dirut123',
                '6000000001' => 'sdm123',
                '5000000002' => 'sdm123',
            ];

            $userAkses = [];
            $id = 1;
            foreach ($allPegawai as $p) {
                $userAkses[] = [
                    'id' => $id++,
                    'username' => $p['nik'],
                    'password' => $defaultPasswords[$p['nik']] ?? 'password',
                    'nama' => $p['nama'],
                    'userlevel' => $defaultRoles[$p['nik']] ?? '5',
                ];
            }

            session()->put('dummy_userakses', $userAkses);
        } else {
            // Ensure any new pegawai not yet in dummy_userakses is synced
            $existing = session('dummy_userakses', []);
            $existingNiks = array_column($existing, 'username');
            $maxId = $existing ? max(array_column($existing, 'id')) : 0;

            $updated = false;
            foreach ($allPegawai as $p) {
                if (! in_array($p['nik'], $existingNiks, true)) {
                    $maxId++;
                    $existing[] = [
                        'id' => $maxId,
                        'username' => $p['nik'],
                        'password' => 'password',
                        'nama' => $p['nama'],
                        'userlevel' => '5',
                    ];
                    $updated = true;
                }
            }

            if ($updated) {
                session()->put('dummy_userakses', $existing);
            }
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
