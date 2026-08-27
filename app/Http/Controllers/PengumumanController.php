<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PengumumanController extends Controller
{
    public const ROLES_LIST = [
        'pegawai' => 'Pegawai',
        'kadivKategori' => 'Kadiv Kategori',
        'kspi' => 'KSPI',
        'tpdpk' => 'TPDPK',
        'direktur' => 'Direktur (DIRUT)',
        'sdm' => 'SDM',
        'keuangan' => 'Keuangan',
    ];

    /**
     * Parsing array PostgreSQL (misal: "{pegawai,sdm}") menjadi array PHP biasa.
     */
    protected function parsePgArray(?string $pgArray): array
    {
        if (empty($pgArray)) {
            return array_keys(self::ROLES_LIST);
        }

        $trimmed = trim($pgArray, '{}');
        if ($trimmed === '') {
            return [];
        }

        return array_map('trim', explode(',', $trimmed));
    }

    /**
     * Format array PHP menjadi format PostgreSQL array string (misal: "{pegawai,sdm}").
     */
    protected function toPgArray(array $arr): string
    {
        if (empty($arr)) {
            return '{}';
        }
        return '{' . implode(',', array_map('trim', $arr)) . '}';
    }

    /**
     * Hitung status komputasi pengumuman (tayang, terjadwal, kedaluwarsa, nonaktif).
     */
    protected function computeStatus(object|array $row): array
    {
        $r = (array) $row;
        $now = now();
        $aktif = (bool) ($r['aktif'] ?? true);
        $terbitPada = !empty($r['terbit_pada']) ? Carbon::parse($r['terbit_pada']) : null;
        $kedaluwarsaPada = !empty($r['kedaluwarsa_pada']) ? Carbon::parse($r['kedaluwarsa_pada']) : null;

        $sudahKedaluwarsa = $kedaluwarsaPada !== null && $now->greaterThan($kedaluwarsaPada);
        $sedangTerjadwal = $aktif && $terbitPada !== null && $now->lessThan($terbitPada);
        $sedangTayang = $aktif && !$sudahKedaluwarsa && ($terbitPada === null || $now->greaterThanOrEqualTo($terbitPada));

        $statusKode = 'nonaktif';
        $statusLabel = 'Nonaktif';
        $badgeClass = 'badge-secondary';

        if ($sudahKedaluwarsa) {
            $statusKode = 'kedaluwarsa';
            $statusLabel = 'Kedaluwarsa';
            $badgeClass = 'badge-danger';
        } elseif ($sedangTerjadwal) {
            $statusKode = 'terjadwal';
            $statusLabel = 'Terjadwal';
            $badgeClass = 'badge-warning';
        } elseif ($sedangTayang) {
            $statusKode = 'tayang';
            $statusLabel = 'Tayang';
            $badgeClass = 'badge-success';
        }

        $r['status_kode'] = $statusKode;
        $r['status_label'] = $statusLabel;
        $r['badge_class'] = $badgeClass;
        $r['sudah_kedaluwarsa'] = $sudahKedaluwarsa;
        $r['sedang_terjadwal'] = $sedangTerjadwal;
        $r['sedang_tayang'] = $sedangTayang;
        $r['target_roles_array'] = is_array($r['target_roles'] ?? null) ? $r['target_roles'] : $this->parsePgArray($r['target_roles'] ?? null);

        return $r;
    }

    public function index(Request $request)
    {
        $filter = $request->get('filter', 'semua');
        $search = $request->get('q', '');

        // Auto-nonaktifkan yang sudah expired di database
        try {
            DB::table('pengumuman')
                ->where('aktif', true)
                ->whereNotNull('kedaluwarsa_pada')
                ->where('kedaluwarsa_pada', '<', now())
                ->update(['aktif' => false, 'updated_at' => now()]);
        } catch (\Throwable $e) {}

        try {
            $query = DB::table('pengumuman')->orderByDesc('disematkan')->orderByDesc('created_at');
            $rows = $query->get()->map(fn ($r) => $this->computeStatus($r));
        } catch (\Throwable $e) {
            $rows = collect();
        }

        if (!empty($search)) {
            $rows = $rows->filter(function ($item) use ($search) {
                return str_contains(strtolower($item['judul'] ?? ''), strtolower($search))
                    || str_contains(strtolower($item['isi'] ?? ''), strtolower($search));
            });
        }

        $counts = [
            'semua' => $rows->count(),
            'tayang' => $rows->where('status_kode', 'tayang')->count(),
            'terjadwal' => $rows->where('status_kode', 'terjadwal')->count(),
            'kedaluwarsa' => $rows->where('status_kode', 'kedaluwarsa')->count(),
            'nonaktif' => $rows->where('status_kode', 'nonaktif')->count(),
        ];

        if ($filter !== 'semua' && in_array($filter, ['tayang', 'terjadwal', 'kedaluwarsa', 'nonaktif'])) {
            $rows = $rows->where('status_kode', $filter)->values();
        } else {
            $rows = $rows->values();
        }

        return view('pengumuman.index', [
            'pengumuman' => $rows,
            'filter' => $filter,
            'search' => $search,
            'counts' => $counts,
            'rolesList' => self::ROLES_LIST,
        ]);
    }

    public function create()
    {
        return view('pengumuman.create', [
            'rolesList' => self::ROLES_LIST,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'isi' => 'required|string',
            'prioritas' => 'nullable|in:normal,penting',
            'disematkan' => 'nullable|boolean',
            'aktif' => 'nullable|boolean',
            'target_roles' => 'nullable|array',
            'terbit_pada' => 'nullable|date',
            'kedaluwarsa_pada' => 'nullable|date',
            'lampiran' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $userLogin = session('simpeg_user');
        $pembuatName = $userLogin['name'] ?? $userLogin['nama'] ?? 'SDM PERUMDAM';
        
        $pembuatId = null;
        if (!empty($userLogin['nik'])) {
            try {
                $pegawai = DB::table('pegawai')->where('nik', $userLogin['nik'])->first();
                $pembuatId = $pegawai ? $pegawai->id : null;
            } catch (\Throwable $e) {}
        }
        if (empty($pembuatId) && !empty($userLogin['id'])) {
            if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', (string)$userLogin['id'])) {
                $pembuatId = (string)$userLogin['id'];
            }
        }

        $targetRoles = $request->input('target_roles', array_keys(self::ROLES_LIST));
        if (empty($targetRoles)) {
            $targetRoles = array_keys(self::ROLES_LIST);
        }

        $lampiranUrl = null;
        $lampiranNama = null;

        if ($request->hasFile('lampiran')) {
            $file = $request->file('lampiran');
            $lampiranNama = $file->getClientOriginalName();
            $path = $file->store('pengumuman', 'public');
            $lampiranUrl = Storage::url($path);
        }

        $terbitPada = !empty($validated['terbit_pada']) 
            ? Carbon::parse($validated['terbit_pada'], 'Asia/Jakarta')->setTimezone('UTC')->toIso8601String() 
            : null;
        $kedaluwarsaPada = !empty($validated['kedaluwarsa_pada']) 
            ? Carbon::parse($validated['kedaluwarsa_pada'], 'Asia/Jakarta')->setTimezone('UTC')->toIso8601String() 
            : null;

        try {
            $newId = DB::table('pengumuman')->insertGetId([
                'judul' => trim($validated['judul']),
                'isi' => trim($validated['isi']),
                'aktif' => $request->boolean('aktif', true),
                'prioritas' => ($validated['prioritas'] ?? 'normal') === 'penting',
                'disematkan' => $request->boolean('disematkan', false),
                'target_roles' => $this->toPgArray($targetRoles),
                'pembuat' => $pembuatName,
                'pembuat_id' => $pembuatId,
                'terbit_pada' => $terbitPada,
                'kedaluwarsa_pada' => $kedaluwarsaPada,
                'lampiran_url' => $lampiranUrl,
                'lampiran_nama' => $lampiranNama,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Kirim Push Notification instan ke seluruh perangkat HP via OneSignal jika langsung aktif
            if ($request->boolean('aktif', true) && empty($terbitPada)) {
                \App\Services\OneSignalService::kirimPengumuman(
                    $validated['judul'],
                    $validated['isi'],
                    $targetRoles,
                    ['pengumuman_id' => $newId]
                );
            }

            return redirect()->route('pengumuman.index')->with('success', 'Pengumuman baru berhasil diterbitkan dan tersinkronisasi ke aplikasi mobile.');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Gagal menyimpan pengumuman: ' . $e->getMessage());
        }
    }

    public function show(int $id)
    {
        $row = DB::table('pengumuman')->where('id', $id)->first();
        abort_if(! $row, 404);

        $pengumuman = $this->computeStatus($row);

        return view('pengumuman.show', [
            'p' => $pengumuman,
            'rolesList' => self::ROLES_LIST,
        ]);
    }

    public function edit(int $id)
    {
        $row = DB::table('pengumuman')->where('id', $id)->first();
        abort_if(! $row, 404);

        $pengumuman = $this->computeStatus($row);

        return view('pengumuman.edit', [
            'p' => $pengumuman,
            'rolesList' => self::ROLES_LIST,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $row = DB::table('pengumuman')->where('id', $id)->first();
        abort_if(! $row, 404);

        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'isi' => 'required|string',
            'prioritas' => 'nullable|in:normal,penting',
            'disematkan' => 'nullable|boolean',
            'aktif' => 'nullable|boolean',
            'target_roles' => 'nullable|array',
            'terbit_pada' => 'nullable|date',
            'kedaluwarsa_pada' => 'nullable|date',
            'lampiran' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $targetRoles = $request->input('target_roles', array_keys(self::ROLES_LIST));
        if (empty($targetRoles)) {
            $targetRoles = array_keys(self::ROLES_LIST);
        }

        $lampiranUrl = $row->lampiran_url;
        $lampiranNama = $row->lampiran_nama;

        if ($request->hasFile('lampiran')) {
            $file = $request->file('lampiran');
            $lampiranNama = $file->getClientOriginalName();
            $path = $file->store('pengumuman', 'public');
            $lampiranUrl = Storage::url($path);
        } elseif ($request->boolean('hapus_lampiran')) {
            $lampiranUrl = null;
            $lampiranNama = null;
        }

        $terbitPada = !empty($validated['terbit_pada']) 
            ? Carbon::parse($validated['terbit_pada'], 'Asia/Jakarta')->setTimezone('UTC')->toIso8601String() 
            : null;
        $kedaluwarsaPada = !empty($validated['kedaluwarsa_pada']) 
            ? Carbon::parse($validated['kedaluwarsa_pada'], 'Asia/Jakarta')->setTimezone('UTC')->toIso8601String() 
            : null;

        try {
            DB::table('pengumuman')->where('id', $id)->update([
                'judul' => trim($validated['judul']),
                'isi' => trim($validated['isi']),
                'aktif' => $request->boolean('aktif', false),
                'prioritas' => ($validated['prioritas'] ?? 'normal') === 'penting',
                'disematkan' => $request->boolean('disematkan', false),
                'target_roles' => $this->toPgArray($targetRoles),
                'terbit_pada' => $terbitPada,
                'kedaluwarsa_pada' => $kedaluwarsaPada,
                'lampiran_url' => $lampiranUrl,
                'lampiran_nama' => $lampiranNama,
                'updated_at' => now(),
            ]);

            return redirect()->route('pengumuman.index')->with('success', 'Pengumuman "' . $validated['judul'] . '" berhasil diperbarui.');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Gagal memperbarui pengumuman: ' . $e->getMessage());
        }
    }

    public function destroy(int $id)
    {
        try {
            DB::table('pengumuman')->where('id', $id)->delete();
            return redirect()->route('pengumuman.index')->with('success', 'Pengumuman berhasil dihapus.');
        } catch (\Throwable $e) {
            return redirect()->route('pengumuman.index')->with('error', 'Gagal menghapus pengumuman: ' . $e->getMessage());
        }
    }

    public function toggleAktif(int $id)
    {
        $row = DB::table('pengumuman')->where('id', $id)->first();
        abort_if(! $row, 404);

        $now = now();
        $kedaluwarsa = !empty($row->kedaluwarsa_pada) ? Carbon::parse($row->kedaluwarsa_pada) : null;

        if (!$row->aktif && $kedaluwarsa !== null && $now->greaterThan($kedaluwarsa)) {
            return back()->with('error', 'Pengumuman sudah melewati batas waktu kedaluwarsa. Silakan edit jadwal kedaluwarsanya terlebih dahulu.');
        }

        DB::table('pengumuman')->where('id', $id)->update([
            'aktif' => ! $row->aktif,
            'updated_at' => now(),
        ]);

        $statusStr = ! $row->aktif ? 'diaktifkan / diterbitkan' : 'dinonaktifkan';
        return back()->with('success', "Pengumuman berhasil $statusStr.");
    }

    public function toggleSematkan(int $id)
    {
        $row = DB::table('pengumuman')->where('id', $id)->first();
        abort_if(! $row, 404);

        DB::table('pengumuman')->where('id', $id)->update([
            'disematkan' => ! $row->disematkan,
            'updated_at' => now(),
        ]);

        $pinStr = ! $row->disematkan ? 'disematkan ke posisi teratas' : 'dilepas dari sematan';
        return back()->with('success', "Pengumuman berhasil $pinStr.");
    }
}
