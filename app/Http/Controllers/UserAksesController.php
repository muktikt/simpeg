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
        if (! session()->has('dummy_userakses')) {
            session()->put('dummy_userakses', [
                ['id' => 1, 'username' => '1711254', 'password' => 'password', 'nama' => 'Mukti Kurniawan', 'userlevel' => '1'],
                ['id' => 2, 'username' => '1800001', 'password' => 'password', 'nama' => 'Dewi Anggraini', 'userlevel' => '2'],
                ['id' => 3, 'username' => '1800003', 'password' => 'password', 'nama' => 'Nur Hidayah', 'userlevel' => '5'],
                ['id' => 4, 'username' => '1800004', 'password' => 'password', 'nama' => 'Bambang Wijaya', 'userlevel' => '7'],
            ]);
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
