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
            '1711001' => '7', // Nurpan - Direktur Utama
            '1711002' => '7', // Dr. Sunaryo - Direktur Umum
            '1711003' => '7', // Jojo Sutarjo - Direktur Teknik
            '1711254' => '1', // Heddy Kelana - Manajer SDM
            '1711157' => '1', // Cahrudin - Asmen Pembinaan SDM & K3
            '1711444' => '1', // Suwanto - Operator Penggajian & Administrasi SDM
            '1711590' => '1', // Riko Prahtama - Operator SDM & K3
            '1711567' => '1', // Asep Kurnadi - Operator Administrasi SDM
            '1711296' => '2', // Yayah Khaeriyah - Manajer Keuangan
            '1711145' => '2', // Ari Hendrayati - Keuangan
            '1711161' => '5', // Dodi Sudrajat - KSPI
            '1711446' => '5', // Edy Ratno Dirjo - Kadiv Teknik
            '1711479' => '5', // Candra Dewi Prihatiningsih - Kadiv Administrasi
        ];

        $defaultPasswords = [
            '1711001' => 'dirut123',
            '1711002' => 'dirum123',
            '1711003' => 'dirtek123',
            '1711254' => 'sdm123',
            '1711157' => 'sdm123',
            '1711444' => 'sdm123',
            '1711590' => 'sdm123',
            '1711567' => 'sdm123',
            '1711296' => 'keuangan123',
            '1711145' => 'keuangan123',
            '1711161' => 'kspi123',
            '1711446' => 'kadivteknik123',
            '1711479' => 'kadivadmin123',
        ];

        $existing = session('dummy_userakses', []);
        $obsoleteNiks = [
            '3000000003', '4000000001', '4000000002', '4000000003', 
            '4000000005', '4000000006', '5000000001', '5000000002', 
            '6000000001', '4000000004', '1800004', '1800005', '1800003', '1800001'
        ];

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
        try {
            $dbPegawai = \Illuminate\Support\Facades\DB::table('pegawai')
                ->select('id', 'nik', 'name', 'jabatan', 'role')
                ->orderBy('name')
                ->get();

            if ($dbPegawai->isNotEmpty()) {
                $users = [];
                $index = 1;
                foreach ($dbPegawai as $p) {
                    $roleLower = strtolower($p->role ?? '');
                    $userlevel = match ($roleLower) {
                        'direktur' => '7',
                        'sdm', 'admin' => '1',
                        'keuangan', 'keu' => '2',
                        default => '5',
                    };
                    $users[] = [
                        'id' => $index++,
                        'db_id' => $p->id,
                        'username' => (string) $p->nik,
                        'password' => $this->defaultPasswords[$p->nik] ?? 'password',
                        'nama' => $p->name ?? 'Pegawai',
                        'userlevel' => $userlevel,
                    ];
                }
                return $users;
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('DB userakses read failed: ' . $e->getMessage());
        }

        return session('dummy_userakses', []);
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

        $dbRole = match ($validated['userlevel']) {
            '1' => 'sdm',
            '2' => 'keuangan',
            '7' => 'direktur',
            default => 'pegawai',
        };

        try {
            \Illuminate\Support\Facades\DB::table('pegawai')
                ->where('nik', $validated['username'])
                ->update(['role' => $dbRole]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('DB userakses update role failed: ' . $e->getMessage());
        }

        \Illuminate\Support\Facades\Cache::forget('pegawai_master_cache');
        \Illuminate\Support\Facades\Cache::forget('simpeg_all_pegawai_list');

        return redirect()->route('user-akses.index')->with('success', 'Akun pengguna berhasil ditambahkan/diperbarui di database.');
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

        $dbRole = match ($validated['userlevel']) {
            '1' => 'sdm',
            '2' => 'keuangan',
            '7' => 'direktur',
            default => 'pegawai',
        };

        try {
            \Illuminate\Support\Facades\DB::table('pegawai')
                ->where('nik', $validated['username'])
                ->update(['role' => $dbRole]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('DB userakses update role failed: ' . $e->getMessage());
        }

        \Illuminate\Support\Facades\Cache::forget('pegawai_master_cache');
        \Illuminate\Support\Facades\Cache::forget('simpeg_all_pegawai_list');

        return redirect()->route('user-akses.index')->with('success', 'Hak akses akun berhasil diperbarui di database.');
    }

    public function destroy(int $id)
    {
        $user = collect($this->all())->firstWhere('id', $id);
        if ($user) {
            try {
                \Illuminate\Support\Facades\DB::table('pegawai')
                    ->where('nik', $user['username'])
                    ->update(['role' => 'pegawai']);
            } catch (\Throwable $e) {}
        }

        \Illuminate\Support\Facades\Cache::forget('pegawai_master_cache');
        \Illuminate\Support\Facades\Cache::forget('simpeg_all_pegawai_list');

        return redirect()->route('user-akses.index')->with('success', 'Hak akses akun berhasil direset ke Pegawai biasa.');
    }
}
