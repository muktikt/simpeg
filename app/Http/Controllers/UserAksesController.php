<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserAksesController extends Controller
{
    protected array $defaultPasswords = [
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
            return [];
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('DB userakses read failed: ' . $e->getMessage());
        }

        return [];
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
